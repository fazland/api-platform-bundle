<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Form\DataTransformer\Money;

use Fazland\ApiPlatformBundle\Form\DataTransformer\Money\MoneyTransformer;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class MoneyTransformerTest extends TestCase
{
    /**
     * @var MoneyTransformer
     */
    private $transformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->transformer = new MoneyTransformer();
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

    public function provideNonMoneyValues(): iterable
    {
        yield [0.23];
        yield [47];
        yield [47];
        yield [['foobar']];
        yield [new \stdClass()];
        yield ['string'];
    }

    /**
     * @dataProvider provideNonMoneyValues
     */
    public function testTransformShouldThrowOnNonMoneyValues($value): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected Money\\Money');
        $this->transformer->transform($value);
    }

    public function testTransformShouldWork(): void
    {
        $expected = [
            'amount' => '50000',
            'currency' => 'EUR',
        ];
        self::assertEquals($expected, $this->transformer->transform(Money::EUR('50000')));
    }

    /**
     * @dataProvider provideEmptyValues
     */
    public function testReverseTransformShouldReturnNullOnNullValues(?string $value): void
    {
        self::assertNull($this->transformer->reverseTransform($value));
    }

    public function testReverseTransformShouldThrowOnInvalidArray(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Amount must be numeric');
        $this->transformer->reverseTransform(['amount' => 'i am not numeric', 'currency' => 'currency']);
    }

    public function provideNonArrayNorNumericValue(): iterable
    {
        yield [['foobar']];
        yield [new \stdClass()];
        yield ['string'];
    }

    /**
     * @dataProvider provideNonArrayNorNumericValue
     */
    public function testReverseTransformShouldThrowOnNonArrayNorNumericValue($value): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Value must be numeric or an array with amount and currency keys set');
        $this->transformer->reverseTransform($value);
    }

    public function provideValidReverseTransformValues(): iterable
    {
        yield [['amount' => '50000', 'currency' => 'EUR']];
        yield [Money::EUR('50000')];
    }

    /**
     * @dataProvider provideValidReverseTransformValues
     */
    public function testReverseTransformShouldWork($value): void
    {
        self::assertEquals(Money::EUR('50000'), $this->transformer->reverseTransform($value));
    }
}
