<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Contracts;

interface ValueResolveStrategy
{
    public function resolveValues(array $configuration): array;
}
