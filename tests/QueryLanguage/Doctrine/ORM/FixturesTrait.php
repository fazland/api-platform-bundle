<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\QueryLanguage\Doctrine\ORM;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Entity\QueryLanguage as QueryLanguageFixtures;

trait FixturesTrait
{
    private static EntityManagerInterface $entityManager;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        $configuration = new Configuration();
        $configuration->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader(), __DIR__.'/../../../Fixtures/Entity/QueryLanguage'));
        $configuration->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_EVAL);
        $configuration->setProxyNamespace('__CG__\\'.QueryLanguageFixtures::class);
        $configuration->setProxyDir(\sys_get_temp_dir().'/'.\uniqid('api-platform-proxy', true));
        $configuration->setEntityNamespaces([QueryLanguageFixtures::class]);

        self::$entityManager = EntityManager::create(['url' => 'sqlite:///:memory:'], $configuration);
        $schemaTool = new SchemaTool(self::$entityManager);
        $schemaTool->updateSchema(self::$entityManager->getMetadataFactory()->getAllMetadata(), true);

        self::$entityManager->persist(new QueryLanguageFixtures\User('foo'));
        self::$entityManager->persist(new QueryLanguageFixtures\User('bar'));
        self::$entityManager->persist(new QueryLanguageFixtures\User('foobar'));
        self::$entityManager->persist(new QueryLanguageFixtures\User('barbar'));
        self::$entityManager->persist(new QueryLanguageFixtures\User('baz'));
        self::$entityManager->persist(new QueryLanguageFixtures\User('donald duck'));
        self::$entityManager->persist(new QueryLanguageFixtures\User('goofy'));

        self::$entityManager->flush();
        self::$entityManager->clear();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        self::$entityManager->clear();
    }
}
