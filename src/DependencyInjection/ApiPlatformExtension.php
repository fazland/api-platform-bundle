<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\DependencyInjection;

use Fazland\ApiPlatformBundle\Controller\ExceptionController;
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

        if ($config['versioning']['accept_header']['enabled']) {
            $container->setParameter('fazland_api.versioning.accept_header.default_type', $config['versioning']['accept_header']['default_type']);
            $container->setParameter('fazland_api.versioning.accept_header.uris', $config['versioning']['accept_header']['uris']);

            $loader->load('accept_header_parser.xml');
        }

        $loader->load('patch_manager.xml');
        $loader->load('serializer.xml');
        $loader->load('form.xml');

        if ($config['view']['enabled']) {
            $loader->load('view.xml');
            $loader->load('sunset.xml');
            $loader->load('exception_listeners.xml');
        }

        if ($config['catch_exceptions']) {
            $container->setParameter('kernel.error_controller', ExceptionController::class);
            $loader->load('exception.xml');
        }

        if ($config['cors']['enabled']) {
            $this->loadCors($loader, $container, $config['cors']);
        }

        if (\method_exists($container, 'registerForAutoconfiguration')) {
            $container->registerForAutoconfiguration(DecoderInterface::class)->addTag('fazland_api.decoder');
        }

        $container->setParameter('fazland_api.response_charset', $config['response_charset']);
        $container->setParameter('fazland_api.auto_submit_request_handler_is_enabled', $config['auto_submit_request_handler']['enabled']);
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
            0 < \count($config['origins']) ? $config['origins'] : null,
            $config['exposed_headers'],
        ]);
    }
}
