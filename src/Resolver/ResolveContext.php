<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Resolver;

use Severity\ConfigLoader\Builder\ConfigMap;
use function array_pop;
use function array_push;

class ResolveContext
{
    protected ConfigMap $configMap;

    /**
     * @var string[]
     */
    protected array $currentPath;

    public function __construct(ConfigMap $configMap, array $path = [])
    {
        $this->configMap = $configMap;
        $this->currentPath = $path;
    }

    public function get(string $path, $default = null)
    {
        $config = $this->configMap->getByPath('parameters.' . $path);

        return $config;
    }

    public function exists(string $path): bool
    {
        return $this->configMap->exists($path);
    }

    public function getCurrentPath(): array
    {
        return $this->currentPath;
    }

    public function push($path): void
    {
        array_push($this->currentPath, (string)$path);
    }

    public function pop(): void
    {
        array_pop($this->currentPath);
    }

    public function update(array $path)
    {
        return new static($this->configMap, $path);
    }
}
