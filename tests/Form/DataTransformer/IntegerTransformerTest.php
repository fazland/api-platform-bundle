<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Form\DataTransformer;

use Fazland\ApiPlatformBundle\Form\DataTransformer\IntegerTransformer;
use PHPUnit\Framework\TestCase;

class IntegerTransformerTest extends TestCase
{
    /**
     * @var IntegerTransformer
     */
    private $transformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->transformer = new IntegerTransformer();
    }

    public function provideEmptyValues(): iterable
    {
        yield [null];
        yield [''];
    }

    /**
     * @dataProvider provideEmptyValues
     */
    public function testTransformShouldReturnNullOnEmptyValue(?string $value): void
    {
        self::assertNull($this->transformer->transform($value));
    }

    public function provideTransformMethodNames(): iterable
    {
        yield ['transform'];
        yield ['reverseTransform'];
    }

    /**
     * @dataProvider provideEmptyValues
     */
    public function testReverseTransformShouldReturnNullOnEmptyValue(?string $value): void
    {
        self::assertNull($this->transformer->reverseTransform($value));
    }

    /**
     * @dataProvider provideTransformMethodNames
     */
    public function testTransformerShouldReturnIntIfValueIsInt(string $methodName): void
    {
        $value = 42;

        self::assertEquals($value, $this->transformer->$methodName($value));
    }

    public function provideNonNumericValues(): iterable
    {
        yield ['i am not a phone number'];
        yield [new \stdClass()];
        yield [[]];
    }

    /**
     * @dataProvider provideNonNumericValues
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Cannot transform a non-numeric string value to an integer
     */
    public function testTransformShouldThrowOnNonNumericStrings($value): void
    {
        $this->transformer->transform($value);
    }

    /**
     * @dataProvider provideNonNumericValues
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Cannot transform a non-numeric string value to an integer
     */
    public function testReverseTransformShouldThrowOnNonNumericStrings($value): void
    {
        $this->transformer->reverseTransform($value);
    }

    /**
     * @dataProvider provideTransformMethodNames
     */
    public function testTransformerShouldTransformNumericStrings(string $methodName): void
    {
        self::assertEquals(12345, $this->transformer->$methodName('12345'));
    }
}
