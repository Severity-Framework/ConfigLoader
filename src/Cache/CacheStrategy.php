<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Cache;

use BadMethodCallException;
use RuntimeException;
use Severity\ConfigLoader\Builder\ConfigFile;
use function array_map;
use function array_reduce;
use function crc32;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function is_readable;
use function serialize;
use function sprintf;
use function substr;
use function unserialize;

/**
 * Class CacheLoader
 *
 * @internal
 */
class CacheStrategy
{
    protected const EXT_CACHE = '.cache';
    protected const EXT_META  = '.cache.properties';

    /**
     * @var ConfigFile[]
     */
    protected array $files;

    protected string $fullPath;

    protected ?bool $valid = null;

    /**
     * CacheConfiguration constructor.
     *
     * @param ConfigFile[] $files
     * @param string       $cachePath @todo: replace with LoaderConfiguration object
     */
    public function __construct(array $files, string $cachePath)
    {
        $this->files    = $files;
        // @todo find a little bit more elegant way
        $this->fullPath = $cachePath . $this->generateCacheKey();
    }

    public function shouldGenerate(): bool
    {
        if ($this->valid === null) {
            $this->valid = false;
            if ($this->cacheFileExists()) {
                $this->valid = $this->isDeprecated() === false;
            }
        }

        return $this->valid === false;
    }

    protected function cacheFileExists(): bool
    {
        return file_exists($this->fullPath . self::EXT_CACHE) &&
               file_exists($this->fullPath . self::EXT_META);
    }

    protected function generateCacheKey(): string
    {
        $base = array_reduce($this->files, function(string $carry, ConfigFile $file): string {
            return $carry . $file->getPath();
        }, '');

        return substr((string) crc32($base), 0, 16);
    }

    protected function isDeprecated(): bool
    {
        $cacheCreateDate = filemtime($this->fullPath . self::EXT_CACHE);

        /** @noinspection PhpIncludeInspection Method {@see cacheFileExists()} should be called before. */
        $files = (require $this->fullPath . self::EXT_META);

        foreach ($files as $file) {
            if (filemtime($file) > $cacheCreateDate) return true;
        }

        return false;
    }

    public function fetchCache(): array
    {
        if ($this->valid === false || $this->valid === null) {
            throw new BadMethodCallException('The cache is not valid!');
        }

        $fullPath = $this->fullPath . self::EXT_CACHE;
        if (is_readable($fullPath) === false) {
            throw new RuntimeException(sprintf('Failed to load cache file "%s"! Check permissions!', $fullPath));
        }

        $cacheContent = file_get_contents($this->fullPath . self::EXT_CACHE);

        if ($cacheContent === false) {
            throw new RuntimeException(sprintf('Failed to load cache file "%s"!!', $cacheContent));
        }

        return unserialize($cacheContent);
    }

    public function store(array $config): string
    {
        file_put_contents($this->fullPath . self::EXT_CACHE, serialize($config));
        file_put_contents($this->fullPath . self::EXT_META, $this->generateMeta());

        return $this->fullPath . self::EXT_CACHE;
    }

    protected function generateMeta(): string
    {
        $files = array_map(
            function (ConfigFile $file): string {
                return "'{$file->getPath()}'";
            },
            $this->files
        );

        return '<?php return [' . implode(',', $files) . '];';
    }
}