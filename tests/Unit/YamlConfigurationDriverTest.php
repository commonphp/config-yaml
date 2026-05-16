<?php

declare(strict_types=1);

namespace CommonPHP\Drivers\Config\YAML\Tests\Unit;

use CommonPHP\Config\Exceptions\ConfigReadException;
use CommonPHP\Config\Exceptions\ConfigValidationException;
use CommonPHP\Config\Exceptions\ConfigWriteException;
use CommonPHP\Drivers\Config\YAML\YamlConfigurationDriver;
use PHPUnit\Framework\TestCase;

final class YamlConfigurationDriverTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryDirectory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'comphp_config_yaml_'
            . bin2hex(random_bytes(8));

        mkdir($this->temporaryDirectory, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->temporaryDirectory);

        parent::tearDown();
    }

    public function testValidateAcceptsValidDataAndRejectsInvalidData(): void
    {
        $driver = $this->driver();

        self::assertTrue($driver->validate((string) file_get_contents($this->fixturePath('valid.yaml'))));
        self::assertFalse($driver->validate('name: [demo'));
    }

    public function testEncodeCreatesYamlThatCanBeDecoded(): void
    {
        $config = [
            'name' => 'demo',
            'enabled' => true,
            'retries' => 3,
            'database' => [
                'host' => 'localhost',
            ],
        ];

        $driver = $this->driver();

        self::assertSame($config, $driver->decode($driver->encode($config)));
    }

    public function testEncodeRejectsUnsupportedValues(): void
    {
        $this->expectException(ConfigValidationException::class);

        $this->driver()->encode(['object' => new \stdClass()]);
    }

    public function testDecodeReturnsArrayForValidYamlMapping(): void
    {
        self::assertSame(
            [
                'name' => 'demo',
                'enabled' => true,
                'retries' => 3,
                'database' => [
                    'host' => 'localhost',
                ],
            ],
            $this->driver()->decode((string) file_get_contents($this->fixturePath('valid.yaml')))
        );
    }

    public function testDecodeReturnsEmptyArrayForEmptyYaml(): void
    {
        self::assertSame([], $this->driver()->decode(''));
        self::assertSame([], $this->driver()->decode(" \n"));
    }

    public function testDecodeThrowsForInvalidYaml(): void
    {
        $this->expectException(ConfigValidationException::class);

        $this->driver()->decode('name: [demo');
    }

    public function testDecodeRejectsScalarOnlyYaml(): void
    {
        $this->expectException(ConfigValidationException::class);

        $this->driver()->decode('true');
    }

    public function testReadReturnsConfigArrayFromValidFile(): void
    {
        self::assertSame(
            [
                'name' => 'demo',
                'enabled' => true,
                'retries' => 3,
                'database' => [
                    'host' => 'localhost',
                ],
            ],
            $this->driver()->read($this->fixturePath('valid.yaml'))
        );
    }

    public function testReadThrowsWhenFileDoesNotExist(): void
    {
        $this->expectException(ConfigReadException::class);

        $this->driver()->read($this->tempPath('missing.yaml'));
    }

    public function testReadThrowsWhenPathIsNotAFile(): void
    {
        $directory = $this->tempPath('directory');
        mkdir($directory);

        $this->expectException(ConfigReadException::class);

        $this->driver()->read($directory);
    }

    public function testReadThrowsWhenFileIsNotReadable(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            self::markTestSkipped('File readability permissions are not enforced consistently on Windows.');
        }

        $path = $this->tempPath('unreadable.yaml');
        file_put_contents($path, 'name: demo');
        chmod($path, 0000);

        try {
            if (is_readable($path)) {
                self::markTestSkipped('The current runtime can still read the chmod-protected file.');
            }

            $this->expectException(ConfigReadException::class);

            $this->driver()->read($path);
        } finally {
            chmod($path, 0600);
        }
    }

    public function testWriteCreatesReadableConfigFile(): void
    {
        $path = $this->tempPath('config.yaml');
        $config = [
            'name' => 'demo',
            'enabled' => true,
            'database' => [
                'host' => 'localhost',
            ],
        ];

        $driver = $this->driver();
        $driver->write($path, $config);

        self::assertFileExists($path);
        self::assertSame($config, $driver->read($path));
    }

    public function testWriteThrowsWhenParentDirectoryDoesNotExist(): void
    {
        $this->expectException(ConfigWriteException::class);

        $this->driver()->write($this->tempPath('missing/config.yaml'), ['name' => 'demo']);
    }

    public function testWriteThrowsWhenTargetFileIsNotWritable(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            self::markTestSkipped('File writability permissions are not enforced consistently on Windows.');
        }

        $path = $this->tempPath('readonly.yaml');
        file_put_contents($path, 'name: old');
        chmod($path, 0444);

        try {
            if (is_writable($path)) {
                self::markTestSkipped('The current runtime can still write to the chmod-protected file.');
            }

            $this->expectException(ConfigWriteException::class);

            $this->driver()->write($path, ['name' => 'demo']);
        } finally {
            chmod($path, 0600);
        }
    }

    private function driver(): YamlConfigurationDriver
    {
        return new YamlConfigurationDriver();
    }

    private function fixturePath(string $name): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . $name;
    }

    private function tempPath(string $name): string
    {
        return $this->temporaryDirectory . DIRECTORY_SEPARATOR . $name;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
            } else {
                @chmod($path, 0600);
                @unlink($path);
            }
        }

        @chmod($directory, 0700);
        @rmdir($directory);
    }
}
