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

OpenTelemetry telemetry driver for Prism PHP applications. This package provides distributed tracing capabilities using OpenTelemetry standards.

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
    'enabled' => true,
    'driver' => 'opentelemetry',
    'drivers' => [
        'opentelemetry' => [
            'service_name' => env('OPENTELEMETRY_SERVICE_NAME', 'laravel-app'),
            'service_version' => env('OPENTELEMETRY_SERVICE_VERSION', '1.0.0'),
            'endpoint' => env('OPENTELEMETRY_ENDPOINT', 'http://localhost:4318/v1/traces'),
        ],
    ],
],
```

Configure your environment variables:

```env
# Enable Prism telemetry
PRISM_TELEMETRY_ENABLED=true
PRISM_TELEMETRY_DRIVER=opentelemetry

# OpenTelemetry configuration
OPENTELEMETRY_SERVICE_NAME=my-laravel-app
OPENTELEMETRY_SERVICE_VERSION=1.0.0
OPENTELEMETRY_ENDPOINT=http://localhost:4318/v1/traces
```

## Usage

Once configured, Prism will automatically send telemetry data to your OpenTelemetry collector.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
