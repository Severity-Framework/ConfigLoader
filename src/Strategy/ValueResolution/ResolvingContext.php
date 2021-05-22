<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Strategy\ValueResolution;

use Exception;
use InvalidArgumentException;
use Severity\ConfigLoader\Exceptions\InvalidPathSegmentException;
use Severity\ConfigLoader\Exceptions\NoPathGivenException;
use Severity\ConfigLoader\Exceptions\NotExistingPathSegmentException;
use function array_key_exists;
use function array_unshift;
use function explode;
use function implode;
use function is_array;
use function sprintf;

/**
 * Class ResolvingContext
 *
 * @internal
 *
 * @psalm-internal Severity\ConfigLoader
 */
class ResolvingContext
{
    protected const INDEX_PARAMETERS = 'parameters';

    public function __construct(protected array $configuration, protected string $levelDelimiter)
    {
        if (array_key_exists(self::INDEX_PARAMETERS, $configuration) === false) {
            throw new InvalidArgumentException('Property "parameters" does not exist in the given configuration context.');
        }
    }

    /**
     * @param string $path
     *
     * @throws InvalidPathSegmentException
     * @throws NoPathGivenException
     * @throws NotExistingPathSegmentException
     *
     * @return mixed
     */
    public function get(string $path): mixed
    {
        if (trim($path) === '') {
            throw new NoPathGivenException('Argument 1 is an empty path.');
        }

        $steps = explode($this->levelDelimiter, $path);
        $array = $this->configuration[self::INDEX_PARAMETERS];

        $pathTaken = [];

        foreach ($steps as $key) {
            $pathTaken[] = $key;

            if (is_array($array) === false) {
                throw new InvalidPathSegmentException(sprintf(
                    'Parameter "%s" is not an array!', implode($this->levelDelimiter, $pathTaken)
                ));
            }

            if (array_key_exists($key, $array) === false) {
                throw new NotExistingPathSegmentException(sprintf(
                    'Parameter "%s" does not exist!', implode($this->levelDelimiter, $pathTaken)
                ));
            }

            $array = $array[$key];
        }

        return $array;
    }

    /**
     * @param string $path
     *
     * @throws InvalidPathSegmentException
     * @throws NoPathGivenException
     * @throws NotExistingPathSegmentException
     *
     * @return mixed
     */
    public function getParent(string $path): mixed
    {
        $steps = explode($this->levelDelimiter, $path);
        array_pop($steps);

        return $this->get(implode($this->levelDelimiter, $steps));
    }
}
