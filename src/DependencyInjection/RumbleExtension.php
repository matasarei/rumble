<?php

namespace Matasar\Bundle\Rumble\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Yevhen Matasar <matasar.ei@gmail.com>
 */
class RumbleExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (empty($config['endpoint'])) {
            throw new \RuntimeException('Endpoint must be set in project configuration. See README file.');
        }

        if ($config['multi_app_mode'] && empty($config['app_name'])) {
            throw new \RuntimeException('App name must be set for multi-app mode.');
        }

        $container->setParameter('rumble.table_name', $config['table_name']);
        $container->setParameter('rumble.multi_app_mode', $config['multi_app_mode']);
        $container->setParameter('rumble.app_name', $config['app_name']);
        $container->setParameter('rumble.migrations_dir', $config['migrations_dir']);
        $container->setParameter('rumble.seeds_dir', $config['seeds_dir']);
        $container->setParameter('rumble.version', $config['version']);
        $container->setParameter('rumble.region', $config['region']);
        $container->setParameter('rumble.key', $config['key']);
        $container->setParameter('rumble.secret', $config['secret']);
        $container->setParameter('rumble.endpoint', $config['endpoint']);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');
    }
}
