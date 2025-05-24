<?php

declare(strict_types=1);

namespace Prism\OpenTelemetry;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

class OpenTelemetryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->make('telemetry-manager')->extend('opentelemetry', fn ($app): OpenTelemetryDriver => new OpenTelemetryDriver(
            $app->make('opentelemetry.tracer')
        ));
    }

    public function register(): void
    {
        $this->registerTracer();
    }

    protected function registerTracer(): void
    {
        $this->app->singleton('opentelemetry.tracer', function (): TracerInterface {
            if (! config('prism.telemetry.enabled', false)) {
                return Globals::tracerProvider()->getTracer('opentelemetry');
            }

            $resource = ResourceInfoFactory::emptyResource()->merge(
                ResourceInfo::create(
                    Attributes::create([
                        'service.name' => config('prism.telemetry.drivers.opentelemetry.service_name', 'prism-playground'),
                        'service.version' => config('prism.telemetry.drivers.opentelemetry.service_version', '1.0.0'),
                    ])
                )
            );

            $endpoint = config('prism.telemetry.drivers.opentelemetry.endpoint', 'http://localhost:4318/v1/traces');

            $spanExporter = new SpanExporter(
                (new OtlpHttpTransportFactory)->create(
                    $endpoint,
                    'application/json'
                )
            );

            $tracerProvider = TracerProvider::builder()
                ->addSpanProcessor(new SimpleSpanProcessor($spanExporter))
                ->setResource($resource)
                ->build();

            return $tracerProvider->getTracer('opentelemetry');
        });

        $this->app->alias('opentelemetry.tracer', TracerInterface::class);
    }
}
