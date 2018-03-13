<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle;

use Fazland\ApiPlatformBundle\DependencyInjection\CompilerPass\RegisterDecoders;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ApiPlatformBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $container
            ->addCompilerPass(new RegisterDecoders())
        ;
    }
}
