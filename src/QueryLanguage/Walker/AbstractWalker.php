<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Walker;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\LiteralExpression;

abstract class AbstractWalker implements TreeWalkerInterface
{
    /**
     * Field name.
     *
     * @var string
     */
    protected $field;

    public function __construct(string $field)
    {
        $this->field = $field;
    }

    /**
     * {@inheritdoc}
     */
    public function walkLiteral(LiteralExpression $expression)
    {
        return $expression->getValue();
    }
}
