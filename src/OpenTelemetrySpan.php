<?php

declare(strict_types=1);

namespace Prism\OpenTelemetry;

use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\StatusCode;
use Prism\Prism\Telemetry\Contracts\Span;
use Prism\Prism\Telemetry\ValueObjects\SpanStatus;
use Prism\Prism\Telemetry\ValueObjects\TelemetryAttribute;

class OpenTelemetrySpan implements Span
{
    protected float $startTime;

    protected ?float $endTime = null;

    public function __construct(
        protected SpanInterface $otelSpan,
        array $attributes = []
    ) {
        $this->startTime = microtime(true);
        $this->setAttributes($attributes);
    }

    public function setAttribute(TelemetryAttribute|string $key, mixed $value): self
    {
        $attributeKey = $key instanceof TelemetryAttribute ? $key->value : $key;

        $this->otelSpan->setAttribute($attributeKey, $this->normalizeValue($value));

        return $this;
    }

    public function setAttributes(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    public function addEvent(string $name, array $attributes = []): self
    {
        $normalizedAttributes = [];
        foreach ($attributes as $key => $value) {
            $attributeKey = $key instanceof TelemetryAttribute ? $key->value : $key;
            $normalizedAttributes[$attributeKey] = $this->normalizeValue($value);
        }

        $this->otelSpan->addEvent($name, $normalizedAttributes);

        return $this;
    }

    public function setStatus(SpanStatus $status, ?string $description = null): self
    {
        $otelStatus = match ($status) {
            SpanStatus::Ok => StatusCode::STATUS_OK,
            SpanStatus::Error => StatusCode::STATUS_ERROR,
            SpanStatus::Timeout => StatusCode::STATUS_ERROR,
            SpanStatus::Cancelled => StatusCode::STATUS_ERROR,
        };

        $this->otelSpan->setStatus($otelStatus, $description);

        return $this;
    }

    public function end(?float $endTime = null): void
    {
        $this->endTime = $endTime ?? microtime(true);

        if ($endTime !== null) {
            $this->otelSpan->end((int) ($endTime * 1_000_000_000)); // Convert to nanoseconds
        } else {
            $this->otelSpan->end();
        }
    }

    public function isRecording(): bool
    {
        return $this->otelSpan->isRecording();
    }

    public function getName(): string
    {
        return $this->otelSpan->getName() ?? '';
    }

    public function getStartTime(): float
    {
        return $this->startTime;
    }

    public function getDuration(): ?float
    {
        if ($this->endTime === null) {
            return null;
        }

        return $this->endTime - $this->startTime;
    }

    public function getOtelSpan(): SpanInterface
    {
        return $this->otelSpan;
    }

    protected function normalizeValue(mixed $value): string|int|float|bool|null
    {
        if (is_scalar($value) || $value === null) {
            return $value;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            return json_encode($value);
        }

        return (string) $value;
    }
}
