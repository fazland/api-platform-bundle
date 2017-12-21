<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\DependencyInjection;

use Fazland\ApiPlatformBundle\Decoder\DecoderInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class ApiPlatformExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        if ($config['body_converter']['enabled']) {
            $loader->load('decoders.xml');
        }

        $loader->load('patch_manager.xml');
        $loader->load('serializer.xml');

        if ($config['view']['enabled']) {
            $loader->load('view.xml');
            $loader->load('exception_listeners.xml');
        }

        if ($config['catch_exceptions']) {
            $loader->load('exception.xml');
        }

        if ($config['cors']) {
            $loader->load('cors.xml');
        }

        if (method_exists($container, 'registerForAutoconfiguration')) {
            $container->registerForAutoconfiguration(DecoderInterface::class)->addTag('kcs_api.decoder');
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration();
    }
}
