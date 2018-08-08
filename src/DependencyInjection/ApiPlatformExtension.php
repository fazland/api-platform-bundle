<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\DependencyInjection;

use Fazland\ApiPlatformBundle\Decoder\DecoderInterface;
use Fazland\ApiPlatformBundle\HttpKernel\CorsListener;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class ApiPlatformExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
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

        if ($config['cors']['enabled']) {
            $this->loadCors($loader, $container, $config['cors']);
        }

        if (method_exists($container, 'registerForAutoconfiguration')) {
            $container->registerForAutoconfiguration(DecoderInterface::class)->addTag('fazland_api.decoder');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
    {
        return new Configuration();
    }

    private function loadCors(XmlFileLoader $loader, ContainerBuilder $container, array $config): void
    {
        $loader->load('cors.xml');

        $definition = $container->findDefinition(CorsListener::class);
        $definition->setArguments([
            0 < count($config['origins']) ? $config['origins'] : null,
            $config['exposed_headers'],
        ]);
    }
}
