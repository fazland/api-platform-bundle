<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Doctrine\ORM\Exception;

use Doctrine\ORM\NonUniqueResultException as Base;
use Fazland\ApiPlatformBundle\Doctrine\Exception\NonUniqueResultException as NonUniqueResultExceptionInterface;

class NonUniqueResultException extends Base implements NonUniqueResultExceptionInterface
{
}
