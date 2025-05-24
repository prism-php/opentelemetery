<?php

declare(strict_types=1);

namespace Prism\OpenTelemetry;

use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Trace\TracerProvider;
use Prism\Prism\Telemetry\Contracts\Span;
use Prism\Prism\Telemetry\Contracts\TelemetryDriver;
use Prism\Prism\Telemetry\TelemetryManager;
use Prism\Prism\Telemetry\ValueObjects\SpanStatus;

class OpenTelemetryDriver implements TelemetryDriver
{
    protected TracerInterface $tracer;

    public function __construct(
        protected TracerProviderInterface $tracerProvider,
        protected array $config = [],
        protected ?TelemetryManager $telemetryManager = null
    ) {
        $this->tracer = $tracerProvider->getTracer(
            name: 'prism',
            version: '1.0.0',
            schemaUrl: 'https://opentelemetry.io/schemas/1.21.0'
        );
    }

    public function startSpan(string $name, array $attributes = [], ?float $startTime = null): Span
    {
        $spanBuilder = $this->tracer->spanBuilder($name);

        if ($startTime !== null) {
            $spanBuilder->setStartTimestamp((int) ($startTime * 1_000_000_000)); // Convert to nanoseconds
        }

        // Check if there's a current span in Prism's TelemetryManager and use it as parent
        $currentSpan = $this->telemetryManager?->current();
        if ($currentSpan instanceof OpenTelemetrySpan) {
            try {
                // Temporarily activate the current span to get the proper context
                $scope = $currentSpan->getOtelSpan()->activate();
                $parentContext = Context::getCurrent();
                $scope->detach();

                $spanBuilder->setParent($parentContext);
            } catch (\Throwable) {
                // If context activation fails, fall back to current context
                $spanBuilder->setParent(Context::getCurrent());
            }
        } else {
            // Use the current OpenTelemetry context if available
            $spanBuilder->setParent(Context::getCurrent());
        }

        $otelSpan = $spanBuilder->startSpan();

        return new OpenTelemetrySpan($otelSpan, $attributes);
    }

    public function span(string $name, array $attributes, callable $callback): mixed
    {
        $span = $this->startSpan($name, $attributes);

        // Activate the span in the context so child spans can be created properly
        $scope = $span->getOtelSpan()->activate();

        try {
            $result = $callback($span);
            $span->setStatus(SpanStatus::Ok);

            return $result;
        } catch (\Throwable $e) {
            $span->setStatus(
                SpanStatus::Error,
                $e->getMessage()
            );
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    public function flush(): void
    {
        if ($this->tracerProvider instanceof TracerProvider) {
            $this->tracerProvider->forceFlush();
        }
    }

    public function __destruct()
    {
        $this->flush();
    }
}
