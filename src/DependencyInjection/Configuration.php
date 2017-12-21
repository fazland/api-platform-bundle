<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        $rootNode = $tb->root('api_platform', 'array');

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
                ->booleanNode('cors')
                    ->defaultValue(true)
                ->end()
            ->end()
        ;

        return $tb;
    }
}
