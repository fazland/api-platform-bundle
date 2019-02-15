<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Validator\Money;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
final class GreaterThanOrEqual extends AbstractComparison
{
    /**
     * @var string
     */
    public $message = 'This value should be greater than or equal to {{ compared_value }}.';
}
