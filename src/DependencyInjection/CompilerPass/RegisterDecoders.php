<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\DependencyInjection\CompilerPass;

use Kcs\ApiPlatformBundle\Decoder\DecoderProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterDecoders implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $decoders = [];

        foreach ($container->findTaggedServiceIds('kcs_api.decoder') as $serviceId => $unused) {
            $definition = $container->getDefinition($serviceId);
            $definition->setLazy(true);

            $class = $definition->getClass();
            $format = $class::getFormat();

            $decoders[$format] = new Reference($serviceId);
        }

        $provider = $container->findDefinition(DecoderProvider::class);
        $provider->replaceArgument(0, $decoders);
    }
}
