<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Contracts;

interface ConfigurationResource
{
    /**
     * @return array
     */
    public function fetch(): array;
}
