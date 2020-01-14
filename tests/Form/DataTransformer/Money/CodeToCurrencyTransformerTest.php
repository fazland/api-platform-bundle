<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Form\DataTransformer\Money;

use Fazland\ApiPlatformBundle\Form\DataTransformer\Money\CodeToCurrencyTransformer;
use Money\Currency;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CodeToCurrencyTransformerTest extends TestCase
{
    private CodeToCurrencyTransformer $transformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->transformer = new CodeToCurrencyTransformer();
    }

    public function provideEmptyValues(): iterable
    {
        yield [null];
        yield [''];
    }

    /**
     * @dataProvider provideEmptyValues
     */
    public function testTransformShouldReturnNullOnNullValues(?string $value): void
    {
        self::assertNull($this->transformer->transform($value));
    }

    public function provideNonStringValues(): iterable
    {
        yield [0.23];
        yield [47];
        yield [47];
        yield [['foobar']];
        yield [new \stdClass()];
    }

    public function provideNonCurrencyValues(): iterable
    {
        yield from $this->provideNonStringValues();
        yield ['string'];
    }

    /**
     * @dataProvider provideNonCurrencyValues
     */
    public function testTransformShouldThrowOnNonMoneyValues($value): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected Money\\Currency');
        $this->transformer->transform($value);
    }

    public function testTransformShouldWork(): void
    {
        self::assertEquals('EUR', $this->transformer->transform(new Currency('EUR')));
    }

    /**
     * @dataProvider provideEmptyValues
     */
    public function testReverseTransformShouldReturnNullOnNullValues(?string $value): void
    {
        self::assertNull($this->transformer->reverseTransform($value));
    }

    /**
     * @dataProvider provideNonStringValues
     */
    public function testReverseTransformShouldThrowOnNonValidValues($value): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected a string');
        $this->transformer->reverseTransform($value);
    }

    public function provideValidReverseTransformValues(): iterable
    {
        yield ['EUR'];
        yield [new Currency('EUR')];
    }

    /**
     * @dataProvider provideValidReverseTransformValues
     */
    public function testReverseTransformShouldWork($value): void
    {
        self::assertEquals(new Currency('EUR'), $this->transformer->reverseTransform($value));
    }
}
