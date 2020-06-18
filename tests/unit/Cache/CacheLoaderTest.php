<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Tests\Unit\Cache;

use BadMethodCallException;
use DateTime;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Severity\ConfigLoader\Builder\ConfigFile;
use Severity\ConfigLoader\Cache\CacheLoader;
use Severity\ConfigLoader\Tests\Utility\Contracts\ConfigLoaderTestCase;
use Severity\ConfigLoader\Tests\Utility\Traits\VisibilityHelper;
use function chmod;
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

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        foreach (glob(self::getCachePath('*.*')) as $file) {
            unlink($file);
        }
    }


    /**
     * @param string $path
     * @param array $content
     *
     * @return ConfigFile|MockObject
     */
    protected function mockConfigFile(string $path, array $content): ConfigFile
    {
        $configFileMock = $this->createMock(ConfigFile::class);
        $configFileMock->method('getPath')
                       ->willReturn($path);
        $configFileMock->method('fetch')
                       ->willReturn($content);

        return $configFileMock;
    }

    /**
     * Tests {@see CacheLoader::__construct()} with empty file list.
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

    /**
     * Tests {@see CacheLoader::shouldGenerate()} method.
     *
     * @throws Exception
     *
     * @return void
     */
    public function testShouldGenerateWithNoGenerateCase(): void
    {
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock A">
        $configContentA  = [
            'parameters' => [
                'foo' => 'baz'
            ]
        ];
        $configFilePathA = self::getFixturePath('Cache/CacheLoader/ShouldGenerateWithNoGeneration/example_a.yaml');
        $configFileMockA = $this->mockConfigFile($configFilePathA, $configContentA);

        touch($configFilePathA, (new DateTime('-2 hours'))->getTimestamp());
        //</editor-fold>
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock B">
        $configContentB  = [
            'parameters' => [
                'bar' => 'foo'
            ],
            'services' => [

            ]
        ];
        $configFilePathB = self::getFixturePath('Cache/CacheLoader/ShouldGenerateWithNoGeneration/example_b.yaml');
        $configFileMockB = $this->mockConfigFile($configFilePathB, $configContentB);

        touch($configFilePathB, (new DateTime('-1 hours -31 minutes'))->getTimestamp());
        //</editor-fold>

        //<editor-fold defaultstate="collapsed" desc="Mock cache file">
        // @todo: introduce a cache key generation policy class
        $cacheFileKey = substr((string) crc32($configFilePathA . $configFilePathB), 0, 16);

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

    /**
     * Tests {@see CacheLoader::shouldGenerate()} method.
     *
     * @throws Exception
     *
     * @return void
     */
    public function testShouldGenerateWithGenerateCase(): void
    {
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock A">
        $configContentA  = [
            'parameters' => [
                'foo' => 'baz'
            ]
        ];
        $configFilePathA = self::getFixturePath('Cache/CacheLoader/ShouldGenerateWithGeneration/example_a.yaml');
        $configFileMockA = $this->mockConfigFile($configFilePathA, $configContentA);

        touch($configFilePathA, (new DateTime('-4 seconds'))->getTimestamp());
        //</editor-fold>
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock B">
        $configContentB  = [
            'parameters' => [
                'bar' => 'foo'
            ],
            'services' => [

            ]
        ];
        $configFilePathB = self::getFixturePath('Cache/CacheLoader/ShouldGenerateWithGeneration/example_b.yaml');
        $configFileMockB = $this->mockConfigFile($configFilePathB, $configContentB);

        touch($configFilePathB, (new DateTime('-1 minutes'))->getTimestamp());
        //</editor-fold>

        //<editor-fold defaultstate="collapsed" desc="Mock cache file">
        // @todo: introduce a cache key generation policy class
        $cacheFileKey = substr((string) crc32($configFilePathA . $configFilePathB), 0, 16);

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

    /**
     * Tests {@see CacheLoader::fetchCache()} method.
     *
     * @throws Exception
     *
     * @return void
     */
    public function testFetchCache(): void
    {
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock A">
        $configContentA  = [
            'parameters' => [
                'foo' => 'baz'
            ]
        ];
        $configFilePathA = self::getFixturePath('Cache/CacheLoader/Fetch/example_a.yaml');
        $configFileMockA = $this->mockConfigFile($configFilePathA, $configContentA);

        touch($configFilePathA, (new DateTime('-4 seconds'))->getTimestamp());
        //</editor-fold>
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock B">
        $configContentB  = [
            'parameters' => [
                'bar' => 'foo'
            ],
            'services' => [

            ]
        ];
        $configFilePathB = self::getFixturePath('Cache/CacheLoader/Fetch/example_b.yaml');
        $configFileMockB = $this->mockConfigFile($configFilePathB, $configContentB);

        touch($configFilePathB, (new DateTime('-1 minutes'))->getTimestamp());
        //</editor-fold>

        $cacheFileKey = substr((string) crc32($configFilePathA . $configFilePathB), 0, 16);

        $cacheContent = [
            'parameters' => [
                'param' => 'foo'
            ],
            'services' => [
                'bar' =>' baz'
            ]
        ];

        file_put_contents($this->getCachePath("{$cacheFileKey}.cache"), serialize($cacheContent));

        /** @var CacheLoader|MockObject $config */
        $config = $this->getMockBuilder(CacheLoader::class)
                       ->setConstructorArgs([[$configFileMockA, $configFileMockB], $this->getCachePath('')])
                       ->onlyMethods(['shouldGenerate'])
                       ->getMock();

        $this->setProperty($config, 'valid', true);

        $config->method('shouldGenerate')->willReturn(false);

        $this->assertSame($cacheContent, $config->fetchCache());
    }

    /**
     * Tests {@see CacheLoader::fetchCache()} method.
     *
     * @throws Exception
     *
     * @return void
     */
    public function testFetchCacheWithInvalidCall(): void
    {
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock A">
        $configContentA  = [
            'parameters' => [
                'foo' => 'baz'
            ]
        ];
        $configFilePathA = self::getFixturePath('Cache/CacheLoader/ShouldGenerateWithNoGeneration/example_a.yaml');
        $configFileMockA = $this->mockConfigFile($configFilePathA, $configContentA);

        touch($configFilePathA, (new DateTime('-4 seconds'))->getTimestamp());
        //</editor-fold>
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock B">
        $configContentB  = [
            'parameters' => [
                'bar' => 'foo'
            ],
            'services' => [

            ]
        ];
        $configFilePathB = self::getFixturePath('Cache/CacheLoader/ShouldGenerateWithNoGeneration/example_b.yaml');
        $configFileMockB = $this->mockConfigFile($configFilePathB, $configContentB);

        touch($configFilePathB, (new DateTime('-1 minutes'))->getTimestamp());
        //</editor-fold>

        $config = new CacheLoader([$configFileMockA, $configFileMockB], $this->getCachePath(''));

        $this->expectException(BadMethodCallException::class);
        $config->fetchCache();
    }

    public function testFetchCacheWithNoPermission(): void
    {
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock A">
        $configContentA = [
            'parameters' => [
                'foo' => 'baz'
            ]
        ];
        $configFilePathA = self::getFixturePath('Cache/CacheLoader/ShouldGenerateWithNoGeneration/example_a.yaml');
        $configFileMockA = $this->mockConfigFile($configFilePathA, $configContentA);

        touch($configFilePathA, (new DateTime('-4 seconds'))->getTimestamp());
        //</editor-fold>
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock B">
        $configContentB = [
            'parameters' => [
                'bar' => 'foo'
            ],
            'services' => [

            ]
        ];
        $configFilePathB = self::getFixturePath('Cache/CacheLoader/ShouldGenerateWithNoGeneration/example_b.yaml');
        $configFileMockB = $this->mockConfigFile($configFilePathB, $configContentB);

        touch($configFilePathB, (new DateTime('-1 minutes'))->getTimestamp());
        //</editor-fold>

        $config = new CacheLoader([$configFileMockA, $configFileMockB], $this->getCachePath(''));

        $this->setProperty($config, 'valid', true);

        /** @var string $cachePath */
        $cachePath = $this->getProperty($config, 'fullPath');

        chmod("{$cachePath}.cache", 0000);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/(Check permissions)/');
        $config->fetchCache();
    }

    /**
     * Tests {@see CacheLoader::store()} method.
     *
     * @throws Exception
     *
     * @return void
     */
    public function testStore(): void
    {
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock A">
        $configContentA  = [
            'parameters' => [
                'foo' => 'baz'
            ]
        ];
        $configFilePathA = self::getFixturePath('Cache/CacheConfigurator/ShouldGenerateWithNoGeneration/example_c.yaml');
        $configFileMockA = $this->mockConfigFile($configFilePathA, $configContentA);
        //</editor-fold>
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock B">
        $configContentB  = [
            'parameters' => [
                'bar' => 'foo'
            ],
            'services' => [

            ]
        ];
        $configFilePathB = self::getFixturePath('Cache/CacheConfigurator/ShouldGenerateWithNoGeneration/example_d.yaml');
        $configFileMockB = $this->mockConfigFile($configFilePathB, $configContentB);
        //</editor-fold>

        $newConfiguration = [
            'parameters' => [
                'param' => 'bar',
                'baz'   => 'foo'
            ],
            'services' => [],
        ];

        $loader = new CacheLoader([$configFileMockA, $configFileMockB], $this->getCachePath(''));
        $path = $loader->store($newConfiguration);

        $this->assertFileExists("{$path}");
        $this->assertFileExists("{$path}.properties");

        $this->assertSame($newConfiguration, unserialize(file_get_contents($path)));

        $meta = (require $path . '.properties');

        $this->assertSame([$configFilePathA, $configFilePathB], $meta);
    }
}
