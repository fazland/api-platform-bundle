<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Walker\PhpCr;

use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;
use Doctrine\ODM\PHPCR\Query\Builder\ConstraintAndx;
use Doctrine\ODM\PHPCR\Query\Builder\ConstraintChild;
use Doctrine\ODM\PHPCR\Query\Builder\ConstraintComparison;
use Doctrine\ODM\PHPCR\Query\Builder\ConstraintNot;
use Doctrine\ODM\PHPCR\Query\Builder\ConstraintOrx;
use Doctrine\ODM\PHPCR\Query\Builder\OperandDynamicField;
use Doctrine\ODM\PHPCR\Query\Builder\OperandDynamicLocalName;
use Doctrine\ODM\PHPCR\Query\Builder\OperandDynamicLowerCase;
use Doctrine\ODM\PHPCR\Query\Builder\OperandStaticLiteral;
use Doctrine\ODM\PHPCR\Query\Builder\OrderByAdd;
use Doctrine\ODM\PHPCR\Query\Builder\Ordering;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\LiteralExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\NullExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ValueExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\AbstractWalker;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class NodeWalker extends AbstractWalker
{
    private const COMPARISON_MAP = [
        '=' => QOMConstants::JCR_OPERATOR_EQUAL_TO,
        '<' => QOMConstants::JCR_OPERATOR_LESS_THAN,
        '<=' => QOMConstants::JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO,
        '>' => QOMConstants::JCR_OPERATOR_GREATER_THAN,
        '>=' => QOMConstants::JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO,
        'like' => QOMConstants::JCR_OPERATOR_LIKE,
    ];

    /**
     * @var string|null
     */
    private $fieldType;

    public function __construct(string $field, ?string $fieldType = null)
    {
        parent::__construct($field);

        $this->fieldType = $fieldType;
    }

    /**
     * {@inheritdoc}
     */
    public function walkLiteral(LiteralExpression $expression)
    {
        $value = parent::walkLiteral($expression);
        if ($expression instanceof NullExpression) {
            return $value;
        }

        if ('date' === $this->fieldType) {
            return new \DateTime($value);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparison(string $operator, ValueExpression $expression)
    {
        if ('parent' === $this->fieldType) {
            if ($operator !== '=') {
                throw new \InvalidArgumentException('Cannot evaluate child nodes with non equals comparison.');
            }

            return new ConstraintChild(new DummyNode(), \explode('.', $this->field)[0], $this->walkLiteral($expression));
        }

        $comparison = new ConstraintComparison(new DummyNode(), self::COMPARISON_MAP[$operator]);

        $field = $this->field;
        $left = function (AbstractNode $parent) use ($field): AbstractNode {
            return 'nodename' === $this->fieldType ?
                new OperandDynamicLocalName($parent, \explode('.', $field)[0]) :
                new OperandDynamicField($parent, $field);
        };

        if ('like' === $operator) {
            $lowerCase = new OperandDynamicLowerCase($comparison);
            $lowerCase->addChild($left($lowerCase));
            $left = $lowerCase;

            $value = '%'.$expression->getValue().'%';
        } else {
            $left = $left($comparison);
            $value = $expression->getValue();
        }

        $right = new OperandStaticLiteral($comparison, $value);

        $comparison->addChild($left);
        $comparison->addChild($right);

        return $comparison;
    }

    /**
     * {@inheritdoc}
     */
    public function walkAll()
    {
        // Do nothing.
    }

    /**
     * {@inheritdoc}
     */
    public function walkOrder(string $field, string $direction)
    {
        $left = function (AbstractNode $parent) use ($field): AbstractNode {
            return 'nodename' === $this->fieldType ?
                new OperandDynamicLocalName($parent, \explode('.', $field)[0]) :
                new OperandDynamicField($parent, $field);
        };

        $node = new OrderByAdd();
        /** @var Ordering $ordering */
        $ordering = $node->{$direction}();
        $ordering->addChild($left($ordering));

        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function walkNot(ExpressionInterface $expression)
    {
        $node = new ConstraintNot();
        $node->addChild($expression->dispatch($this));

        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function walkAnd(array $arguments)
    {
        $node = new ConstraintAndx();
        foreach ($arguments as $argument) {
            $node->addChild($argument->dispatch($this));
        }

        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function walkOr(array $arguments)
    {
        $node = new ConstraintOrx();
        foreach ($arguments as $argument) {
            $node->addChild($argument->dispatch($this));
        }

        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function walkEntry(string $key, ExpressionInterface $expression)
    {
        $walker = new NodeWalker($this->field.'.'.$key);

        return $expression->dispatch($walker);
    }
}
