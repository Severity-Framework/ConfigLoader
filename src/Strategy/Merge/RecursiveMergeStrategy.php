<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Strategy\Merge;

use Severity\ConfigLoader\Contracts\ConfigurationMergeStrategy;
use Severity\ConfigLoader\Exceptions\ConfigLoaderException;
use function array_key_exists;
use function gettype;
use function is_array;

class RecursiveMergeStrategy implements ConfigurationMergeStrategy
{
    /**
     * {@inheritDoc}
     */
    public function merge(array $configFiles): array
    {
        $configuration = [];
        foreach ($configFiles as $configFile) {
            $configuration = $this->deepMerge($configuration, $configFile->fetch());
        }

        return $configuration;
    }

    protected function deepMerge(array $a, array $b, string $path = ''): array
    {
        $target = $a;

        foreach ($b as $key => $value) {
            $path .= ".$key";

            if (is_int($key)) {
                $target[] = $value;
            } elseif (array_key_exists($key, $target) === false) {
                $target[$key] = $value;
            } elseif (is_array($value)) {
                if (is_array($target[$key]) === false) {
                    throw new ConfigLoaderException(sprintf(
                        'Configuration key type mismatch (array => %s) on path "%s".',
                        gettype($target[$key]),
                        $path
                    ));
                }

                $target[$key] = $this->deepMerge($target[$key], $value, $path);
            } else {
                if (is_array($target[$key])) {
                    throw new ConfigLoaderException(sprintf(
                        'Configuration key type mismatch (%s => array) on path "%s".',
                        gettype($value),
                        $path
                    ));
                }

                $target[$key] = $value;
            }
        }

        return $target;
    }
}
