<?php

namespace Kassko\Bundle\DataAccessBundle\DependencyInjection;

use Kassko\DataAccess\Registry\Registry;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class KasskoDataAccessExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $this->configureLogger($config, $container);
        $this->configureLazyLoader($container);
        $this->configureConfiguration($config, $container);
    }

    private function configureLogger(array $config, ContainerBuilder $container)
    {
        if (isset($config['logger_service'])) {

            $loggerServiceId = $config['logger_service'];
            $loggerDef = $container->getDefinition($loggerServiceId);
            $loggerDef->addTag('kassko_data_access.registry_item', ['key' => Registry::KEY_LOGGER]);

            $objectManagerDef = $container->getDefinition('kassko_data_access.object_manager');
            $objectManagerDef->addMethodCall('setLogger', [new Reference($loggerServiceId)]);
        }
    }

    private function configureLazyLoader(ContainerBuilder $container)
    {
        $lazyLoaderFactoryDef = $container->getDefinition('kassko_data_access.lazy_loader_factory');
        $lazyLoaderFactoryDef->addTag('kassko_data_access.registry_item', ['key' => Registry::KEY_LAZY_LOADER_FACTORY]);
    }

    private function configureConfiguration(array $config, ContainerBuilder $container)
    {
        $configurationDef = $container->getDefinition('kassko_data_access.configuration');

        $this->configureMapping($config['mapping'], $container, $configurationDef);
        $this->configureMetadataCache($config['cache']['metadata_cache'], $container, $configurationDef);
        $this->configureResultCache($config['cache']['result_cache'], $container, $configurationDef);
    }

    private function configureMapping(array $config, ContainerBuilder $container, Definition $configurationDef)
    {
        $this->configureMappingWithDefaults($config, $container, $configurationDef);

        if (empty($config['bundles'])) {
            return;
        }

        foreach ($config['bundles'] as $bundleName => $bundleConfig) {

            $parentClassMetadataResourceType = $bundleConfig['type'];

            if (! empty($bundleConfig['resource_path'])) {
                $parentClassMetadataResourcePath = trim($bundleConfig['resource_path']);
            }

            if (! empty($bundleConfig['resource_dir'])) {
                $classMetadataResourceDir = $bundleConfig['resource_dir'];
            }

            foreach ($bundleConfig['entities'] as $entityName => $entityConfig) {

                if (! empty($entityConfig['type'])) {
                    $classMetadataResourceType = trim($entityConfig['type']);
                }

                if (! empty($entityConfig['resource_path'])) {
                    $classMetadataResource = trim($entityConfig['resource_path']);
                } elseif (! empty($entityConfig['resource_name'])) {
                    $classMetadataResource = $classMetadataResourceDir.'/'.$entityConfig['resource_name'];
                }

                $mappingEntityClass = trim($entityConfig['entity_class']);

                if (isset($classMetadataResourceType)) {
                    $configurationDef->addMethodCall('addClassMetadataResourceType', [$mappingEntityClass, $classMetadataResourceType]);
                } elseif (isset($parentClassMetadataResourceType)) {
                    $configurationDef->addMethodCall('addClassMetadataResourceType', [$mappingEntityClass, $parentClassMetadataResourceType]);
                }

                if (isset($classMetadataResource)) {
                    $configurationDef->addMethodCall('addClassMetadataResource', [$mappingEntityClass, $classMetadataResource]);
                }

                if (isset($classMetadataDir)) {
                    $configurationDef->addMethodCall('addClassMetadataDir', [$mappingEntityClass, $classMetadataDir]);
                } elseif (isset($parentClassMetadataDir)) {
                    $configurationDef->addMethodCall('addClassMetadataDir', [$mappingEntityClass, $parentClassMetadataDir]);
                }
            }
        }
    }

    private function configureMappingWithDefaults(array $config, ContainerBuilder $container, Definition $configurationDef)
    {
        if (! empty($config['default_resource_type'])) {
            $configurationDef->addMethodCall('setDefaultClassMetadataResourceType', [$config['default_resource_type']]);
        }

        if (! empty($config['default_resource_dir'])) {
            $configurationDef->addMethodCall('setDefaultClassMetadataResourceDir', [$config['default_resource_dir']]);
        }
    }

    private function configureMetadataCache(array $config, ContainerBuilder $container, Definition $configurationDef)
    {
        $cacheClass = null;
        $cacheId = null;

        if (! empty ($config['class'])) {
            $cacheClass = $config['class'];
        } elseif (! empty($config['id'])) {
            $cacheId = $config['id'];
        } else {
            $cacheClass = "Doctrine\\Common\\Cache\\ArrayCache";
        }

        if (null !== $cacheClass) {

            $cacheId = 'kassko_data_access.class_metadata_cache';
            $cacheDef = new DefinitionDecorator('kassko_data_access.class_metadata_cache.prototype');
            $cacheDef->setClass($cacheClass)->setPublic(false);
            $container->setDefinition($cacheId, $cacheDef);
        }

        $cacheAdapterId = $cacheId.'_adapter';
        $cacheAdapterDef = new Definition($config['adapter_class'], [new Reference($cacheId)]);
        $container->setDefinition($cacheAdapterId, $cacheAdapterDef);

        $cacheConfigId = 'kassko_data_access.configuration.class_metadata_cache';
        $cacheConfigDef = new DefinitionDecorator('kassko_data_access.configuration.cache.prototype');
        $cacheConfigDef->addMethodCall('setCache', [new Reference($cacheAdapterId)]);
        $cacheConfigDef->addMethodCall('setLifeTime', [$config['life_time']]);
        $cacheConfigDef->addMethodCall('setShared', [$config['is_shared']]);
        $container->setDefinition($cacheConfigId, $cacheConfigDef);

        $configurationDef->addMethodCall('setClassMetadataCacheConfig', [new Reference($cacheConfigId)]);
    }

    private function configureResultCache(array $config, ContainerBuilder $container, Definition $configurationDef)
    {
        $cacheClass = null;
        $cacheId = null;

        if (! empty($config['class'])) {
            $cacheClass = $config['class'];
        } elseif (! empty($config['id'])) {
            $cacheId = $config['id'];
        } else {
            $cacheClass = "Doctrine\\Common\\Cache\\ArrayCache";
        }

        if (null !== $cacheClass) {

            $cacheId = 'kassko_data_access.result_cache';
            $cacheDef = new DefinitionDecorator('kassko_data_access.result_cache.prototype');
            $cacheDef->setClass($cacheClass)->setPublic(false);
            $container->setDefinition($cacheId, $cacheDef);
        }

        $cacheAdapterId = $cacheId.'_adapter';
        $cacheAdapterDef = new Definition($config['adapter_class'], [new Reference($cacheId)]);
        $container->setDefinition($cacheAdapterId, $cacheAdapterDef);

        $cacheConfigId = 'kassko_data_access.result_cache_configuration';
        $cacheConfigDef = new DefinitionDecorator('kassko_data_access.configuration.cache.prototype');
        $cacheConfigDef->addMethodCall('setCache', [new Reference($cacheAdapterId)]);
        $cacheConfigDef->addMethodCall('setLifeTime', [$config['life_time']]);
        $cacheConfigDef->addMethodCall('setShared', [$config['is_shared']]);
        $container->setDefinition($cacheConfigId, $cacheConfigDef);

        $configurationDef->addMethodCall('setResultCacheConfig', [new Reference($cacheConfigId)]);
    }
}
