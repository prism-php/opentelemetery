<?php

declare(strict_types=1);

use OpenTelemetry\API\Trace\TracerInterface;
use Prism\OpenTelemetry\OpenTelemetryDriver;

it('can resolve tracer interface', function (): void {
    $tracer = app(TracerInterface::class);

    expect($tracer)->toBeInstanceOf(TracerInterface::class);
});

it('can resolve opentelemetry driver', function (): void {
    $driver = app(OpenTelemetryDriver::class);

    expect($driver)->toBeInstanceOf(OpenTelemetryDriver::class);
});

it('can use opentelemetry configuration', function (): void {
    config([
        'prism.telemetry.drivers.opentelemetry' => [
            'service_name' => 'test-service',
            'service_version' => '2.0.0',
            'endpoint' => 'http://test:4318/v1/traces',
        ],
    ]);

    expect(config('prism.telemetry.drivers.opentelemetry.service_name'))->toBe('test-service');
    expect(config('prism.telemetry.drivers.opentelemetry.service_version'))->toBe('2.0.0');
    expect(config('prism.telemetry.drivers.opentelemetry.endpoint'))->toBe('http://test:4318/v1/traces');
});

it('can work with enabled configuration', function (): void {
    config(['prism.telemetry.enabled' => true]);

    $driver = app(OpenTelemetryDriver::class);
    $spanId = $driver->startSpan('test-span');

    expect($spanId)->toBeString()->not->toBeEmpty();

    $driver->endSpan($spanId);
});
