<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Strategy\ValueResolution;

use RuntimeException;
use Severity\ConfigLoader\Contracts\ValueResolveStrategy;
use Severity\ConfigLoader\Exceptions\InvalidPathSegmentException;
use Severity\ConfigLoader\Exceptions\NoPathGivenException;
use Severity\ConfigLoader\Exceptions\NotExistingPathSegmentException;
use Severity\ConfigLoader\Exceptions\RecursiveValueReferenceException;
use Severity\ConfigLoader\Strategy\ValueResolution\IterativeValueResolutionStrategy\ReferenceStack;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_pop;
use function array_shift;
use function array_unshift;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function preg_match_all;
use function sprintf;
use function strlen;
use function substr;
use function substr_replace;

/**
 * Class IterativeValueResolutionStrategy
 *
 * @psalm-type MatchShape = array{non-empty-string, int}
 */
class IterativeValueResolutionStrategy implements ValueResolveStrategy
{
    protected const DEFAULT_LEVEL_DELIMITER = '>>';

    public function __construct(protected string $levelDelimiter = self::DEFAULT_LEVEL_DELIMITER) { }

    public function resolveValues(array $configuration): array
    {
        $referenceStack = new ReferenceStack();

        $current = &$configuration;

        $path = [];
        $keys = array_keys($current);

        $context = new ResolvingContext($configuration, $this->levelDelimiter);

        while (($key = array_shift($keys)) !== null) {
            if (array_key_exists($key, $current) === false) {
                $parentKey = array_pop($path);

                $parent = $referenceStack->pop();
                $parent[$parentKey] = $current;

                $current = $parent;

                array_unshift($keys, $key);

                continue;
            }

            if (is_array($current[$key])) {
                array_unshift($keys, ... array_keys($current[$key]));

                $referenceStack->push($current);

                $current = &$current[$key];
                $path[]  = $key;

                continue;
            }

            $result = $this->processNode($key, $current[$key], $path, $context);
            if ($result !== null) {
                [$processedKey, $processedValue] = $result;

                if ($processedKey !== $key) {
                    unset($current[$key]);

                    $key = $processedKey;
                }

                $current[$key] = $processedValue;
            }
        }

        return $current;
    }

    /**
     * @param mixed    $key
     * @param mixed    $value
     * @param string[] $path
     * @param ResolvingContext $context
     *
     * @return array|null
     *
     * @psalm-return array{array-key, mixed}|null
     */
    protected function processNode(int|string $key, mixed $value, array $path, ResolvingContext $context): ?array
    {
        $newKey = null;
        if (is_string($key)) {
            $newKey = $this->process($key, $path, $context);
            if ($newKey !==  null) {
                $key = $newKey;
            }
        }

        $newValue = null;
        if ($value !== null) {
            $newValue = $this->process($value, $path, $context);
            if ($newValue !== null) {
                $value = $newValue;
            }
        }

        if ($newKey || $newValue) {
            return [$key, $value];
        }

        return null;
    }

    protected function process(int|string $key, array $path, ResolvingContext $context): mixed
    {
        $matches = $this->getMatches($key);

        if ($matches === null) {
            return null;
        }

        $diff = 0;

        /**
         * @var string $match
         * @var int    $pos
         */
        foreach ($matches as [$match, $pos]) {
            $newPath   = $path;
            $newPath[] = $key;

            $resolvedMatch = (string) $this->resolveValue($match, $context, [implode($this->levelDelimiter, $newPath)]);

            $lengthOfTheMatch = strlen($match);

            $key  = substr_replace($key, $resolvedMatch, $pos - $diff, $lengthOfTheMatch);
            $diff = $lengthOfTheMatch - strlen($resolvedMatch);
        }

        return $key;
    }

    /**
     * @param mixed $key
     *
     * @return array|null
     *
     * @psalm-return non-empty-list<MatchShape>
     */
    protected function getMatches(mixed $key): ?array
    {
        if (is_string($key) === false) {
            return null;
        }

        return (int) preg_match_all("/((?<!\\\)%(?:[\w()\->_.]|\\\%)+%)/", $key, $matches, PREG_OFFSET_CAPTURE) > 0 ?
            $matches[1] : null;
    }

    /**
     * @param string           $ref
     * @param ResolvingContext $context
     * @param string[]         $path
     *
     * @throws NotExistingPathSegmentException
     * @throws RecursiveValueReferenceException
     * @throws InvalidPathSegmentException
     * @throws NoPathGivenException
     *
     * @return mixed
     */
    protected function resolveValue(string $ref, ResolvingContext $context, array $path): mixed
    {
        $variableString = substr($ref, 1, strlen($ref) - 2);

        try {
            $value = $context->get($variableString);
        } catch (NotExistingPathSegmentException $e) {
            try {
                $parent = $context->getParent($variableString);

                if (is_array($parent) === false) {
                    throw new RuntimeException(sprintf(
                        // Theoretically this can never happen since we already reach at this point
                        'Parent of path "%s" should point to an array node.', $variableString
                    ));
                }

                $isRef = false;
                foreach (array_keys($parent) as $sibling) {
                    if ($this->getMatches($sibling) !== null) {
                        $isRef = true;
                        break;
                    }
                }

                $message = $e->getMessage();
                if ($isRef) {
                    $message = sprintf(
                        '%s. Referencing a dynamically resolved value is not supported and not advised.', $e->getMessage()
                    );
                }

                throw new NotExistingPathSegmentException($message);
            } catch (NoPathGivenException $_) {
                // In case the resolvable reference is on the root level. The parent is absent.
            }

            throw $e;
        }

        // Look for dynamic reference
        if (($refs = $this->getMatches($value)) !== null) {
            if (in_array($ref, $path)) {
                throw new RecursiveValueReferenceException(sprintf(
                    'Recursive value reference encountered: \'%s\'',
                    implode(" => ", array_map(fn(string $key): string => "$key", $path))
                ));
            }

            foreach ($refs as $ref) {
                $newPath = $path;
                $newPath[] = $ref;

                $this->resolveValue($ref, $context, $newPath);
            }
        }

        return $value;
    }
}
