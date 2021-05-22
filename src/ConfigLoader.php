<?php declare(strict_types=1);

namespace Severity\ConfigLoader;

use Severity\ConfigLoader\Builder\YamlFileResource;
use Severity\ConfigLoader\Builder\ConfigMap;
use Severity\ConfigLoader\Cache\CacheStrategy;
use Severity\ConfigLoader\Contracts\ConfigurationResource;
use Severity\ConfigLoader\Contracts\ConfigurationMergeStrategy;
use Severity\ConfigLoader\Contracts\ValueResolveStrategy;
use Severity\ConfigLoader\Exceptions\UnsupportedFileTypeException;

class ConfigLoader
{
    protected ConfigurationMergeStrategy $resolveManager;

    protected ValueResolveStrategy $valueResolveStrategy;

    /**
     * @var ConfigurationResource[]
     *
     * @psalm-var list<ConfigurationResource>
     */
    protected array $configFiles = [];

    /**
     * Loader constructor.
     *
     * @param ConfigurationMergeStrategy $resolveManager
     */
    public function __construct(
        ConfigurationMergeStrategy $resolveManager,
        ValueResolveStrategy $valueResolveStrategy
    ) {
        $this->resolveManager = $resolveManager;
        $this->valueResolveStrategy = $valueResolveStrategy;
    }

    /**
     * Adds the given path to the list of configuration files to be loaded.
     *
     * @param string $path
     * @param string $type
     *
     * @throws UnsupportedFileTypeException
     *
     * @return static
     */
    public function loadFile(string $path, string $type): static
    {
        switch ($type) {
            case 'yaml':
            case 'yml':
                $this->configFiles[] = new YamlFileResource($path);
                break;

            default:
                throw new UnsupportedFileTypeException("Type \'$type\' is not supported.");
        }

        return $this;
    }

    public function export(): array
    {
        $configuration = $this->resolveManager->merge($this->configFiles);

        return $this->valueResolveStrategy->resolveValues($configuration);
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
