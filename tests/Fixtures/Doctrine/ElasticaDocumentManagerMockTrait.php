<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\Doctrine;

use Elastica\Client;
use Elastica\Index;
use Fazland\ODM\Elastica\Builder;
use Fazland\ODM\Elastica\DocumentManagerInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

trait ElasticaDocumentManagerMockTrait
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var Client|ObjectProphecy
     */
    private $client;

    public function getDocumentManager(): DocumentManagerInterface
    {
        if (null === $this->documentManager) {
            $this->client = $this->prophesize(Client::class);
            $builder = new Builder();

            $builder
                ->setClient($this->client->reveal())
                ->setMetadataFactory(new FakeElasticaMetadataFactory())
            ;

            $this->client->getIndex(Argument::type('string'))->will(function ($args) {
                return new Index($this->reveal(), $args[0]);
            });

            $this->documentManager = $builder->build();
        }

        return $this->documentManager;
    }
}
