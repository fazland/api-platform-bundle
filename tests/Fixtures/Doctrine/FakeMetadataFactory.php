<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Tests\Fixtures\Doctrine;

use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

class FakeMetadataFactory implements ClassMetadataFactory
{
    private $_metadata = [];
    private $entityManager;
    private $reflService;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->reflService = new RuntimeReflectionService();
    }

    /**
     * @param mixed $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function setCacheDriver()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getAllMetadata()
    {
        return array_values($this->_metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFor($className)
    {
        if (! isset($this->_metadata[$className])) {
            throw new MappingException();
        }

        return $this->_metadata[$className];
    }

    /**
     * {@inheritdoc}
     */
    public function hasMetadataFor($className)
    {
        return isset($this->_metadata[$className]);
    }

    /**
     * {@inheritdoc}
     *
     * @param ClassMetadata $class
     */
    public function setMetadataFor($className, $class)
    {
        $this->_metadata[$className] = $class;
        $class->initializeReflection($this->reflService);
        $class->wakeupReflection($this->reflService);
    }

    /**
     * {@inheritdoc}
     */
    public function isTransient($className)
    {
        return false;
    }
}
