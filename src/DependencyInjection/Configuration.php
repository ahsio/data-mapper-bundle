<?php

namespace Kassko\Bundle\DataAccessBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $rootNode = $builder->root('kassko_data_access');

        $rootNode
            ->addDefaultsIfNotSet()
        	->children()
                ->scalarNode('logger_service')->end()
                ->arrayNode('cache')->addDefaultsIfNotSet()
                    ->append($this->addCacheNode('metadata_cache'))
                    ->append($this->addCacheNode('result_cache'))
                ->end()
                ->arrayNode('mapping')->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('defaultResourceType')->defaultValue('annotations')->end()
                        ->arrayNode('bundles')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('type')->defaultValue('annotations')->end()
                                    ->scalarNode('resource_dir')->end()

                                    ->arrayNode('entities')
                                        ->prototype('array')
                                            ->children()
                                                ->scalarNode('type')->end()
                                                ->scalarNode('resource_path')->end()
                                                ->scalarNode('resource_name')->end()
                                                ->scalarNode('entity_class')->end()
                                            ->end()
                                        ->end()
                                    ->end()

                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
        	->end()
        ;

        return $builder;
    }

    private function addCacheNode($name)
    {
        $builder = new TreeBuilder();
        $node = $builder->root($name);

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('class')->end()
                ->scalarNode('id')->end()
                ->scalarNode('life_time')->defaultValue(0)->end()
                ->booleanNode('is_shared')->defaultFalse()->end()
<<<<<<< Updated upstream
                ->scalarNode('adapter_class')->defaultValue('Kassko\DataAccess\Adapter\DoctrineCacheAdapter')->end()
=======
                ->scalarNode('adapter_class')->defaultValue('Kassko\DataAccess\Cache\DoctrineCacheAdapter')->end()
>>>>>>> Stashed changes
            ->end()
        ;

        return $node;
    }
}
