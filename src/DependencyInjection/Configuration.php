<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('api_platform');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('versioning')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('accept_header')
                            ->canBeDisabled()
                            ->children()
                                ->scalarNode('default_type')->defaultValue('application/json')->end()
                                ->arrayNode('uris')
                                    ->requiresAtLeastOneElement()
                                    ->defaultValue(['.*'])
                                    ->scalarPrototype()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('body_converter')
                    ->canBeDisabled()
                ->end()
                ->arrayNode('view')
                    ->canBeDisabled()
                ->end()
                ->booleanNode('catch_exceptions')
                    ->defaultValue(true)
                ->end()
                ->arrayNode('cors')
                    ->canBeDisabled()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('origins')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('exposed_headers')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('response_charset')
                    ->defaultValue('UTF-8')
                ->end()
                ->arrayNode('auto_submit_request_handler')
                    ->canBeDisabled()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
