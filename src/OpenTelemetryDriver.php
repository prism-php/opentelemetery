<?php

declare(strict_types=1);

namespace Prism\OpenTelemetry;

use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextInterface;
use Prism\Prism\Contracts\TelemetryDriver;

class OpenTelemetryDriver implements TelemetryDriver
{
    /**
     * @var array<string, array{span: SpanInterface, context: ContextInterface}>
     */
    protected array $activeSpans = [];

    public function __construct(
        protected TracerInterface $tracer
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function startSpan(string $name, array $attributes = [], ?string $parentId = null): string
    {
        $parentContext = null;

        if ($parentId !== null && isset($this->activeSpans[$parentId])) {
            $parentContext = $this->activeSpans[$parentId]['context'];
        }

        $span = $this->createSpan($name, $attributes, $parentContext);
        $context = $span->storeInContext($parentContext ?? Context::getCurrent());
        $spanId = $this->generateSpanId();

        $this->activeSpans[$spanId] = [
            'span' => $span,
            'context' => $context,
        ];

        return $spanId;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function endSpan(string $contextId, array $attributes = []): void
    {
        if (! isset($this->activeSpans[$contextId])) {
            return;
        }

        $activeSpan = $this->activeSpans[$contextId];
        $span = $activeSpan['span'];

        foreach ($attributes as $key => $value) {
            if ($key === '') {
                continue;
            }
            if (! is_string($key)) {
                continue;
            }
            if (is_scalar($value) || $value === null) {
                $span->setAttribute($key, $value);
            } elseif (is_array($value)) {
                $span->setAttribute($key, json_encode($value));
            } else {
                $span->setAttribute($key, serialize($value));
            }
        }

        $span->setStatus(StatusCode::STATUS_OK);
        $span->end();

        unset($this->activeSpans[$contextId]);
    }

    public function recordException(string $contextId, \Throwable $exception): void
    {
        if (! isset($this->activeSpans[$contextId])) {
            return;
        }

        $span = $this->activeSpans[$contextId]['span'];

        $span->recordException($exception, [
            'exception.escaped' => false,
        ]);

        $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createSpan(string $name, array $attributes, ?ContextInterface $parentContext = null): SpanInterface
    {
        if ($name === '') {
            $name = 'unknown';
        }

        $spanBuilder = $this->tracer
            ->spanBuilder($name)
            ->setSpanKind(SpanKind::KIND_INTERNAL);

        foreach ($attributes as $key => $value) {
            if ($key === '') {
                continue;
            }
            if (! is_string($key)) {
                continue;
            }
            if (is_scalar($value) || $value === null) {
                $spanBuilder->setAttribute($key, $value);
            } elseif (is_array($value)) {
                $spanBuilder->setAttribute($key, json_encode($value));
            } else {
                $spanBuilder->setAttribute($key, serialize($value));
            }
        }

        if ($parentContext instanceof \OpenTelemetry\Context\ContextInterface) {
            return $spanBuilder->setParent($parentContext)->startSpan();
        }

        return $spanBuilder->startSpan();
    }

    protected function generateSpanId(): string
    {
        return bin2hex(random_bytes(8));
    }
}
