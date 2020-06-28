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
        $this->configMap   = $configMap;
        $this->currentPath = $path;
    }

    public function get(string $path, string $delimiter)
    {
        return $this->configMap->getByPath("parameters{$delimiter}" . $path, $delimiter);
    }

    public function exists(string $path, string $delimiter): bool
    {
        return $this->configMap->exists("parameters{$delimiter}" . $path);
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
}
