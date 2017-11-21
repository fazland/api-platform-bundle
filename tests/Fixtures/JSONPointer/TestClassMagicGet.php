<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Tests\Fixtures\JSONPointer;

class TestClassMagicGet
{
    private $magicProperty;

    public $publicProperty;

    public function __construct($value)
    {
        $this->magicProperty = $value;
    }

    public function __set($property, $value)
    {
        if ('magicProperty' === $property) {
            $this->magicProperty = $value;
        }
    }

    public function __get($property)
    {
        if ('magicProperty' === $property) {
            return $this->magicProperty;
        }

        if ('constantMagicProperty' === $property) {
            return 'constant value';
        }

        if ('throwing' === $property) {
            throw new \Exception('Non-existent property');
        }
    }
}
