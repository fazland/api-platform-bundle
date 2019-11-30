<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Validator;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

trait ValidatorTestTrait
{
    /**
     * @var ExecutionContextInterface|ObjectProphecy
     */
    private $context;

    /**
     * @var ConstraintValidatorInterface
     */
    private $validator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->context = $this->prophesize(ExecutionContextInterface::class);
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context->reveal());
    }

    public function emptyOrNull(): iterable
    {
        yield [null];
        yield [''];
    }

    /**
     * @dataProvider emptyOrNull
     *
     * @param string|null $value
     */
    public function testValidateShouldNotActOnEmptyOrNullValue(string $value = null): void
    {
        $this->context->addViolation(Argument::cetera())->shouldNotBeCalled();

        $this->validator->validate($value, $this->createConstraint());
    }

    public function testValidateShouldThrowOnInvalidConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $invalidConstraint = $this->prophesize(Constraint::class);
        $this->validator->validate(Argument::any(), $invalidConstraint->reveal());
    }

    public function invalidObjects(): iterable
    {
        yield [[]];
        yield [0];
        yield [0.0];
        yield [new \stdClass()];
    }

    /**
     * @dataProvider invalidObjects
     *
     * @param mixed $value
     */
    public function testValidateShouldThrowOnInvalidValueTypes($value): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate($value, $this->createConstraint());
    }

    /**
     * Creates the validator to be tested.
     *
     * @return ConstraintValidatorInterface
     */
    abstract public function createValidator(): ConstraintValidatorInterface;

    /**
     * Creates the constraint object to be passed to the tested validator.
     *
     * @param array $options
     *
     * @return Constraint
     */
    abstract public function createConstraint(array $options = []): Constraint;
}
