<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\Bundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Fazland\ApiPlatformBundle\ApiPlatformBundle;
use Fazland\ApiPlatformBundle\Tests\Fixtures\TestKernel;
use Kcs\Serializer\Bundle\SerializerBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
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
            new SensioFrameworkExtraBundle(),
            new SecurityBundle(),
            new SerializerBundle(),
            new DoctrineBundle(),
            new ApiPlatformBundle(),
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
