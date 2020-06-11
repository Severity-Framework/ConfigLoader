<?php declare(strict_types=1);

namespace Severity\ConfigLoader;

use InvalidArgumentException;
use Severity\ConfigLoader\Builder\ConfigFile;
use Severity\ConfigLoader\Builder\ConfigMap;
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

    private function configureResolver(): ResolveManager
    {
        $resolver = new ResolveManager();

        $resolver->pushResolver(new ParameterResolver());

        return $resolver;
    }

    public function loadConfig(string $path): void
    {
        $this->configFiles[] = new ConfigFile($path);
    }

    public function export(): array
    {
        if ($this->shouldGenerate() === false) {
            return $this->returnFromCache();
        }

        $config = $this->generate();

//        $this->storeConfig($config);

        return $config;
    }

    protected function shouldGenerate(): bool
    {
        return true;
    }

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
