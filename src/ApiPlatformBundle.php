<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle;

use Kcs\ApiPlatformBundle\DependencyInjection\CompilerPass\RegisterDecoders;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ApiPlatformBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container
            ->addCompilerPass(new RegisterDecoders());
    }
}
