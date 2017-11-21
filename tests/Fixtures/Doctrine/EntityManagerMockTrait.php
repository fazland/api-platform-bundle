<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Tests\Fixtures\Doctrine;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\PDOConnection;
use Doctrine\DBAL\Driver\PDOMySql\Driver;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Kcs\ApiPlatformBundle\Tests\Doctrine\Mocks\MockPlatform;
use Prophecy\Prophecy\ObjectProphecy;

trait EntityManagerMockTrait
{
    /**
     * @var EntityManager
     */
    private $_entityManager;

    /**
     * @var Connection|ObjectProphecy
     */
    private $_connection;

    /**
     * @var DriverConnection|ObjectProphecy
     */
    private $_innerConnection;

    /**
     * @var Configuration|ObjectProphecy
     */
    private $_configuration;

    public function getEntityManager()
    {
        if (null === $this->_entityManager) {
            $this->_configuration = new Configuration();

            $this->_configuration->setResultCacheImpl(new ArrayCache());
            $this->_configuration->setClassMetadataFactoryName(FakeMetadataFactory::class);
            $this->_configuration->setMetadataDriverImpl($this->prophesize(MappingDriver::class)->reveal());
            $this->_configuration->setProxyDir(sys_get_temp_dir());
            $this->_configuration->setProxyNamespace('__TMP__\\ProxyNamespace\\');
            $this->_configuration->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_ALWAYS);

            $this->_innerConnection = $this->prophesize(PDOConnection::class);

            $this->_connection = new Connection([
                'pdo' => $this->_innerConnection->reveal(),
                'platform' => new MockPlatform(),
            ], new Driver(), $this->_configuration);

            $this->_entityManager = EntityManager::create($this->_connection, $this->_configuration);
        }

        return $this->_entityManager;
    }
}
