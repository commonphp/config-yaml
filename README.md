# CommonPHP YAML Config Driver

Configuration driver for CommonPHP that encodes and decodes YAML configuration data.

## Requirements

- PHP `^8.5`
- `comphp/config:^0.3`
- `symfony/yaml:^8.0`

## Installation

Once this package is available through your Composer repositories, install it with:

```bash
composer require comphp/config-yaml
```

## Usage

```php
<?php

use CommonPHP\Drivers\Config\YAML\YamlConfigurationDriver;

$driver = new YamlConfigurationDriver();

$config = [
    'app' => 'demo',
    'debug' => true,
    'database' => [
        'host' => 'localhost',
    ],
];

$yaml = $driver->encode($config);
$decoded = $driver->decode($yaml);

$driver->write(__DIR__ . '/config.yaml', $config);
$fromFile = $driver->read(__DIR__ . '/config.yaml');
```

## Format Notes

This driver uses Symfony YAML. YAML mappings decode to arrays, empty YAML decodes to an empty array, and scalar-only YAML is rejected as configuration data.

## Error Handling

Read, write, parse, validation, and unsupported value failures throw CommonPHP config exceptions such as `ConfigReadException`, `ConfigWriteException`, `ConfigValidationException`, or `ConfigException`.

## Documentation

- [Usage](docs/usage.md)
- [Testing](TESTING.md)
- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)

## License

MIT. See [LICENSE.md](LICENSE.md).
