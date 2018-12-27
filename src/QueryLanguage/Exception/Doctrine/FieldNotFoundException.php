<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Exception\Doctrine;

use Fazland\ApiPlatformBundle\QueryLanguage\Exception\ExceptionInterface;

class FieldNotFoundException extends \DomainException implements ExceptionInterface
{
    /**
     * Constructor.
     *
     * @param string $fieldName
     * @param string $className
     */
    public function __construct(string $fieldName, string $className)
    {
        parent::__construct(\sprintf('Field "%s" could not be found in %s mapping.', $fieldName, $className));
    }
}
