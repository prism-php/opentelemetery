<?php

declare(strict_types=1);

use OpenTelemetry\API\Trace\SpanBuilderInterface;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use Prism\OpenTelemetry\OpenTelemetryDriver;
use Prism\OpenTelemetry\OpenTelemetrySpan;
use Prism\Prism\Telemetry\ValueObjects\TelemetryAttribute;

it('creates spans with correct attributes', function (): void {
    $tracerProvider = mock(TracerProviderInterface::class);
    $tracer = mock(TracerInterface::class);
    $spanBuilder = mock(SpanBuilderInterface::class);
    $otelSpan = mock(SpanInterface::class);

    $tracerProvider->expects('getTracer')
        ->with('prism', '1.0.0', 'https://opentelemetry.io/schemas/1.21.0')
        ->andReturn($tracer);

    $tracer->expects('spanBuilder')
        ->with('test-operation')
        ->andReturn($spanBuilder);

    $spanBuilder->expects('startSpan')
        ->andReturn($otelSpan);

    $otelSpan->allows('setAttribute')->andReturn($otelSpan);

    $driver = new OpenTelemetryDriver($tracerProvider);

    $span = $driver->startSpan('test-operation', [
        TelemetryAttribute::ProviderName->value => 'openai',
        'custom.attribute' => 'value',
    ]);

    expect($span)->toBeInstanceOf(OpenTelemetrySpan::class);
});

it('creates spans with start time', function (): void {
    $tracerProvider = mock(TracerProviderInterface::class);
    $tracer = mock(TracerInterface::class);
    $spanBuilder = mock(SpanBuilderInterface::class);
    $otelSpan = mock(SpanInterface::class);

    $startTime = microtime(true);
    $expectedNanoTime = (int) ($startTime * 1_000_000_000);

    $tracerProvider->allows('getTracer')->andReturn($tracer);
    $tracer->allows('spanBuilder')->andReturn($spanBuilder);

    $spanBuilder->expects('setStartTimestamp')
        ->with($expectedNanoTime)
        ->andReturn($spanBuilder);

    $spanBuilder->allows('startSpan')->andReturn($otelSpan);
    $otelSpan->allows('setAttribute')->andReturn($otelSpan);

    $driver = new OpenTelemetryDriver($tracerProvider);

    $span = $driver->startSpan('test-operation', [], $startTime);

    expect($span)->toBeInstanceOf(OpenTelemetrySpan::class);
});

it('handles span execution with callback successfully', function (): void {
    $tracerProvider = mock(TracerProviderInterface::class);
    $tracer = mock(TracerInterface::class);
    $spanBuilder = mock(SpanBuilderInterface::class);
    $otelSpan = mock(SpanInterface::class);

    $tracerProvider->allows('getTracer')->andReturn($tracer);
    $tracer->allows('spanBuilder')->andReturn($spanBuilder);
    $spanBuilder->allows('startSpan')->andReturn($otelSpan);

    $otelSpan->allows('setAttribute')->andReturn($otelSpan);
    $otelSpan->allows('setStatus');
    $otelSpan->allows('end');

    $driver = new OpenTelemetryDriver($tracerProvider);

    $result = $driver->span('test-operation', [], function ($span): string {
        expect($span)->toBeInstanceOf(OpenTelemetrySpan::class);

        return 'success';
    });

    expect($result)->toBe('success');
});

it('handles span execution with callback exception', function (): void {
    $tracerProvider = mock(TracerProviderInterface::class);
    $tracer = mock(TracerInterface::class);
    $spanBuilder = mock(SpanBuilderInterface::class);
    $otelSpan = mock(SpanInterface::class);

    $tracerProvider->allows('getTracer')->andReturn($tracer);
    $tracer->allows('spanBuilder')->andReturn($spanBuilder);
    $spanBuilder->allows('startSpan')->andReturn($otelSpan);

    $otelSpan->allows('setAttribute')->andReturn($otelSpan);
    $otelSpan->allows('setStatus');
    $otelSpan->allows('end');

    $driver = new OpenTelemetryDriver($tracerProvider);

    expect(fn (): mixed => $driver->span('test-operation', [], function ($span): void {
        throw new \Exception('Test exception');
    }))->toThrow(\Exception::class, 'Test exception');
});
