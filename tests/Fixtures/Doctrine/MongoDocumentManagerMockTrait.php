<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\Doctrine;

use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\SchemaManager;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

trait MongoDocumentManagerMockTrait
{
    /**
     * @var DocumentManager
     */
    private $_documentManager;

    /**
     * @var Client|ObjectProphecy
     */
    private $_client;

    /**
     * @var Database|ObjectProphecy
     */
    private $_db;

    /**
     * @var Collection|ObjectProphecy
     */
    private $_collection;

    /**
     * @var Connection
     */
    private $_connection;

    /**
     * @var Configuration
     */
    private $_configuration;

    public function getDocumentManager(): DocumentManager
    {
        if (null === $this->_documentManager) {
            $mongoDb = null;

            $server = $this->prophesize(\MongoClient::class);
            $server->getReadPreference()->willReturn(['type' => \MongoClient::RP_PRIMARY]);
            $server->getWriteConcern()->willReturn([
                'w' => 1,
                'wtimeout' => 5000,
            ]);
            $server->selectDB('doctrine')->will(function ($args) use (&$mongoDb) {
                list($dbName) = $args;
                if (isset($mongoDb)) {
                    return $mongoDb;
                }

                return $mongoDb = new \MongoDB($this->reveal(), $dbName);
            });
            $server->getClient()->willReturn($this->_client = $this->prophesize(Client::class));

            $this->_client->selectDatabase('doctrine', Argument::any())
                ->willReturn($this->_db = $this->prophesize(Database::class));
            $this->_client->selectCollection('doctrine', 'FooBar', Argument::any())
                ->willReturn($this->_collection = $this->prophesize(Collection::class));
            $this->_db->selectCollection('FooBar', Argument::any())->willReturn($this->_collection);

            $server->selectCollection(Argument::cetera())->willReturn($oldCollection = $this->prophesize(\MongoCollection::class));
            $oldCollection->getCollection()->willReturn($this->_collection);

            $schemaManager = $this->prophesize(SchemaManager::class);
            $metadataFactory = new FakeMetadataFactory();
            $this->_connection = new Connection($server->reveal());

            $this->_configuration = new Configuration();
            $this->_configuration->setHydratorDir(sys_get_temp_dir());
            $this->_configuration->setHydratorNamespace('__TMP__\\HydratorNamespace');
            $this->_configuration->setProxyDir(sys_get_temp_dir());
            $this->_configuration->setProxyNamespace('__TMP__\\ProxyNamespace');

            $this->_documentManager = DocumentManager::create($this->_connection, $this->_configuration);

            (function () use ($schemaManager) {
                $this->schemaManager = $schemaManager->reveal();
            })->call($this->_documentManager);

            (function () use ($metadataFactory) {
                $this->metadataFactory = $metadataFactory;
            })->call($this->_documentManager);
        }

        return $this->_documentManager;
    }
}
