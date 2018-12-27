<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\Doctrine;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;

class FakeMetadataFactory implements ClassMetadataFactory
{
    private $_metadata = [];
    private $reflService;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->reflService = new RuntimeReflectionService();
    }

    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
    }

    public function setDocumentManager($documentManager): void
    {
    }

    public function setConfiguration(): void
    {
    }

    public function setCacheDriver(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getAllMetadata(): array
    {
        return \array_values($this->_metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFor($className): ClassMetadata
    {
        if (! isset($this->_metadata[$className])) {
            throw new MappingException('Cannot find metadata for "'.$className.'"');
        }

        return $this->_metadata[$className];
    }

    /**
     * {@inheritdoc}
     */
    public function hasMetadataFor($className): bool
    {
        return isset($this->_metadata[$className]);
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadataFor($className, $class): void
    {
        $this->_metadata[$className] = $class;

        if ($class instanceof ORMClassMetadata) {
            $class->initializeReflection($this->reflService);
            $class->wakeupReflection($this->reflService);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isTransient($className): bool
    {
        return false;
    }
}
