<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;

/**
 * @Annotation()
 */
class Sunset implements ConfigurationInterface
{
    /**
     * @Required()
     */
    public ?string $date;

    public function __construct()
    {
        $this->date = null;
    }

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
