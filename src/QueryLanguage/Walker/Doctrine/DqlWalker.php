<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Walker\Doctrine;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\LiteralExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\StringExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\AbstractWalker;

class DqlWalker extends AbstractWalker
{
    private const COMPARISON_MAP = [
        '=' => Expr\Comparison::EQ,
        '<' => Expr\Comparison::LT,
        '<=' => Expr\Comparison::LTE,
        '>' => Expr\Comparison::GT,
        '>=' => Expr\Comparison::GTE,
        'like' => 'LIKE',
    ];

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    public function __construct(QueryBuilder $queryBuilder, string $field)
    {
        parent::__construct($field);

        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @inheritDoc
     */
    public function walkComparison(string $operator, LiteralExpression $expression)
    {
        $field = $this->field;
        if ($operator === 'like') {
            $field = 'LOWER('.$field.')';
            $expression = StringExpression::create('%'.$expression.'%');
        }

        $params = $this->queryBuilder->getParameters();
        $parameterName = $origParamName = preg_replace('/\W+/', '_', Inflector::tableize($this->field));

        $filter = function (Parameter $parameter) use (&$parameterName): bool {
            return $parameter->getName() === $parameterName;
        };

        $i = 1;
        while (0 < $params->filter($filter)->count()) {
            $parameterName = $origParamName . '_' . $i++;
        }

        $this->queryBuilder->setParameter($parameterName, $expression->dispatch($this));
        return new Expr\Comparison($field, self::COMPARISON_MAP[$operator], ':'.$parameterName);
    }

    /**
     * @inheritdoc
     */
    public function walkAll()
    {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function walkOrder(string $field, string $direction)
    {
        return new Expr\OrderBy($field, $direction);
    }

    /**
     * @inheritDoc
     */
    public function walkNot(ExpressionInterface $expression)
    {
        return new Expr\Func('NOT', [$expression->dispatch($this)]);
    }

    /**
     * @inheritDoc
     */
    public function walkAnd(array $arguments)
    {
        return new Expr\Andx(array_map(function (ExpressionInterface $expression) {
            return $expression->dispatch($this);
        }, $arguments));
    }

    /**
     * @inheritDoc
     */
    public function walkOr(array $arguments)
    {
        return new Expr\Orx(array_map(function (ExpressionInterface $expression) {
            return $expression->dispatch($this);
        }, $arguments));
    }

    /**
     * @inheritdoc
     */
    public function walkEntry(string $key, ExpressionInterface $expression)
    {
        $walker = new DqlWalker($this->queryBuilder, $this->field.'.'.$key);

        return $expression->dispatch($walker);
    }
}
