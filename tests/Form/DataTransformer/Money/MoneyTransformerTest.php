<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Form\DataTransformer\Money;

use Fazland\ApiPlatformBundle\Form\DataTransformer\Money\MoneyTransformer;
use Money\Money;
use PHPUnit\Framework\TestCase;

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
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Expected Money\Money
     */
    public function testTransformShouldThrowOnNonMoneyValues($value): void
    {
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

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Amount must be numeric
     */
    public function testReverseTransformShouldThrowOnInvalidArray(): void
    {
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
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Value must be numeric or an array with amount and currency keys set
     */
    public function testReverseTransformShouldThrowOnNonArrayNorNumericValue($value): void
    {
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
