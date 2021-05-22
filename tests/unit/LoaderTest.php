<?php

namespace Severity\ConfigLoader\Tests\Unit;

use Severity\ConfigLoader\Builder\YamlFileResource;
use Severity\ConfigLoader\ConfigLoader;
use PHPUnit\Framework\TestCase;
use Severity\ConfigLoader\Strategy\ValueResolution\IterativeValueResolutionStrategy;
use Severity\ConfigLoader\Strategy\Merge\RecursiveMergeStrategy;
use Severity\ConfigLoader\ResolveManager;
use Severity\ConfigLoader\Resolver\ParameterResolver;
use Severity\ConfigLoader\Tests\Utility\Contracts\ConfigLoaderTestCase;
use Severity\ConfigLoader\Tests\Utility\Traits\VisibilityHelper;
use function array_shift;
use function array_unshift;
use function var_dump;

/**
 * Class LoaderTest
 *
 * @covers \Severity\ConfigLoader\ConfigLoader
 */
class LoaderTest extends ConfigLoaderTestCase
{
    use VisibilityHelper;

    protected ResolveManager $resolveManager;

    protected function setUp(): void
    {
        $this->resolveManager = $this->createMock(ResolveManager::class);
    }

    public function testComplete(): void
    {
        $loader = new ConfigLoader(
            new RecursiveMergeStrategy(),
            new IterativeValueResolutionStrategy()
        );

        $loader->loadFile($this->getFixturePath('Loader/Complete/config1.yaml'), 'yaml');
        // $loader->loadFile($this->getFixturePath('Loader/Complete/config2.yaml'), 'yaml');
        // $loader->loadFile($this->getFixturePath('Loader/Complete/config3.yaml'), 'yaml');
        $config = $loader->export();

        dd($config);

        $fileLoader = new ConfigFileFinder($loader);
        $fileLoader->import($this->getFixturePath('/config/**/cache.yaml'));

        // Simply get the output
        // ---------------------
        // Or use caching
        $dumper             = new Dumper();
        $configCacheService = new ConfigCache($loader);



       

        $loader->loadConfig(new YamlFileResource($this->getFixturePath('Loader/Complete/config1.yaml')));
        $loader->loadConfig(new YamlFileResource($this->getFixturePath('Loader/Complete/config2.yaml')));
        $loader->loadConfig(new YamlFileResource($this->getFixturePath('Loader/Complete/config3.yaml')));

        var_dump($loader->export());
    }
}
