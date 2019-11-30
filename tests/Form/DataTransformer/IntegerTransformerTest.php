<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Form\DataTransformer;

use Fazland\ApiPlatformBundle\Form\DataTransformer\IntegerTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

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
     */
    public function testTransformShouldThrowOnNonNumericStrings($value): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Cannot transform a non-numeric string value to an integer');
        $this->transformer->transform($value);
    }

    /**
     * @dataProvider provideNonNumericValues
     */
    public function testReverseTransformShouldThrowOnNonNumericStrings($value): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Cannot transform a non-numeric string value to an integer');
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
