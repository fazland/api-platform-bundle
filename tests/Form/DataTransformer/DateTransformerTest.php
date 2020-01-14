<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Form\DataTransformer;

use Cake\Chronos\Chronos;
use Fazland\ApiPlatformBundle\Form\DataTransformer\DateTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DateTransformerTest extends TestCase
{
    private DateTransformer $transformer;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->transformer = new DateTransformer();
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

    public function provideNonDateTimeInterfaceValues(): iterable
    {
        yield ['i am not a phone number'];
        yield [1];
        yield [1.0];
        yield [new \stdClass()];
        yield [[]];
    }

    /**
     * @dataProvider provideNonDateTimeInterfaceValues
     */
    public function testTransformShouldThrowOnNonDateTimeInterfaceValue($value): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected a DateTimeInterface instance');
        $this->transformer->transform($value);
    }

    public function testTransformShouldFormatDate(): void
    {
        $now = Chronos::now();

        self::assertEquals($now->toDateString(), $this->transformer->transform($now));
    }

    /**
     * @dataProvider provideEmptyValues
     */
    public function testReverseTransformShouldReturnNullOnEmptyValue(?string $value): void
    {
        self::assertNull($this->transformer->reverseTransform($value));
    }

    public function provideInvalidReverseTransformValues(): iterable
    {
        foreach (\iterator_to_array($this->provideNonDateTimeInterfaceValues()) as $value) {
            yield [$value, \is_string($value) ? 'Unexpected date format' : 'Expected a string'];
        }
    }

    /**
     * @dataProvider provideInvalidReverseTransformValues
     */
    public function testReverseTransformShouldThrowOnInvalidValue($value, string $expectedExceptionMessage): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->transformer->reverseTransform($value);
    }

    public function testReverseTransformShouldReturnValueOnDateTimeInterfaceValue(): void
    {
        $now = Chronos::now();

        self::assertEquals($now, $this->transformer->reverseTransform($now));
    }

    public function provideDateAsStringValues(): iterable
    {
        $now = Chronos::now()->startOfDay();
        yield [$now, $now->toDateString()];
        yield [$now, $now->format('d/m/Y')];
    }

    /**
     * @dataProvider provideDateAsStringValues
     */
    public function testReverseTransformShouldAcceptDateAsString(\DateTimeInterface $expected, string $dateAsString): void
    {
        self::assertEquals($expected, $this->transformer->reverseTransform($dateAsString));
    }
}
