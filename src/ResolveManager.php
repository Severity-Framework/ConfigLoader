<?php declare(strict_types=1);

namespace Severity\ConfigLoader;

use Severity\ConfigLoader\Builder\ConfigMap;
use Severity\ConfigLoader\Contracts\ResolverInterface;
use Severity\ConfigLoader\Resolver\ResolveContext;
use function is_array;
use function is_bool;
use function is_float;
use function is_integer;
use function is_scalar;

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

    public function resolve(ConfigMap $configMap): void
    {
        $context = new ResolveContext($configMap);

        $resolvedConfig = $this->doResolve($configMap->get(), $context);

        $configMap->set($resolvedConfig);
    }

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

    protected function resolveValue($value, ResolveContext $context)
    {
        if (is_int($value) || is_bool($value) || is_float($value) || $value === null) {
            return $value;
        }

        return $this->resolveStringValue($value, $context);
    }

    protected function resolveStringValue(string $value, ResolveContext $context)
    {
        foreach ($this->configResolvers as $resolver) {
            $resolved = $resolver->translate($value, $context);

            if ($resolved !== $value) {
                return $resolved;
            }
        }

        return $value;
    }
}
