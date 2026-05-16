<?php

declare(strict_types=1);

namespace CommonPHP\Drivers\Config\YAML;

use CommonPHP\Config\Contracts\AbstractConfigDriver;
use CommonPHP\Config\Exceptions\ConfigException;
use CommonPHP\Config\Exceptions\ConfigValidationException;
use Symfony\Component\Yaml\Exception\DumpException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Throwable;

final class YamlConfigurationDriver extends AbstractConfigDriver
{
    public function validate(string $data): bool
    {
        try {
            $this->decode($data);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function encode(array $config): string
    {
        $this->validateConfigValue($config);

        try {
            return Yaml::dump(
                $config,
                4,
                2
            );
        } catch (DumpException $e) {
            throw new ConfigException('Could not encode YAML configuration data.', $e->getCode(), $e);
        }
    }

    public function decode(string $data): array
    {
        if (trim($data) === '') {
            return [];
        }

        try {
            $decoded = Yaml::parse(
                $data,
                Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE
            );
        } catch (ParseException $e) {
            throw new ConfigValidationException('Invalid YAML configuration data.', $e->getCode(), $e);
        }

        if ($decoded === null) {
            return [];
        }

        if (!is_array($decoded)) {
            throw new ConfigValidationException('YAML configuration data must decode to an array.');
        }

        return $decoded;
    }

    private function validateConfigValue(mixed $value): void
    {
        if (
            is_string($value)
            || is_int($value)
            || is_float($value)
            || is_bool($value)
            || $value === null
        ) {
            return;
        }

        if (is_array($value)) {
            foreach ($value as $childValue) {
                $this->validateConfigValue($childValue);
            }

            return;
        }

        throw new ConfigValidationException(
            'Unsupported YAML configuration value type: ' . get_debug_type($value)
        );
    }
}