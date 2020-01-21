<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Form\DataTransformer;

use Fazland\ApiPlatformBundle\QueryLanguage\Exception\SyntaxError;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Grammar\Grammar;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class StringToExpresionTransformer implements DataTransformerInterface
{
    private Grammar $grammar;

    public function __construct(?Grammar $grammar = null)
    {
        $this->grammar = $grammar ?? Grammar::getInstance();
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value): string
    {
        if (null === $value) {
            return '';
        }

        if (! $value instanceof ExpressionInterface) {
            throw new TransformationFailedException('Expected '.ExpressionInterface::class);
        }

        return (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value): ?ExpressionInterface
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        if (! \is_string($value)) {
            throw new TransformationFailedException('Expected string');
        }

        try {
            return $this->grammar->parse($value);
        } catch (SyntaxError $e) {
            throw new TransformationFailedException('Invalid value', 0, $e);
        }
    }
}
