<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Validator;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation\ValidationWalkerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ExpressionValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (! $constraint instanceof Expression) {
            throw new UnexpectedTypeException($constraint, Expression::class);
        }

        if (null === $value) {
            return;
        }

        if (! $value instanceof ExpressionInterface) {
            throw new UnexpectedTypeException($value, ExpressionInterface::class);
        }

        $walker = $constraint->walker;
        if (null === $walker) {
            return;
        }

        if (\ is_callable($walker)) {
            $walker = $walker();
        } elseif (\is_string($walker)) {
            $walker = new $walker();
        }

        if (! $walker instanceof ValidationWalkerInterface) {
            throw new UnexpectedTypeException($walker, ValidationWalkerInterface::class);
        }

        $walker->setValidationContext($this->context);
        $value->dispatch($walker);
    }
}
