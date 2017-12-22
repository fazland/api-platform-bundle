<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Doctrine\ORM\Exception;

use Doctrine\ORM\NoResultException as Base;
use Fazland\ApiPlatformBundle\Doctrine\Exception\NoResultException as NoResultExceptionInterface;

class NoResultException extends Base implements NoResultExceptionInterface
{
}
