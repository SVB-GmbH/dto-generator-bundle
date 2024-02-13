<?php

namespace SVB\DataTransfer\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class DtoGeneratorConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('dto_generator');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('cache_service')->defaultValue(null)->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
