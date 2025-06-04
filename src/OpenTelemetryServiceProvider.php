<?php

declare(strict_types=1);

namespace Prism\OpenTelemetry;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Common\Time\Clock;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\TracerProviderBuilder;
use Prism\Prism\Telemetry\TelemetryManager;

class OpenTelemetryServiceProvider extends ServiceProvider
{
    protected ?TracerProvider $tracerProvider = null;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->app->extend('prism-telemetry', function (TelemetryManager $manager): TelemetryManager {
            $manager->extend('opentelemetry', fn (): OpenTelemetryDriver => $this->createOpenTelemetryDriver($manager));

            return $manager;
        });

        // Flush spans on application shutdown
        $this->app->terminating(function (): void {
            // Try to get the telemetry manager and flush if OpenTelemetry driver is active
            try {
                if ($this->app->bound('prism-telemetry')) {
                    $manager = $this->app->make('prism-telemetry');
                    $driver = $manager->driver('opentelemetry');
                    if (method_exists($driver, 'flush')) {
                        $driver->flush();
                    }
                }
            } catch (\Throwable) {
                // Silently handle flush errors
            }

            // Also try direct flush if we have the tracer provider
            if ($this->tracerProvider instanceof TracerProvider) {
                $this->tracerProvider->forceFlush();
            }
        });
    }

    protected function createOpenTelemetryDriver(?TelemetryManager $manager = null): OpenTelemetryDriver
    {
        $config = config('prism.telemetry.drivers.opentelemetry', []);

        $this->tracerProvider = $this->createTracerProvider($config);

        return new OpenTelemetryDriver($this->tracerProvider, $config, $manager);
    }

    protected function createTracerProvider(array $config): TracerProviderInterface
    {
        $resourceBuilder = ResourceInfoFactory::emptyResource()
            ->merge(ResourceInfo::create(Attributes::create([
                'service.name' => $config['service_name'] ?? 'prism',
                'service.version' => $config['service_version'] ?? '1.0.0',
            ])));

        $spanProcessor = $this->createSpanProcessor($config);

        return (new TracerProviderBuilder)
            ->addSpanProcessor($spanProcessor)
            ->setResource($resourceBuilder)
            ->build();
    }

    protected function createSpanProcessor(array $config): BatchSpanProcessor
    {
        $exporter = $this->createExporter($config);

        return new BatchSpanProcessor(
            $exporter,
            Clock::getDefault()
        );
    }

    protected function createExporter(array $config): SpanExporter
    {
        $transport = (new OtlpHttpTransportFactory)->create($config['endpoint'], 'application/x-protobuf');

        return new SpanExporter($transport);
    }
}
