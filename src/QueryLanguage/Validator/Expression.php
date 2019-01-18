<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Expression extends Constraint
{
    /**
     * @var string|callable|null
     *
     * @Required()
     */
    public $walker;

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption(): string
    {
        return 'walker';
    }
}
