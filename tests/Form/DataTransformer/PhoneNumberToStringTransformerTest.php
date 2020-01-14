<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Form\DataTransformer;

use Fazland\ApiPlatformBundle\Form\DataTransformer\PhoneNumberToStringTransformer;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class PhoneNumberToStringTransformerTest extends TestCase
{
    private PhoneNumberToStringTransformer $transformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->transformer = new PhoneNumberToStringTransformer();
    }

    public function testTransformShouldReturnEmptyStringOnNull(): void
    {
        self::assertEquals('', $this->transformer->transform(null));
    }

    public function nonPhoneNumberArguments(): iterable
    {
        yield ['i am not a phone number'];
        yield [new \stdClass()];
        yield [[]];
        yield [123];
        yield [11.123];
        yield ['+393939898231'];
    }

    /**
     * @dataProvider nonPhoneNumberArguments
     */
    public function testTransformShouldThrowOnNonPhoneNumberArgument($argument): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->transformer->transform($argument);
    }

    public function testTransformShouldReturnPhoneNumberFormattedInE164(): void
    {
        $phoneNumber = PhoneNumberUtil::getInstance()->parse('+393334455678');

        self::assertEquals('+393334455678', $this->transformer->transform($phoneNumber));
    }

    public function testReverseTransformShouldReturnNullOnEmptyString(): void
    {
        self::assertEquals(null, $this->transformer->reverseTransform(''));
    }

    public function nonPhoneNumberStringRepresentation(): iterable
    {
        yield ['i am not a phone number'];
        yield [new \stdClass()];
        yield [[]];
        yield [123];
        yield [11.123];
    }

    /**
     * @dataProvider nonPhoneNumberStringRepresentation
     */
    public function testReverseTransformShouldThrowOnNonPhoneNumberStringRepresentation($argument): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->transformer->reverseTransform($argument);
    }

    public function testReverseTransformShouldReturnPhoneNumber(): void
    {
        $phone = '+393939898231';

        $phoneNumber = $this->transformer->reverseTransform($phone);
        self::assertInstanceOf(PhoneNumber::class, $phoneNumber);
        self::assertEquals($phone, PhoneNumberUtil::getInstance()->format($phoneNumber, PhoneNumberFormat::E164));
    }
}
