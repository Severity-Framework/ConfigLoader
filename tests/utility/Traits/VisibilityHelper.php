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
     * @return void
     */
    protected function setProperty($obj, string $propertyName, $data): void
    {
        /** @noinspection PhpUnhandledExceptionInspection The class does exist */
        $class = new ReflectionClass($obj);

        /** @noinspection PhpUnhandledExceptionInspection That property does exist */
        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);

        $property->setValue($obj, $data);
    }

    /**
     * @param object $obj
     * @param string $propertyName
     *
     * @return mixed
     */
    protected function getProperty($obj, string $propertyName)
    {
        /** @noinspection PhpUnhandledExceptionInspection The class does exist */
        $class = new ReflectionClass($obj);

        /** @noinspection PhpUnhandledExceptionInspection That property does exist */
        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($obj);
    }
}