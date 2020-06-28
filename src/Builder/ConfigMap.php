<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Builder;

use Severity\ConfigLoader\Exceptions\ConfigMergeException;
use Severity\ConfigLoader\Exceptions\InvalidPathSegmentException;
use Severity\ConfigLoader\Exceptions\NotExistingPathSegmentException;
use function array_key_exists;
use function explode;
use function implode;
use function is_array;
use function is_int;
use function sprintf;

/**
 * Class ConfigMap
 *
 * @internal
 */
class ConfigMap
{
    protected array $configuration;

    protected string $fileInMerge = '';

    /**
     * ConfigMap constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->configuration = $data;
    }

    /**
     * Merges the given array of data with the stored array.
     *
     * @param ConfigFile $file
     *
     * @throws ConfigMergeException
     *
     * @return void
     */
    public function merge(ConfigFile $file): void
    {
        $this->fileInMerge = $file->getPath();

        $this->configuration = $this->deepMerge($this->configuration, $file->fetch());

        $this->fileInMerge = '';
    }

    /**
     * Deeeeeply-merges the 2 given array together and returns the result.
     *
     * @param array    $a
     * @param array    $b
     * @param string[] $path
     *
     * @throws ConfigMergeException
     *
     * @return array
     */
    protected function deepMerge(array $a, array $b, array $path = []): array
    {
        $target = $a;
        foreach ($b as $key => $val) {
            $path[] = $key;

            if (is_int($key)) {
                $target[] = $val;
            } elseif (array_key_exists($key, $target) === false) {
                $target[$key] = $val;
            } elseif (is_array($val)) {
                if (is_array($target[$key]) === false) {
                    throw new ConfigMergeException(sprintf('Error during merging config file: %s! The existing key "%s" is not an array!', $this->fileInMerge, implode('.', $path)));
                }

                $target[$key] = $this->deepMerge($target[$key], $val, $path);
            } else {
                if (is_array($a[$key])) {
                    throw new ConfigMergeException(sprintf('Error during merging config file: %s! The existing key "%s" is an array!', $this->fileInMerge, implode('.', $path)));
                }

                $target[$key] = $val;
            }
        }

        return $target;
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
     * @param string $delimiter
     *
     * @throws InvalidPathSegmentException
     * @throws NotExistingPathSegmentException
     *
     * @return mixed
     */
    public function getByPath(string $path, string $delimiter = '.')
    {
        $steps = explode($delimiter, $path);
        $array = $this->configuration;

        $pathTaken = [];

        foreach ($steps as $key) {
            $pathTaken[] = $key;

            if (is_array($array) === false) {
                throw new InvalidPathSegmentException(sprintf('Parameter "%s" is not an array!', implode($delimiter, $pathTaken)));
            }

            if (array_key_exists($key, $array) === false) {
                throw new NotExistingPathSegmentException(sprintf('Parameter "%s" does not exist!', implode($delimiter, $pathTaken)));
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

        return true;
    }
}
