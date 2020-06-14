<?php

namespace Severity\ConfigLoader\Tests\Unit\Cache;

use DateTime;
use ReflectionException;
use RuntimeException;
use Severity\ConfigLoader\Builder\ConfigFile;
use Severity\ConfigLoader\Cache\CacheLoader;
use Severity\ConfigLoader\Tests\Utility\Contracts\ConfigLoaderTestCase;
use Severity\ConfigLoader\Tests\Utility\Traits\VisibilityHelper;
use function crc32;
use function file_exists;
use function file_put_contents;
use function is_readable;
use function is_writable;
use function mkdir;
use function sprintf;
use function substr;
use function touch;
use function umask;
use function var_dump;

/**
 * Class CacheConfigurationTest
 *
 * @covers \Severity\ConfigLoader\Cache\CacheLoader
 */
class CacheLoaderTest extends ConfigLoaderTestCase
{
    use VisibilityHelper;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        umask(0000);

        $cachePath = self::getCachePath('');

        if (is_dir($cachePath) === false) {
            mkdir($cachePath, 0777);
        } else {
            if (is_writable($cachePath) === false) {
                throw new RuntimeException(sprintf('Path "%s" is not writable!', $cachePath));
            }

            if (is_readable($cachePath) === false) {
                throw new RuntimeException(sprintf('Path "%s" is not readable!', $cachePath));
            }

            if (file_exists($cachePath) === false) {
                throw new RuntimeException(sprintf('Path "%s" already exists, but it has to be a directory!', $cachePath));
            }

        }
    }

    /**
     * Tests {@see CacheLoader::__construct()} with empty file list.
     *
     * @throws ReflectionException
     *
     * @return void
     */
    public function testConstructWithEmpty(): void
    {
        $config = new CacheLoader([], '');

        $property = $this->getProperty($config, 'files');

        $this->assertEquals([], $property);
    }

    /**
     * Tests {@see CacheLoader::__construct()} with non-empty file list.
     *
     * @throws ReflectionException
     *
     * @return void
     */
    public function testConstructWithNonEmpty(): void
    {
        $configFileMock = $this->mockConfigFileWithPath('SomePath');

        $config = new CacheLoader([
            $configFileMock
        ], '');

        $property = $this->getProperty($config, 'files');

        $this->assertCount(1, $property);
        $this->assertSame($configFileMock, $property[0]);
    }

    public function testShouldGenerateWithNoGenerateCase(): void
    {
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock A">
        $configContentA  = [
            'parameters' => [
                'foo' => 'baz'
            ]
        ];
        $configFilePathA = self::getFixturePath('Cache/CacheConfigurator/NoGeneration/example_a.yaml');

        $configFileMockA = $this->createMock(ConfigFile::class);
        $configFileMockA->method('getPath')
                        ->willReturn($configFilePathA);
        $configFileMockA->method('fetch')
                        ->willReturn($configContentA);
        $configFileMTimeA = new DateTime('-2 hours');
        touch($configFilePathA, $configFileMTimeA->getTimestamp());
        //</editor-fold>
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock B">
        $configContentB  = [
            'parameters' => [
                'bar' => 'foo'
            ],
            'services' => [

            ]
        ];
        $configFilePathB = self::getFixturePath('Cache/CacheConfigurator/NoGeneration/example_b.yaml');

        $configFileMockB = $this->createMock(ConfigFile::class);
        $configFileMockB->method('getPath')
                        ->willReturn($configFilePathB);
        $configFileMockB->method('fetch')
                        ->willReturn($configContentB);
        $configFileMTimeB = new DateTime('-1 hours -31 minutes');
        touch($configFilePathB, $configFileMTimeB->getTimestamp());
        //</editor-fold>

        //<editor-fold defaultstate="collapsed" desc="Mock cache file">
        // @todo: introduce a cache key generation policy class
        $cacheFileKey = substr(crc32($configFilePathA . $configFilePathB), 0, 16);

        // @todo: would it be faster as plain lines?
        $cacheContent = "<?php return [
            '{$configFileMockA->getPath()}',
            '{$configFileMockB->getPath()}'
        ];";

        // Create cache file
        file_put_contents($this->getCachePath("{$cacheFileKey}.cache.properties"), $cacheContent);
        //</editor-fold>
        touch($this->getCachePath("{$cacheFileKey}.cache"));

        $config = new CacheLoader([$configFileMockA, $configFileMockB], $this->getCachePath(''));

        $this->assertFalse($config->shouldGenerate());
    }

    public function testShouldGenerateWithGenerateCase(): void
    {
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock A">
        $configContentA  = [
            'parameters' => [
                'foo' => 'baz'
            ]
        ];
        $configFilePathA = self::getFixturePath('Cache/CacheConfigurator/NoGeneration/example_a.yaml');

        $configFileMockA = $this->createMock(ConfigFile::class);
        $configFileMockA->method('getPath')
                        ->willReturn($configFilePathA);
        $configFileMockA->method('fetch')
                        ->willReturn($configContentA);
        $configFileMTimeA = new DateTime('-4 seconds');
        touch($configFilePathA, $configFileMTimeA->getTimestamp());
        //</editor-fold>
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock B">
        $configContentB  = [
            'parameters' => [
                'bar' => 'foo'
            ],
            'services' => [

            ]
        ];
        $configFilePathB = self::getFixturePath('Cache/CacheConfigurator/NoGeneration/example_b.yaml');

        $configFileMockB = $this->createMock(ConfigFile::class);
        $configFileMockB->method('getPath')
                        ->willReturn($configFilePathB);
        $configFileMockB->method('fetch')
                        ->willReturn($configContentB);
        $configFileMTimeB = new DateTime('-1 minutes');
        touch($configFilePathB, $configFileMTimeB->getTimestamp());
        //</editor-fold>

        //<editor-fold defaultstate="collapsed" desc="Mock cache file">
        // @todo: introduce a cache key generation policy class
        $cacheFileKey = substr(crc32($configFilePathA . $configFilePathB), 0, 16);

        // @todo: would it be faster as plain lines?
        $cacheContent = "<?php return [
            '{$configFileMockA->getPath()}',
            '{$configFileMockB->getPath()}'
        ];";

        // Create cache file
        file_put_contents($this->getCachePath("{$cacheFileKey}.cache.properties"), $cacheContent);
        //</editor-fold>
        touch($this->getCachePath("{$cacheFileKey}.cache"), (new DateTime('-1hours -31 minutes'))->getTimestamp());

        $config = new CacheLoader([$configFileMockA, $configFileMockB], $this->getCachePath(''));
        $this->assertTrue($config->shouldGenerate());
    }

    public function testFetchCache(): void
    {
        $config = $this->getMockBuilder(CacheLoader::class)
                       ->onlyMethods(['']);
    }

    public function testStore(): void
    {

    }
}
