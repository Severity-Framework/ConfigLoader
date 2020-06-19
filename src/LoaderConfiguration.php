<?php declare(strict_types=1);

namespace Severity\ConfigLoader;

use InvalidArgumentException;
use function is_dir;
use function is_writable;
use function rtrim;
use function sprintf;
use const DIRECTORY_SEPARATOR;

class LoaderConfiguration
{
    protected bool $devMode;

    protected ?string $cachePath = null;

    /**
     * LoaderConfiguration constructor.
     *
     * @param bool        $devMode
     * @param string|null $cachePath @todo replace with psr-6
     */
    public function __construct(bool $devMode, ?string $cachePath)
    {
        $this->devMode = $devMode;

        if ($cachePath !== null) {
            $this->cachePath = rtrim($cachePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }

        if (is_dir($cachePath) === false) {
            throw new InvalidArgumentException(sprintf('Given cache path "%s" does not exist!', $cachePath));
        }
        if (is_writable($cachePath) === false) {
            throw new InvalidArgumentException(sprintf('Given cache path "%s" is not writable!', $cachePath));
        }

        $this->cachePath = rtrim($cachePath, '\\') . '\\';

    }

    /**
     * @return bool
     */
    public function isDevMode(): bool
    {
        return $this->devMode;
    }

    /**
     * @return string|null
     */
    public function getCachePath(): ?string
    {
        return $this->cachePath;
    }

    public function getCacheFilePath(string $path): string
    {
        if ($this->cachePath) {
            return $this->cachePath . $path;
        }

        return '';
    }

    public function isCacheEnabled(): bool
    {
        return $this->cachePath !== null;
    }
}
