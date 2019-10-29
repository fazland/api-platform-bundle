<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Walker\PhpCr;

use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;

/**
 * Used only to build valid query objects in NodeWalker.
 * Please DO NOT use it.
 *
 * @internal
 */
class DummyNode extends AbstractNode
{
    /**
     * {@inheritdoc}
     */
    public function getNodeType()
    {
        // Do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function getCardinalityMap()
    {
        // Do nothing
    }
}
