![](assets/banner.webp)

<p align="center">
    <a href="https://packagist.org/packages/prism-php/opentelemetery">
        <img src="https://poser.pugx.org/prism-php/opentelemetery/d/total.svg" alt="Total Downloads">
    </a>
    <a href="https://packagist.org/packages/prism-php/opentelemetery">
        <img src="https://poser.pugx.org/prism-php/opentelemetery/v/stable.svg" alt="Latest Stable Version">
    </a>
    <a href="https://packagist.org/packages/prism-php/opentelemetery">
        <img src="https://poser.pugx.org/prism-php/opentelemetery/license.svg" alt="License">
    </a>
</p>

# Prism OpenTelemetry

OpenTelemetry telemetry driver for Prism PHP applications. This package integrates with Prism's telemetry system to export spans to OpenTelemetry-compatible backends like Jaeger, Zipkin, or cloud providers.

## Installation

Install the package via Composer:

```bash
composer require prism-php/opentelemetry
```

## Configuration

Configure Prism to use the OpenTelemetry driver in your `config/prism.php`:

```php
// config/prism.php
'telemetry' => [
    'enabled' => env('PRISM_TELEMETRY_ENABLED', true),
    'default' => env('PRISM_TELEMETRY_DRIVER', 'opentelemetry'),
    
    'drivers' => [
        'opentelemetry' => [
            'driver' => 'opentelemetry',
            'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://localhost:4318/v1/traces'),
            'headers' => env('OTEL_EXPORTER_OTLP_HEADERS', ''),
            'service_name' => env('OTEL_SERVICE_NAME', 'prism'),
            'service_version' => env('OTEL_SERVICE_VERSION', '1.0.0'),
        ],
    ],
],
```

Configure your environment variables:

```env
# OpenTelemetry Configuration
PRISM_TELEMETRY_DRIVER=opentelemetry
OTEL_EXPORTER_OTLP_ENDPOINT=http://localhost:4318/v1/traces
OTEL_EXPORTER_OTLP_HEADERS=
OTEL_SERVICE_NAME=prism
OTEL_SERVICE_VERSION=1.0.0

# For cloud providers, you might need authentication headers:
# OTEL_EXPORTER_OTLP_HEADERS="authorization=Bearer your-token,x-api-key=your-key"
```

## Usage

Once configured, Prism will automatically export telemetry data to your OpenTelemetry collector. The driver supports:

- **Span creation and management** - Automatic span lifecycle handling
- **Attribute setting** - Support for all OpenTelemetry attribute types
- **Event recording** - Add events to spans for detailed tracing
- **Error tracking** - Automatic error status and exception recording
- **Distributed tracing** - Full trace context propagation

## Backend Integrations

### Jaeger
```env
OTEL_EXPORTER_OTLP_ENDPOINT=http://jaeger-collector:14268/api/traces
```

### Zipkin
```env
OTEL_EXPORTER_OTLP_ENDPOINT=http://zipkin:9411/api/v2/spans
```

### Cloud Providers

**AWS X-Ray** (via OpenTelemetry Collector):
```env
OTEL_EXPORTER_OTLP_ENDPOINT=http://aws-otel-collector:4318/v1/traces
```

**Google Cloud Trace**:
```env
OTEL_EXPORTER_OTLP_ENDPOINT=https://cloudtrace.googleapis.com/v1/projects/PROJECT_ID/traces
OTEL_EXPORTER_OTLP_HEADERS="authorization=Bearer gcp-token"
```

**Azure Monitor**:
```env
OTEL_EXPORTER_OTLP_ENDPOINT=https://your-app.applicationinsights.azure.com/v1/traces
OTEL_EXPORTER_OTLP_HEADERS="x-api-key=your-instrumentation-key"
```

## Performance

This driver uses OpenTelemetry's `BatchSpanProcessor` for optimal performance and reliability:

- **Batched exports** - Reduces network overhead
- **Asynchronous processing** - Non-blocking span collection
- **Memory efficient** - Automatic span buffer management
- **Failure resilient** - Built-in retry mechanisms

## Security

- Always use HTTPS endpoints in production
- Store authentication tokens securely in environment variables
- Avoid logging sensitive data in span attributes
- Use service accounts for cloud provider authentication

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.