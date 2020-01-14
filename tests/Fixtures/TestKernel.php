<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures;

use Psr\Log\NullLogger;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\HttpKernel\Kernel;

abstract class TestKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    protected function initializeContainer(): void
    {
        $class = $this->getContainerClass();
        $cache = new ConfigCache($this->getCacheDir().'/'.$class.'.php', $this->debug);

        $container = $this->buildContainer();
        $container->register('logger', NullLogger::class);
        $container->compile();
        $this->dumpContainer($cache, $container, $class, $this->getContainerBaseClass());

        $this->container = require $cache->getPath();

        $this->container->set('kernel', $this);

        if ($this->container->has('cache_warmer')) {
            $this->container->get('cache_warmer')->warmUp($this->container->getParameter('kernel.cache_dir'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir(): string
    {
        return \dirname((new \ReflectionClass($this))->getFileName()).'/var/cache/'.$this->environment;
    }
}
