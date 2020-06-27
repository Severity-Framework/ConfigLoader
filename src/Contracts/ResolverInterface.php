<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Contracts;

use Severity\ConfigLoader\Resolver\ResolveContext;

interface ResolverInterface
{
    /**
     * @param string $parameterValue
     * @param ResolveContext $context
     * @return mixed|null
     */
    public function translate(string $parameterValue, ResolveContext $context);
}
