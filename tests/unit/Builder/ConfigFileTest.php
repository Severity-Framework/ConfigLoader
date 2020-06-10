<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Tests\Builder;

use InvalidArgumentException;
use Severity\ConfigLoader\Builder\ConfigFile;
use Severity\ConfigLoader\Tests\ConfigLoaderTestCase;

/**
 * Class ConfigFileTest
 *
 * @covers \Severity\ConfigLoader\Builder\ConfigFile
 */
class ConfigFileTest extends ConfigLoaderTestCase
{
    /**
     * Tests {@see ConfigFile::__construct()}
     *
     * @return void
     */
    public function testConstructorForNotExistingFile(): void
    {
        $filePath = $this->getFixturePath('not/existing/file.yaml');

        $this->expectException(InvalidArgumentException::class);

        new ConfigFile($filePath);
    }

    /**
     * Tests {@see ConfigFile::fetch()}
     *
     * @return void
     */
    public function testFetchWithValidContent(): void
    {
        $filePath = $this->getFixturePath('Builder/ConfigFile/valid_example1.yaml');

        $configFile = new ConfigFile($filePath);

        $expected = [
            'parameters' => [
                'foo' => 'bar'
            ]
        ];

        $this->assertSame($expected, $configFile->fetch());
    }
}
