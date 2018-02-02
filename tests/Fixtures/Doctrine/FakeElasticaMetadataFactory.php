<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\Doctrine;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Fazland\ODM\Elastica\Metadata\MetadataFactory;
use Kcs\Metadata\ClassMetadataInterface;

class FakeElasticaMetadataFactory extends MetadataFactory
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

    /**
     * {@inheritdoc}
     */
    public function getAllMetadata(): array
    {
        return array_values($this->_metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFor($className): ClassMetadataInterface
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
    public function setMetadataFor($className, $class)
    {
        $this->_metadata[$className] = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function isTransient($className): bool
    {
        return false;
    }
}
