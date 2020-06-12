<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Builder;

use InvalidArgumentException;
use function yaml_parse_file;

class ConfigFile
{
    protected string $path;

    protected string $type;

    /**
     * ConfigFile constructor.
     *
     * @param string $path
     * @param string $type
     */
    public function __construct(string $path, string $type = 'yaml')
    {
        if (file_exists($path) === false) {
            throw new InvalidArgumentException(sprintf('File on path "%s" does not exist!', $path));
        }

        if (is_readable($path) === false) {
            throw new InvalidArgumentException(sprintf('The file "%s" is not readable!', $path));
        }

        $this->path = $path;
        $this->type = $type;
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
