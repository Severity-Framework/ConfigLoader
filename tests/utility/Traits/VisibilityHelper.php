<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Tests\Utility\Traits;

use ReflectionClass;
use ReflectionException;

trait VisibilityHelper
{
    /**
     * @param object $obj
     * @param string $propertyName
     * @param mixed  $data
     *
     * @throws ReflectionException
     *
     * @return void
     */
    protected function setProperty($obj, string $propertyName, $data): void
    {
        $class = new ReflectionClass($obj);

        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);

        $property->setValue($obj, $data);
    }

    /**
     * @param object $obj
     * @param string $propertyName
     *
     * @throws ReflectionException
     *
     * @return mixed
     */
    protected function getProperty($obj, string $propertyName)
    {
        $class = new ReflectionClass($obj);

        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($obj);
    }
}