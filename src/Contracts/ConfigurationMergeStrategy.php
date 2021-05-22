<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Contracts;

interface ConfigurationMergeStrategy
{
    /**
     * @param ConfigurationResource[] $configFiles
     *
     * @psalm-param list<ConfigurationResource> $configFiles
     *
     * @return array
     */
    public function merge(array $configFiles): array;
}
