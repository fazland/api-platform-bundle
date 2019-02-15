<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Doctrine\ORM\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Fazland\ApiPlatformBundle\Doctrine\ORM\Type\MoneyCurrencyType;
use Money\Currency;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class MoneyCurrencyTypeTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        if (! Type::hasType(MoneyCurrencyType::NAME)) {
            Type::addType(MoneyCurrencyType::NAME, MoneyCurrencyType::class);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        if (Type::hasType(MoneyCurrencyType::NAME)) {
            Type::overrideType(MoneyCurrencyType::NAME, null);
        }

        $reflection = new \ReflectionClass(Type::class);
        $property = $reflection->getProperty('_typesMap');
        $property->setAccessible(true);

        $value = $property->getValue(null);
        unset($value[MoneyCurrencyType::NAME]);

        $property->setValue(null, $value);
    }

    public function testSQLDeclarationShouldBeCorrect(): void
    {
        $platform = $this->prophesize(AbstractPlatform::class);
        $platform->getVarcharTypeDeclarationSQL(Argument::type('array'))->willReturn('VARCHAR(255)');

        $type = Type::getType(MoneyCurrencyType::NAME);

        self::assertEquals('VARCHAR(255)', $type->getSQLDeclaration([], $platform->reveal()));
    }

    public function testConvertToDatabaseValueShouldHandleNullValues(): void
    {
        $type = Type::getType(MoneyCurrencyType::NAME);
        self::assertNull($type->convertToDatabaseValue(null, $this->prophesize(AbstractPlatform::class)->reveal()));
    }

    /**
     * @expectedException \Doctrine\DBAL\Types\ConversionException
     */
    public function testConvertToDatabaseValueShouldThrowIfNotACurrency(): void
    {
        $type = Type::getType(MoneyCurrencyType::NAME);
        $type->convertToDatabaseValue(new \stdClass(), $this->prophesize(AbstractPlatform::class)->reveal());
    }

    public function testConvertToDatabaseValueShouldReturnCorrectValue(): void
    {
        $type = Type::getType(MoneyCurrencyType::NAME);
        self::assertEquals('EUR', $type->convertToDatabaseValue(new Currency('EUR'), $this->prophesize(AbstractPlatform::class)->reveal()));
    }

    public function testConvertToPHPValueShouldHandleNullValue(): void
    {
        $type = Type::getType(MoneyCurrencyType::NAME);
        self::assertNull($type->convertToPHPValue(null, $this->prophesize(AbstractPlatform::class)->reveal()));
    }

    public function testConvertToPHPValueShouldReturnACurrency(): void
    {
        $type = Type::getType(MoneyCurrencyType::NAME);

        $currency = $type->convertToPHPValue('EUR', $this->prophesize(AbstractPlatform::class)->reveal());
        self::assertEquals(new Currency('EUR'), $currency);
    }
}
