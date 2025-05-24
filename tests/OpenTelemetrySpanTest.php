<?php

declare(strict_types=1);

use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\StatusCode;
use Prism\OpenTelemetry\OpenTelemetrySpan;
use Prism\Prism\Telemetry\ValueObjects\SpanStatus;
use Prism\Prism\Telemetry\ValueObjects\TelemetryAttribute;

it('sets attributes correctly', function (): void {
    $otelSpan = mock(SpanInterface::class);

    $otelSpan->expects('setAttribute')
        ->with('test.attribute', 'value')
        ->andReturn($otelSpan);

    $span = new OpenTelemetrySpan($otelSpan);

    $result = $span->setAttribute('test.attribute', 'value');

    expect($result)->toBe($span);
});

it('sets multiple attributes', function (): void {
    $otelSpan = mock(SpanInterface::class);

    $otelSpan->allows('setAttribute')->andReturn($otelSpan);

    $span = new OpenTelemetrySpan($otelSpan);

    $result = $span->setAttributes([
        'attr1' => 'value1',
        'attr2' => 'value2',
    ]);

    expect($result)->toBe($span);
});

it('sets attributes with TelemetryAttribute enum', function (): void {
    $otelSpan = mock(SpanInterface::class);

    $otelSpan->expects('setAttribute')
        ->with(TelemetryAttribute::ProviderName->value, 'openai')
        ->andReturn($otelSpan);

    $span = new OpenTelemetrySpan($otelSpan);

    $result = $span->setAttribute(TelemetryAttribute::ProviderName, 'openai');

    expect($result)->toBe($span);
});

it('adds events with attributes', function (): void {
    $otelSpan = mock(SpanInterface::class);

    $otelSpan->expects('addEvent')
        ->with('test-event', ['key' => 'value'])
        ->andReturn($otelSpan);

    $span = new OpenTelemetrySpan($otelSpan);

    $result = $span->addEvent('test-event', ['key' => 'value']);

    expect($result)->toBe($span);
});

it('sets status correctly', function (): void {
    $otelSpan = mock(SpanInterface::class);

    $otelSpan->expects('setStatus')
        ->with(StatusCode::STATUS_OK, null)
        ->andReturn($otelSpan);

    $span = new OpenTelemetrySpan($otelSpan);

    $result = $span->setStatus(SpanStatus::Ok);

    expect($result)->toBe($span);
});

it('sets error status with description', function (): void {
    $otelSpan = mock(SpanInterface::class);

    $otelSpan->expects('setStatus')
        ->with(StatusCode::STATUS_ERROR, 'Test error')
        ->andReturn($otelSpan);

    $span = new OpenTelemetrySpan($otelSpan);

    $result = $span->setStatus(SpanStatus::Error, 'Test error');

    expect($result)->toBe($span);
});

it('ends span without timestamp', function (): void {
    $otelSpan = mock(SpanInterface::class);

    $otelSpan->expects('end')
        ->withNoArgs();

    $span = new OpenTelemetrySpan($otelSpan);

    $span->end();

    expect($span->getDuration())->toBeFloat();
});

it('ends span with timestamp', function (): void {
    $otelSpan = mock(SpanInterface::class);

    $endTime = microtime(true);
    $expectedNanoTime = (int) ($endTime * 1_000_000_000);

    $otelSpan->expects('end')
        ->with($expectedNanoTime);

    $span = new OpenTelemetrySpan($otelSpan);

    $span->end($endTime);

    expect($span->getDuration())->toBeFloat();
});

it('returns recording status', function (): void {
    $otelSpan = mock(SpanInterface::class);

    $otelSpan->expects('isRecording')
        ->andReturn(true);

    $span = new OpenTelemetrySpan($otelSpan);

    expect($span->isRecording())->toBeTrue();
});

it('returns span name', function (): void {
    $otelSpan = mock(SpanInterface::class);

    $otelSpan->expects('getName')
        ->andReturn('test-span');

    $span = new OpenTelemetrySpan($otelSpan);

    expect($span->getName())->toBe('test-span');
});

it('returns start time', function (): void {
    $otelSpan = mock(SpanInterface::class);

    $span = new OpenTelemetrySpan($otelSpan);

    expect($span->getStartTime())->toBeFloat();
});

it('returns null duration when not ended', function (): void {
    $otelSpan = mock(SpanInterface::class);

    $span = new OpenTelemetrySpan($otelSpan);

    expect($span->getDuration())->toBeNull();
});

it('normalizes array values to JSON', function (): void {
    $otelSpan = mock(SpanInterface::class);

    $otelSpan->expects('setAttribute')
        ->with('test.array', '["item1","item2"]')
        ->andReturn($otelSpan);

    $span = new OpenTelemetrySpan($otelSpan);

    $span->setAttribute('test.array', ['item1', 'item2']);
});

it('normalizes object values to JSON', function (): void {
    $otelSpan = mock(SpanInterface::class);

    $object = (object) ['key' => 'value'];

    $otelSpan->expects('setAttribute')
        ->with('test.object', '{"key":"value"}')
        ->andReturn($otelSpan);

    $span = new OpenTelemetrySpan($otelSpan);

    $span->setAttribute('test.object', $object);
});
