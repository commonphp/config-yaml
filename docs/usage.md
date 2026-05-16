# Usage

`comphp/config-yaml` provides `CommonPHP\Drivers\Config\YAML\YamlConfigurationDriver` for YAML configuration files.

## Encode and Decode

```php
use CommonPHP\Drivers\Config\YAML\YamlConfigurationDriver;

$driver = new YamlConfigurationDriver();

$config = [
    'name' => 'demo',
    'database' => [
        'host' => 'localhost',
    ],
];

$data = $driver->encode($config);
$decoded = $driver->decode($data);
```

## Read and Write

```php
$driver->write(__DIR__ . '/config.yaml', $config);
$config = $driver->read(__DIR__ . '/config.yaml');
```

## Notes

This driver uses Symfony YAML. Empty YAML decodes to an empty array. Scalar-only YAML is not accepted as a configuration array.

Failures throw CommonPHP config exceptions instead of returning `false`.
