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
        if (\method_exists(TreeBuilder::class, 'getRootNode')) {
            $treeBuilder = new TreeBuilder('api_platform');
            $rootNode = $treeBuilder->getRootNode();
        } else {
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root('api_platform');
        }

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
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
