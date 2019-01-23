<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\DependencyInjection\CompilerPass;

use Fazland\ApiPlatformBundle\Form\Extension\AutoSubmitRequestHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class OverrideDefaultRequestHandlerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $isAutoSubmitRequestHandlerEnabled = $container->getParameter('fazland_api.auto_submit_request_handler_is_enabled');
        if (! $isAutoSubmitRequestHandlerEnabled) {
            return;
        }

        $container->findDefinition('form.type_extension.form.request_handler')
            ->setClass(AutoSubmitRequestHandler::class)
        ;
    }
}
