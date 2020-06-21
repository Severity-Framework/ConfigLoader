<?php declare(strict_types=1);

namespace Severity\ConfigLoader;

use Severity\ConfigLoader\Builder\ConfigFile;
use Severity\ConfigLoader\Builder\ConfigMap;
use Severity\ConfigLoader\Cache\CacheStrategy;

class Loader
{
    protected string $cachePath;

    /**
     * @var ResolveManager
     */
    protected ResolveManager $resolveManager;

    /**
     * @var ConfigFile[]
     */
    protected array $configFiles = [];

    /**
     * Loader constructor.
     *
     * @param ResolveManager $resolveManager
     * @param string         $cachePath
     */
    public function __construct(
        ResolveManager $resolveManager,
        string $cachePath
    ) {
        $this->resolveManager = $resolveManager;
        $this->cachePath      = $cachePath;
    }

    /**
     * Adds the given path to the list of configuration files to be loaded.
     *
     * @param ConfigFile $configFile
     *
     * @return void
     */
    public function loadConfig(ConfigFile $configFile): void
    {
        $this->configFiles[] = $configFile;
    }

    public function export(): array
    {
        $cacheConfiguration = new CacheStrategy($this->configFiles, $this->cachePath);

        if ($cacheConfiguration->shouldGenerate() === false) {
            return $cacheConfiguration->fetchCache();
        }

        $config = $this->generate();

        $cacheConfiguration->store($config);

        return $config;
    }

    protected function generate(): array
    {
        $configuration = new ConfigMap();

        foreach ($this->configFiles as $file) {
            $configuration->merge($file);
        }

        $this->resolveManager->resolve($configuration);

        return $configuration->get();
    }
}
