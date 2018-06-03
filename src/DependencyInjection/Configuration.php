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
                ->scalarNode('table_name')->defaultValue('migrations')->end()
                ->scalarNode('multi_app_mode')->defaultValue(false)->end()
                ->scalarNode('app_name')->defaultValue('')->end()
                ->scalarNode('migrations_dir')->defaultValue('migrations')->end()
                ->scalarNode('seeds_dir')->defaultValue('seeds')->end()
                ->scalarNode('region')->defaultValue('dev')->end()
                ->scalarNode('version')->defaultValue('latest')->end()
                ->scalarNode('key')->defaultValue('dev')->end()
                ->scalarNode('secret')->defaultValue('dev')->end()
                ->scalarNode('endpoint')
            ;

        return $treeBuilder;
    }
}
