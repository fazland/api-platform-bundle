<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\JSONPointer;

class TestClassMagicGet
{
    /**
     * @var mixed
     */
    private $magicProperty;

    /**
     * @var mixed
     */
    public $publicProperty;

    public function __construct($value)
    {
        $this->magicProperty = $value;
    }

    public function __set(string $property, $value)
    {
        if ('magicProperty' === $property) {
            $this->magicProperty = $value;
        }
    }

    public function __get(string $property)
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
