<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Twig;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Configuaration extension.
 *
 */
class ConfigurationExtension extends \Twig_Extension
{
    const CONFIG_RESOURCE = '@PimPrestashopConnectorBundle/Resources/config/configuration_settings.yml';

    /**
     * @param FileLocatorInterface $fileLocator
     */
    public function __construct(FileLocatorInterface $fileLocator)
    {
        $yaml = new Parser();

        $configuration = array();

        try {
            $configFilePath = $fileLocator->locate(self::CONFIG_RESOURCE);

            $configuration = $yaml->parse(file_get_contents($configFilePath));
        } catch (\InvalidArgumentException $e) {
            printf("Configuration file not found from resource: %s", self::CONFIG_RESOURCE);
        } catch (ParseException $e) {
            printf("Unable to parse the YAML string: %s", $e->getMessage());
        }

        $configuration['show_configuration'] = isset($configuration['show_configuration']) ?
            $configuration['show_configuration'] : array();
        $configuration['edit_configuration'] = isset($configuration['edit_configuration']) ?
            $configuration['edit_configuration'] : array();

        $this->configuration = $configuration;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('get_show_configuration', array($this, 'getShowConfiguration')),
        );
    }

    /**
     * @param array $configuration
     *
     * @return array
     */
    public function getShowConfiguration(array $configuration)
    {
        foreach ($this->configuration['show_configuration'] as $blockIndex => $block) {
            $attributes = array();
            foreach ($block['elements'] as $element => $elementParameters) {
                if (in_array($element, array_keys($configuration))) {
                    $attributes[$element] = array_merge(
                        array('value' => $configuration[$element]),
                        $elementParameters ? $elementParameters : array()
                    );
                }
            }

            if (count($attributes) === 0) {
                unset($this->configuration['show_configuration'][$blockIndex]);
            } else {
                $this->configuration['show_configuration'][$blockIndex]['attributes'] = $attributes;
            }
        }

        return $this->configuration['show_configuration'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pim_prestashop_connector_extension';
    }
}
