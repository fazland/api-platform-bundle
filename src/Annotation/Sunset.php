<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Annotation()
 */
class Sunset implements ConfigurationInterface
{
    /**
     * @var string
     *
     * @Required()
     */
    public $date;

    /**
     * {@inheritdoc}
     */
    public function getAliasName(): string
    {
        return 'rest_sunset';
    }

    /**
     * {@inheritdoc}
     */
    public function allowArray(): bool
    {
        return false;
    }
}
