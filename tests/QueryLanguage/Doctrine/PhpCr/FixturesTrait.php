<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\QueryLanguage\Doctrine\PhpCr;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOSqlite\Driver;
use Doctrine\ODM\PHPCR\Configuration;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\PHPCR\NodeTypeRegistrator;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Document\PhpCrQueryLanguage as QueryLanguageFixtures;
use Jackalope\Factory;
use Jackalope\Repository;
use Jackalope\Transport\DoctrineDBAL\Client;
use Jackalope\Transport\DoctrineDBAL\RepositorySchema;
use PHPCR\SimpleCredentials;

trait FixturesTrait
{
    private static DocumentManagerInterface $documentManager;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        $factory = new Factory();
        $transport = new Client($factory, $connection = new Connection(['url' => 'sqlite:///:memory:'], new Driver()));
        $repository = new Repository($factory, $transport);
        $credentials = new SimpleCredentials('admin', 'admin');

        $schema = new RepositorySchema([], $connection);
        foreach ($schema->toSql($connection->getDatabasePlatform()) as $sql) {
            $connection->exec($sql);
        }

        $session = $repository->login($credentials, 'default');
        $registrator = new NodeTypeRegistrator();
        $registrator->registerNodeTypes($session);

        $configuration = new Configuration();
        $configuration->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader(), __DIR__.'/../../../Fixtures/Document/PhpCrQueryLanguage'));
        $configuration->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_EVAL);
        $configuration->setProxyNamespace('__CG__\\'.QueryLanguageFixtures::class);
        $configuration->setProxyDir(\sys_get_temp_dir().'/'.\uniqid('api-platform-proxy', true));
        $configuration->setDocumentNamespaces([
            QueryLanguageFixtures::class,
        ]);

        self::$documentManager = DocumentManager::create($session, $configuration);

        self::$documentManager->persist(new QueryLanguageFixtures\User('foo'));
        self::$documentManager->persist(new QueryLanguageFixtures\User('bar'));
        self::$documentManager->persist(new QueryLanguageFixtures\User('foobar'));
        self::$documentManager->persist(new QueryLanguageFixtures\User('barbar'));
        self::$documentManager->persist(new QueryLanguageFixtures\User('baz'));
        self::$documentManager->persist(new QueryLanguageFixtures\User('donald duck'));
        self::$documentManager->persist(new QueryLanguageFixtures\User('goofy'));

        self::$documentManager->flush();
        self::$documentManager->clear();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        self::$documentManager->clear();
    }
}
