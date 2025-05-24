<?php

declare(strict_types=1);

use OpenTelemetry\SDK\Trace\TracerProvider;
use Prism\OpenTelemetry\OpenTelemetryDriver;

it('can start and end spans', function (): void {
    $tracerProvider = TracerProvider::builder()->build();
    $tracer = $tracerProvider->getTracer('test');
    $driver = new OpenTelemetryDriver($tracer);

    $spanId = $driver->startSpan('test-span', ['test.attribute' => 'value']);

    expect($spanId)->toBeString()->not->toBeEmpty();

    $driver->endSpan($spanId, ['end.attribute' => 'end-value']);
});

it('can create nested spans', function (): void {
    $tracerProvider = TracerProvider::builder()->build();
    $tracer = $tracerProvider->getTracer('test');
    $driver = new OpenTelemetryDriver($tracer);

    $parentSpanId = $driver->startSpan('parent-span');
    $childSpanId = $driver->startSpan('child-span', [], $parentSpanId);

    expect($parentSpanId)->toBeString()->not->toBeEmpty();
    expect($childSpanId)->toBeString()->not->toBeEmpty();
    expect($childSpanId)->not->toBe($parentSpanId);

    $driver->endSpan($childSpanId);
    $driver->endSpan($parentSpanId);
});

it('can record exceptions', function (): void {
    $tracerProvider = TracerProvider::builder()->build();
    $tracer = $tracerProvider->getTracer('test');
    $driver = new OpenTelemetryDriver($tracer);

    $spanId = $driver->startSpan('error-span');
    $exception = new \Exception('Test exception');

    $driver->recordException($spanId, $exception);
    $driver->endSpan($spanId);

    expect(true)->toBeTrue(); // If we reach here, no exception was thrown
});

it('handles invalid span ids gracefully', function (): void {
    $tracerProvider = TracerProvider::builder()->build();
    $tracer = $tracerProvider->getTracer('test');
    $driver = new OpenTelemetryDriver($tracer);

    $driver->endSpan('invalid-span-id');
    $driver->recordException('invalid-span-id', new \Exception('Test'));

    expect(true)->toBeTrue(); // If we reach here, no exception was thrown
});

it('can handle various attribute types', function (): void {
    $tracerProvider = TracerProvider::builder()->build();
    $tracer = $tracerProvider->getTracer('test');
    $driver = new OpenTelemetryDriver($tracer);

    $attributes = [
        'string' => 'value',
        'int' => 42,
        'float' => 3.14,
        'bool' => true,
        'null' => null,
        'array' => ['key' => 'value'],
        'object' => new \stdClass,
    ];

    $spanId = $driver->startSpan('attribute-test', $attributes);
    $driver->endSpan($spanId, $attributes);

    expect(true)->toBeTrue(); // If we reach here, no exception was thrown
});
