<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ValueExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\TreeWalkerInterface;

abstract class LiteralExpression extends ValueExpression
{
    /**
     * {@inheritdoc}
     */
    public function dispatch(TreeWalkerInterface $treeWalker)
    {
        return $treeWalker->walkLiteral($this);
    }

    /**
     * {@inheritdoc}
     */
    public static function create($value): ValueExpression
    {
        if (! \is_string($value)) {
            throw new \TypeError(\sprintf('Argument 1 passed to '.__METHOD__.' must be a string. %s passed', \is_object($value) ? \get_class($value) : \gettype($value)));
        }

        switch (true) {
            case 'true' === $value:
            case 'false' === $value:
                return new BooleanExpression('true' === $value);

            case 'null' === $value:
                return new NullExpression();

            case 1 === \preg_match('/^\d+$/', $value):
                return new IntegerExpression((int) $value);

            case \is_numeric($value):
                return new NumericExpression($value);

            default:
                return new StringExpression($value);
        }
    }
}
