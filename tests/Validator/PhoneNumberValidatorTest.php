<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Validator;

use Fazland\ApiPlatformBundle\Validator\PhoneNumber as PhoneNumberConstraint;
use Fazland\ApiPlatformBundle\Validator\PhoneNumberValidator;
use libphonenumber\PhoneNumber;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;

class PhoneNumberValidatorTest extends TestCase
{
    use ValidatorTestTrait;

    /**
     * {@inheritdoc}
     */
    public function createValidator(): ConstraintValidatorInterface
    {
        return new PhoneNumberValidator();
    }

    /**
     * {@inheritdoc}
     */
    public function createConstraint(array $options = []): Constraint
    {
        return new PhoneNumberConstraint($options);
    }

    public function testValidateShouldAddViolationOnInvalidString(): void
    {
        $value = 'i_am_not_a_valid_phone_number';

        $this->context
            ->addViolation(
                'message',
                ['{{ type }}' => 'fixed_line', '{{ value }}' => $value]
            )
            ->shouldBeCalled()
        ;

        $this->validator->validate($value, $this->createConstraint([
            'message' => 'message',
            'type' => PhoneNumberConstraint::FIXED_LINE,
        ]));
    }

    public function testValidateShouldAddViolationOnInvalidNumber(): void
    {
        $number = $this->prophesize(PhoneNumber::class);

        // Value + is returned from PhoneNumberUtil::format on this PhoneNumber instance.
        $this->context
            ->addViolation(
                'message',
                ['{{ type }}' => 'any', '{{ value }}' => '+']
            )
            ->shouldBeCalled()
        ;

        $this->validator->validate($number->reveal(), $this->createConstraint([
            'message' => 'message',
            'type' => PhoneNumberConstraint::ANY,
        ]));
    }

    public function testValidateShouldNotAddViolationOnValidPhoneNumber(): void
    {
        $this->context->addViolation(Argument::cetera())->shouldNotBeCalled();

        $this->validator->validate('+393331232567', $this->createConstraint());
    }
}
