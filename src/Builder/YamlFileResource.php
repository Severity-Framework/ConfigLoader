<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Builder;

use InvalidArgumentException;
use Severity\ConfigLoader\Contracts\ConfigurationResource;
use function yaml_parse_file;

class YamlFileResource implements ConfigurationResource
{
    protected string $path;

    /**
     * ConfigFile constructor.
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        if (file_exists($path) === false) {
            throw new InvalidArgumentException(sprintf('File on path "%s" does not exist!', $path));
        }

        if (is_readable($path) === false) {
            throw new InvalidArgumentException(sprintf('The file "%s" is not readable!', $path));
        }

        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function fetch(): array
    {
        return yaml_parse_file($this->path);
    }
}
