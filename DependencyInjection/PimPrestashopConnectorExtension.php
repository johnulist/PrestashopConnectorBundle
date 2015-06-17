<?php

namespace Pim\Bundle\PrestashopConnectorBundle\DependencyInjection;

use Pim\Bundle\CatalogBundle\Version;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

/**
 * Prestashop connector bundle extension.
 *
 * @author    Santiago Díaz <santiago.diaz@me.com>
 * @copyright 2015 Santiago Díaz (http://sdiaz.es)
 * @license   http://opensource.org/licenses/mit  The MIT License (MIT)
 */
class PimPrestashopConnectorExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('builders.yml');
        $loader->load('cleaners.yml');
        $loader->load('entities.yml');
        $loader->load('event_subscribers.yml');
        $loader->load('filters.yml');
        $loader->load('guessers.yml');
        $loader->load('mappers.yml');
        $loader->load('mergers.yml');
        $loader->load('managers.yml');
        $loader->load('normalizers.yml');
        $loader->load('processors.yml');
        $loader->load('purgers.yml');
        $loader->load('readers.yml');
        $loader->load('repositories.yml');
        $loader->load('services.yml');
        $loader->load('validators.yml');
        $loader->load('webservices.yml');
        $loader->load('writers.yml');

        $storageConfig = sprintf('storage_driver/%s.yml', $this->getStorageDriver($container));
        if (file_exists(__DIR__.'/../Resources/config/'.$storageConfig)) {
            $loader->load($storageConfig);
        }
    }

    /**
     * Returns the storage driver used.
     *
     * @param ContainerBuilder $container
     *
     * @return string
     */
    protected function getStorageDriver(ContainerBuilder $container)
    {
        if (version_compare(Version::VERSION, '1.3.0', '<')) {
            return $container->getParameter('pim_catalog_storage_driver');
        } else {
            return $container->getParameter('pim_catalog_product_storage_driver');
        }
    }
}
