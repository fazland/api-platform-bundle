<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Form\DTO;

use Fazland\ApiPlatformBundle\Pagination\PageToken;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\OrderExpression;
use Symfony\Component\Validator\Constraints as Assert;

class Query
{
    /**
     * @var ExpressionInterface[]
     */
    public $filters;

    /**
     * @var OrderExpression
     *
     * @Assert\Type(OrderExpression::class)
     */
    public $ordering;

    /**
     * @var PageToken
     */
    public $pageToken;

    /**
     * @var int
     */
    public $skip;

    /**
     * @var int
     */
    public $limit;

    public function __construct()
    {
        $this->filters = [];
    }
}
