<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Contracts;

use Severity\ConfigLoader\Resolver\ResolveContext;

interface ResolverInterface
{
    public function translate(string $parameterValue, ResolveContext $context);
}
