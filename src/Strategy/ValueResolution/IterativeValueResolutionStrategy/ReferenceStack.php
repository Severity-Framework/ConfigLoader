<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Strategy\ValueResolution\IterativeValueResolutionStrategy;

use function array_pop;

class ReferenceStack
{
    protected array $stack = [];

    public function push(array &$ref): void
    {
        $this->stack[] = $ref;
    }

    public function pop(): ?array
    {
        $ref = array_pop($this->stack);

        return $ref;
    }
}
