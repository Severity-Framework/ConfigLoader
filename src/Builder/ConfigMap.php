<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Builder;

use Severity\ConfigLoader\Exceptions\InvalidPathSegmentException;
use Severity\ConfigLoader\Exceptions\NotExistingPathSegmentException;
use function array_key_exists;
use function explode;
use function implode;
use function is_array;
use function sprintf;

class ConfigMap
{
    protected array $configuration;

    /**
     * ConfigMap constructor.
     */
    public function __construct()
    {
        $this->configuration = [];
    }

    /**
     * Merges the given array of data with the stored array.
     *
     * @param array $data
     *
     * @return void
     */
    public function merge(array $data): void
    {
        $this->configuration = $this->deepMerge($data, $this->configuration);
    }

    /**
     * Merges the 2 given array together and returns the result.
     *
     * @param array $target
     * @param array $source
     *
     * @return array
     */
    protected function deepMerge(array $target, array $source): array
    {
        foreach ($target as $key => $value) {
            if ($value === null) $value = [];

            if (is_array($value) && array_key_exists($key, $source) && is_array($source[$key])) {
                $merged[$key] = $this->deepMerge($source[$key], $value);
            } else if (is_numeric($key)) {
                if (!in_array($value, $source)) {
                    $source[] = $value;
                }
            } else {
                $source[$key] = $value;
            }
        }

        return $source;
    }

    public function get(): array
    {
        return $this->configuration;
    }

    public function set(array $data): void
    {
        $this->configuration = $data;
    }

    /**
     * Returns a part of the stored data by a given dot notated string.
     *
     * @param string $path
     *
     * @throws InvalidPathSegmentException
     * @throws NotExistingPathSegmentException
     *
     * @return mixed
     */
    public function getByPath(string $path)
    {
        $steps = explode('.', $path);
        $array = $this->configuration;

        $pathTaken = [];

        foreach ($steps as $key) {
            $pathTaken[] = $key;

            if (is_array($array) === false) {
                throw new InvalidPathSegmentException(sprintf('Parameter "%s" is not an array!', implode('.', $pathTaken)));
            }

            if (array_key_exists($key, $array) === false) {
                throw new NotExistingPathSegmentException(sprintf('Parameter "%s" does not exist!', implode('.', $pathTaken)));
            }

            $array = $array[$key];
        }

        return $array;
    }

    /**
     * Returns whether the given dot notated path exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function exists(string $path): bool
    {
        $steps = explode('.', $path);
        $array = $this->configuration;

        foreach ($steps as $key) {
            if (is_array($array) === false || array_key_exists($key, $array) === false) {
                return false;
            }

            $array = $array[$key];
        }

        return $array;
    }
}
