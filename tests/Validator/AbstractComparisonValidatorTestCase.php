<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Validator;

use Symfony\Component\Intl\Util\IntlTestHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\AbstractComparison;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ComparisonTest_Class
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    public function getValue()
    {
        return $this->value;
    }
}

abstract class AbstractComparisonValidatorTestCase extends ConstraintValidatorTestCase
{
    protected static function addPhp5Dot5Comparisons(array $comparisons): array
    {
        $result = $comparisons;

        // Duplicate all tests involving DateTime objects to be tested with
        // DateTimeImmutable objects as well
        foreach ($comparisons as $comparison) {
            $add = false;

            foreach ($comparison as $i => $value) {
                if ($value instanceof \DateTime) {
                    $comparison[$i] = new \DateTimeImmutable(
                        $value->format('Y-m-d H:i:s.u e'),
                        $value->getTimezone()
                    );
                    $add = true;
                } elseif ('DateTime' === $value) {
                    $comparison[$i] = 'DateTimeImmutable';
                    $add = true;
                }
            }

            if ($add) {
                $result[] = $comparison;
            }
        }

        return $result;
    }

    public function provideInvalidConstraintOptions(): array
    {
        return [
            [null],
            [[]],
        ];
    }

    /**
     * @dataProvider provideInvalidConstraintOptions
     */
    public function testThrowsConstraintExceptionIfNoValueOrPropertyPath($options): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('requires either the "value" or "propertyPath" option to be set.');
        $this->createConstraint($options);
    }

    public function testThrowsConstraintExceptionIfBothValueAndPropertyPath(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('requires only one of the "value" or "propertyPath" options to be set, not both.');
        $this->createConstraint(([
            'value' => 'value',
            'propertyPath' => 'propertyPath',
        ]));
    }

    /**
     * @dataProvider provideAllValidComparisons
     *
     * @param mixed $dirtyValue
     * @param mixed $comparisonValue
     */
    public function testValidComparisonToValue($dirtyValue, $comparisonValue): void
    {
        $constraint = $this->createConstraint(['value' => $comparisonValue]);

        $this->validator->validate($dirtyValue, $constraint);

        $this->assertNoViolation();
    }

    public function provideAllValidComparisons(): array
    {
        // The provider runs before setUp(), so we need to manually fix
        // the default timezone
        $this->setDefaultTimezone('UTC');

        $comparisons = self::addPhp5Dot5Comparisons($this->provideValidComparisons());

        $this->restoreDefaultTimezone();

        return $comparisons;
    }

    /**
     * @dataProvider provideValidComparisonsToPropertyPath
     */
    public function testValidComparisonToPropertyPath($comparedValue): void
    {
        $constraint = $this->createConstraint(['propertyPath' => 'value']);

        $object = new ComparisonTest_Class(5);

        $this->setObject($object);

        $this->validator->validate($comparedValue, $constraint);

        $this->assertNoViolation();
    }

    public function testNoViolationOnNullObjectWithPropertyPath(): void
    {
        $constraint = $this->createConstraint(['propertyPath' => 'propertyPath']);

        $this->setObject(null);

        $this->validator->validate('some data', $constraint);

        $this->assertNoViolation();
    }

    public function testInvalidValuePath(): void
    {
        $constraint = $this->createConstraint(['propertyPath' => 'foo']);

        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage(\sprintf('Invalid property path "foo" provided to "%s" constraint', \get_class($constraint)));

        $object = new ComparisonTest_Class(5);

        $this->setObject($object);

        $this->validator->validate(5, $constraint);
    }

    abstract public function provideValidComparisons(): array;

    abstract public function provideValidComparisonsToPropertyPath(): array;

    /**
     * @dataProvider provideAllInvalidComparisons
     *
     * @param mixed  $dirtyValue
     * @param mixed  $dirtyValueAsString
     * @param mixed  $comparedValue
     * @param mixed  $comparedValueString
     * @param string $comparedValueType
     */
    public function testInvalidComparisonToValue($dirtyValue, $dirtyValueAsString, $comparedValue, $comparedValueString, $comparedValueType): void
    {
        // Conversion of dates to string differs between ICU versions
        // Make sure we have the correct version loaded
        if ($dirtyValue instanceof \DateTime || $dirtyValue instanceof \DateTimeInterface) {
            IntlTestHelper::requireIntl($this, '57.1');
        }

        $constraint = $this->createConstraint(['value' => $comparedValue]);
        $constraint->message = 'Constraint Message';

        $this->validator->validate($dirtyValue, $constraint);

        $this->buildViolation('Constraint Message')
             ->setParameter('{{ value }}', $dirtyValueAsString)
             ->setParameter('{{ compared_value }}', $comparedValueString)
             ->setParameter('{{ compared_value_type }}', $comparedValueType)
             ->setCode($this->getErrorCode())
             ->assertRaised();
    }

    /**
     * @dataProvider throwsOnInvalidStringDatesProvider
     */
    public function testThrowsOnInvalidStringDates(AbstractComparison $constraint, $expectedMessage, $value): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->validator->validate($value, $constraint);
    }

    public function throwsOnInvalidStringDatesProvider(): array
    {
        $constraint = $this->createConstraint([
            'value' => 'foo',
        ]);

        $constraintClass = \get_class($constraint);

        return [
            [$constraint, \sprintf('The compared value "foo" could not be converted to a "DateTimeImmutable" instance in the "%s" constraint.', $constraintClass), new \DateTimeImmutable()],
            [$constraint, \sprintf('The compared value "foo" could not be converted to a "DateTime" instance in the "%s" constraint.', $constraintClass), new \DateTime()],
        ];
    }

    public function provideAllInvalidComparisons(): array
    {
        // The provider runs before setUp(), so we need to manually fix
        // the default timezone
        $this->setDefaultTimezone('UTC');

        $comparisons = self::addPhp5Dot5Comparisons($this->provideInvalidComparisons());

        $this->restoreDefaultTimezone();

        return $comparisons;
    }

    abstract public function provideInvalidComparisons(): array;

    /**
     * @param array|null $options Options for the constraint
     */
    abstract protected function createConstraint(array $options = null): Constraint;
}
