<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\LiteralExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ValueExpression;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ValidationWalker implements ValidationWalkerInterface
{
    /**
     * @var ExecutionContextInterface
     */
    protected $validationContext;

    /**
     * {@inheritdoc}
     */
    public function walkLiteral(LiteralExpression $expression)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparison(string $operator, ValueExpression $expression)
    {
        if ($expression instanceof LiteralExpression) {
            $this->walkLiteral($expression);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkAll()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkOrder(string $field, string $direction)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkNot(ExpressionInterface $expression)
    {
        $expression->dispatch($this);
    }

    /**
     * {@inheritdoc}
     */
    public function walkAnd(array $arguments)
    {
        foreach ($arguments as $expression) {
            $expression->dispatch($this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkOr(array $arguments)
    {
        foreach ($arguments as $expression) {
            $expression->dispatch($this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkEntry(string $key, ExpressionInterface $expression)
    {
        $expression->dispatch($this);
    }

    /**
     * {@inheritdoc}
     */
    public function setValidationContext(ExecutionContextInterface $context): void
    {
        $this->validationContext = $context;
    }

    protected function addViolation(string $message, array $parameters = []): void
    {
        $this->validationContext
            ->buildViolation($message, $parameters)
            ->addViolation()
        ;
    }
}
