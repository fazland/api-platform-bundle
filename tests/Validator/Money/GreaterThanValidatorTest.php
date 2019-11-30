<?php declare(strict_types=1);

namespace Money\Tests\Validator;

use Fazland\ApiPlatformBundle\Tests\Validator\AbstractComparisonValidatorTestCase;
use Fazland\ApiPlatformBundle\Tests\Validator\ComparisonTest_Class;
use Fazland\ApiPlatformBundle\Validator\Money\GreaterThan;
use Fazland\ApiPlatformBundle\Validator\Money\GreaterThanValidator;
use Money\Money;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class GreaterThanValidatorTest extends AbstractComparisonValidatorTestCase
{
    /**
     * {@inheritdoc}
     */
    public function provideValidComparisons(): array
    {
        return [
            [Money::EUR('1000'), 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function provideValidComparisonsToPropertyPath(): array
    {
        return [
            [Money::EUR('1000'), Money::EUR('1000'), Money::EUR('500'), Money::EUR('500'), Money::class],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function provideInvalidComparisons(): array
    {
        return [
            [Money::EUR('500'), 1000],
            [Money::EUR('1000'), 1000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function provideAllInvalidComparisons(): array
    {
        return [
            [Money::EUR('500'), '500', 1000, '1000', Money::class],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createConstraint(array $options = null): Constraint
    {
        try {
            return new GreaterThan($options);
        } catch (\InvalidArgumentException $e) {
            self::markTestSkipped('Invalid money value');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidator(): ConstraintValidator
    {
        return new GreaterThanValidator();
    }

    /**
     * @dataProvider provideAllValidComparisons
     *
     * @param mixed $dirtyValue
     * @param mixed $comparisonValue
     */
    public function testValidComparisonToValue($dirtyValue, $comparisonValue): void
    {
        $constraint = $this->createConstraint([
            'value' => $comparisonValue,
            'currency' => 'EUR',
        ]);

        $this->validator->validate($dirtyValue, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider provideValidComparisonsToPropertyPath
     */
    public function testValidComparisonToPropertyPath($comparedValue): void
    {
        $constraint = $this->createConstraint(['propertyPath' => 'value']);

        $object = new ComparisonTest_Class(Money::EUR('5'));

        $this->setObject($object);

        $this->validator->validate($comparedValue, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider provideValidComparisonsToPropertyPath
     */
    public function testValidComparisonToPropertyPathOnArray($comparedValue): void
    {
        $constraint = $this->createConstraint(['propertyPath' => 'root[value]']);

        $this->setObject((object) ['root' => ['value' => Money::EUR('5')]]);

        $this->validator->validate($comparedValue, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider provideAllInvalidComparisons
     *
     * @param mixed  $dirtyValue
     * @param mixed  $dirtyValueAsString
     * @param mixed  $comparedValue
     * @param mixed  $comparedValueString
     * @param string $comparedValueType
     */
    public function testInvalidComparisonToValue(
        $dirtyValue,
        $dirtyValueAsString,
        $comparedValue,
        $comparedValueString,
        $comparedValueType
    ): void {
        $constraint = $this->createConstraint([
            'value' => $comparedValue,
            'currency' => 'EUR',
        ]);
        $constraint->message = 'Constraint Message';

        $this->validator->validate($dirtyValue, $constraint);

        $this->buildViolation('Constraint Message')
            ->setParameter('{{ value }}', 'object')
            ->setParameter('{{ compared_value }}', 'object')
            ->setParameter('{{ compared_value_type }}', $comparedValueType)
            ->assertRaised()
        ;
    }
}
