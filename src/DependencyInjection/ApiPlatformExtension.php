<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\DependencyInjection;

use Kcs\ApiPlatformBundle\Decoder\DecoderInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class ApiPlatformExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('decoders.xml');
        $loader->load('patch_manager.xml');
        $loader->load('view.xml');

        if (method_exists($container, 'registerForAutoconfiguration')) {
            $container->registerForAutoconfiguration(DecoderInterface::class)
                ->addTag('kcs_api.decoder');
        }
    }
}
