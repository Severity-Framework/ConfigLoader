<?php declare(strict_types=1);

namespace Severity\ConfigLoader;

use InvalidArgumentException;
use Severity\ConfigLoader\Builder\ConfigFile;
use Severity\ConfigLoader\Builder\ConfigMap;
use Severity\ConfigLoader\Cache\CacheLoader;
use Severity\ConfigLoader\Resolver\ParameterResolver;
use function var_dump;

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
     * @param string $cachePath The given folder is gonna be used as a cache folder.
     */
    public function __construct(string $cachePath)
    {
        if (is_dir($cachePath) === false) {
            throw new InvalidArgumentException(sprintf('Given cache path "%s" does not exist!', $cachePath));
        }
        if (is_writable($cachePath) === false) {
            throw new InvalidArgumentException(sprintf('Given cache path "%s" is not writable!', $cachePath));
        }

        $this->cachePath = rtrim($cachePath, '\\') . '\\';

        $this->resolveManager = $this->configureResolver();
    }

    /**
     * Sets up default resolvers.
     *
     * @return ResolveManager
     */
    private function configureResolver(): ResolveManager
    {
        $resolver = new ResolveManager();

        $resolver->pushResolver(new ParameterResolver());

        return $resolver;
    }

    /**
     * Adds the given path to the list of configuration files to be loaded.
     *
     * @param string $path
     *
     * @return void
     */
    public function loadConfig(string $path): void
    {
        $this->configFiles[] = new ConfigFile($path);
    }

    /**
     *
     *
     * @return mixed
     */
    public function export(): array
    {
        $cacheConfiguration = new CacheLoader($this->configFiles, $this->cachePath);

        if ($cacheConfiguration->shouldGenerate() === false) {
            return $cacheConfiguration->fetchCache();
        }

        $config = $this->generate();

        $cacheConfiguration->store($config);

        return $config;
    }

    /**
     * Decides whether, for the given list of configuration files a new cache should file be generated.
     *
     * @return bool
     */
    protected function shouldGenerate(): bool
    {
        return true;
    }

    /**
     *
     *
     * @return mixed[]
     */
    protected function returnFromCache(): array
    {

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
