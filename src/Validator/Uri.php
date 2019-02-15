<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Validator;

use Symfony\Component\Validator\Constraints\Url;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class Uri extends Url
{
}
