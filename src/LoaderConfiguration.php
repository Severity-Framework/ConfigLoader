<?php declare(strict_types=1);

namespace Severity\ConfigLoader;

use function rtrim;
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
