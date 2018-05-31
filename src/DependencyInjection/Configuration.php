<?php

namespace Matasar\Bundle\Rumble\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Bundle configuration.
 *
 * @author Yevhen Matasar <matasar.ei@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('rumble');

        $rootNode
            ->children()
                ->scalarNode('migrations_dir')->defaultValue('migrations')->end()
                ->scalarNode('seeds_dir')->defaultValue('seeds')->end()
                ->scalarNode('region')->defaultValue('dev')->end()
                ->scalarNode('version')->defaultValue('2012-08-10')->end()
                ->scalarNode('key')->defaultValue('dev')->end()
                ->scalarNode('secret')->defaultValue('dev')->end()
                ->scalarNode('endpoint')
            ;

        return $treeBuilder;
    }
}
