<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Tests\Fixtures\Exception;

use Kcs\ApiPlatformBundle\Tests\Fixtures\TestKernel;
use Kcs\Serializer\Bundle\SerializerBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends TestKernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new SerializerBundle(),
            new AppBundle(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config.yml');
    }
}
