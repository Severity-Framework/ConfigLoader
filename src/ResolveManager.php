<?php declare(strict_types=1);

namespace Severity\ConfigLoader;

use Severity\ConfigLoader\Builder\ConfigMap;
use Severity\ConfigLoader\Contracts\ResolverInterface;
use Severity\ConfigLoader\Resolver\ResolveContext;
use function is_array;
use function is_string;

class ResolveManager
{
    /**
     * @var ResolverInterface[]
     */
    protected array $configResolvers = [];

    public function pushResolver(ResolverInterface $resolver): void
    {
        $this->configResolvers[] = $resolver;
    }

    /**
     * Resolves the internal references in the given ConfigMap object.
     *
     * @param ConfigMap $configMap
     *
     * @return void
     */
    public function resolve(ConfigMap $configMap): void
    {
        $context = new ResolveContext($configMap);

        $resolvedConfig = $this->doResolve($configMap->get(), $context);

        $configMap->set($resolvedConfig);
    }

    /**
     * Resolves the internal references in the given array.
     *
     * @param mixed[]        $chunk
     * @param ResolveContext $context
     *
     * @return mixed[]
     */
    protected function doResolve(array $chunk, ResolveContext $context): array
    {
        foreach ($chunk as $key => $value) {
            $context->push($key);

            if (is_array($value)) {
                $chunk[$key] = $this->doResolve($chunk[$key], $context);
            } else {
                $chunk[$key] = $this->resolveValue($value, $context);

                $context->pop();
            }
        }

        return $chunk;
    }

    /**
     * Scans the given value and tries to resolve any internal references in it. Returns the resolved value.
     *
     * @param string|float|int|bool|null  $value
     * @param ResolveContext              $context
     *
     * @return string
     */
    protected function resolveValue(mixed $value, ResolveContext $context): mixed
    {
        if (is_string($value)) {
            return $this->resolveStringValue($value, $context);
        }

        return $value;
    }

    /**
     * Passes the given string to all registered resolver. The first of them returns different value than te current
     * value gonna be returned back.
     *
     * @param string         $value
     * @param ResolveContext $context
     *
     * @return string
     */
    protected function resolveStringValue(string $value, ResolveContext $context): string
    {
        foreach ($this->configResolvers as $resolver) {
            $resolved = $resolver->translate($value, $context);

            if ($resolved !== null) {
                return $resolved;
            }
        }

        return $value;
    }
}
