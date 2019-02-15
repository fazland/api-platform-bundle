<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Form\DataTransformer;

use Fazland\ApiPlatformBundle\Form\DataTransformer\BooleanTransformer;
use PHPUnit\Framework\TestCase;

class BooleanTransformerTest extends TestCase
{
    /**
     * @var BooleanTransformer
     */
    private $transformer;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->transformer = new BooleanTransformer();
    }

    public function testTransformShouldReturnNullOnNull(): void
    {
        self::assertNull($this->transformer->transform(null));
    }

    public function provideNonBooleansValues(): iterable
    {
        yield ['i am not a phone number'];
        yield [new \stdClass()];
        yield [1];
        yield [1.0];
        yield [[]];
    }

    /**
     * @dataProvider provideNonBooleansValues
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Expected a bool
     */
    public function testTransformShouldThrowOnNonBooleans($value): void
    {
        $this->transformer->transform($value);
    }

    public function provideBooleanValues(): iterable
    {
        yield [true];
        yield [false];
    }

    /**
     * @dataProvider provideBooleanValues
     */
    public function testTransformShouldReturnBoolOnBooleans(bool $value): void
    {
        self::assertEquals($value, $this->transformer->transform($value));
    }

    public function testReverseTransformShouldReturnNullOnNull(): void
    {
        self::assertNull($this->transformer->reverseTransform(null));
    }

    /**
     * @dataProvider provideBooleanValues
     */
    public function testReverseTransformShouldReturnBoolOnBooleans(bool $value): void
    {
        self::assertEquals($value, $this->transformer->reverseTransform($value));
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Expected a scalar value, object passed
     */
    public function testReverseTransformShouldThrowOnObjects(): void
    {
        $this->transformer->reverseTransform(new \stdClass());
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Expected a scalar value, array passed
     */
    public function testReverseTransformShouldThrowOnArrays(): void
    {
        $this->transformer->reverseTransform([]);
    }

    public function provideFalseValues(): iterable
    {
        foreach (BooleanTransformer::FALSE_VALUES as $falseValue) {
            yield [$falseValue];
        }
    }

    /**
     * @dataProvider provideFalseValues
     */
    public function testReverseTransformShouldReturnFalseOnFalseValues(string $value): void
    {
        self::assertFalse($this->transformer->reverseTransform($value));
    }

    public function provideTrueValues(): iterable
    {
        foreach (BooleanTransformer::TRUE_VALUES as $trueValue) {
            yield [$trueValue];
        }
    }

    /**
     * @dataProvider provideTrueValues
     */
    public function testReverseTransformShouldReturnTrueOnTrueValues(string $value): void
    {
        self::assertTrue($this->transformer->reverseTransform($value));
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Cannot transform value "i_am_not_a_false_value_nor_true_value"
     */
    public function testReverseTransformShouldThrowOnInvalidStrings(): void
    {
        $this->transformer->reverseTransform('i_am_not_a_false_value_nor_true_value');
    }
}
