<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Doctrine\ORM\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Fazland\ApiPlatformBundle\Doctrine\ORM\Type\PhoneNumberType;
use libphonenumber\PhoneNumber;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class PhoneNumberTypeTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        if (! Type::hasType(PhoneNumberType::NAME)) {
            Type::addType(PhoneNumberType::NAME, PhoneNumberType::class);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        if (Type::hasType(PhoneNumberType::NAME)) {
            Type::overrideType(PhoneNumberType::NAME, null);
        }

        $reflection = new \ReflectionClass(Type::class);
        $property = $reflection->getProperty('_typesMap');
        $property->setAccessible(true);

        $value = $property->getValue(null);
        unset($value[PhoneNumberType::NAME]);

        $property->setValue(null, $value);
    }

    public function testSQLDeclarationShouldBeCorrect(): void
    {
        $platform = $this->prophesize(AbstractPlatform::class);
        $platform->getVarcharTypeDeclarationSQL(Argument::type('array'))->willReturn('VARCHAR(36)');

        $type = Type::getType(PhoneNumberType::NAME);

        self::assertEquals('VARCHAR(36)', $type->getSQLDeclaration([], $platform->reveal()));
    }

    public function testConvertToDatabaseValueShouldReturnNullIfNullValue(): void
    {
        $platform = $this->prophesize(AbstractPlatform::class);
        $type = Type::getType(PhoneNumberType::NAME);

        self::assertNull($type->convertToDatabaseValue(null, $platform->reveal()));
    }

    /**
     * @expectedException \Doctrine\DBAL\Types\ConversionException
     */
    public function testConvertToDatabaseValueShouldThrowExceptionGivenInvalidValue(): void
    {
        $platform = $this->prophesize(AbstractPlatform::class);
        $type = Type::getType(PhoneNumberType::NAME);

        $type->convertToDatabaseValue(new \stdClass(), $platform->reveal());
    }

    public function testConvertToDatabaseValueShouldReturnStringGivenCorrectValue(): void
    {
        $platform = $this->prophesize(AbstractPlatform::class);
        $type = Type::getType(PhoneNumberType::NAME);

        $phoneNumber = new PhoneNumber();
        $phoneNumber
            ->setCountryCode('+39')
            ->setNationalNumber('3470340971');

        $converted = $type->convertToDatabaseValue($phoneNumber, $platform->reveal());

        self::assertIsString($converted);
        self::assertEquals('+393470340971', $converted);
    }

    public function testConvertToPHPValueShouldReturnNullGivenNull(): void
    {
        $platform = $this->prophesize(AbstractPlatform::class);
        $type = Type::getType(PhoneNumberType::NAME);

        self::assertNull($type->convertToPHPValue(null, $platform->reveal()));
    }

    public function testConvertToPHPValueShouldReturnSelfGivenPhoneNumber(): void
    {
        $platform = $this->prophesize(AbstractPlatform::class);
        $type = Type::getType(PhoneNumberType::NAME);

        $phoneNumber = new PhoneNumber();
        self::assertEquals($phoneNumber, $type->convertToPHPValue($phoneNumber, $platform->reveal()));
    }

    /**
     * @expectedException \Doctrine\DBAL\Types\ConversionException
     */
    public function testConvertToPHPValueShouldThrowExceptionGivenWrongPhoneNumber(): void
    {
        $platform = $this->prophesize(AbstractPlatform::class);
        $type = Type::getType(PhoneNumberType::NAME);

        $type->convertToPHPValue('f00-b4r-b4z', $platform->reveal());
    }

    public function testConvertToPHPValueShouldReturnConvertedPhoneNumberGivenCorrectValue(): void
    {
        $platform = $this->prophesize(AbstractPlatform::class);
        $type = Type::getType(PhoneNumberType::NAME);

        /** @var PhoneNumber $phoneNumber */
        $phoneNumber = $type->convertToPHPValue('+393470340971', $platform->reveal());

        self::assertInstanceOf(PhoneNumber::class, $phoneNumber);
        self::assertEquals('+39', $phoneNumber->getCountryCode());
        self::assertEquals('3470340971', $phoneNumber->getNationalNumber());
    }

    public function testGetNameShouldReturnExactName(): void
    {
        $type = Type::getType('phone_number');

        self::assertEquals('phone_number', $type->getName());
    }

    public function testRequiresSQLCommentHintShouldReturnTrue(): void
    {
        $platform = $this->prophesize(AbstractPlatform::class);
        $type = Type::getType(PhoneNumberType::NAME);

        self::assertTrue($type->requiresSQLCommentHint($platform->reveal()));
    }
}
