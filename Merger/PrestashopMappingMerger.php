<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Merger;

use Pim\Bundle\PrestashopConnectorBundle\Webservice\PrestashopRestClientParameters;

/**
 * Prestashop mapping merger.
 *
 */
class PrestashopMappingMerger extends MappingMerger
{
    /**
     * Set parameters of all mappers.
     *
     * @param PrestashopRestClientParameters $clientParameters
     * @param string                      $defaultStoreView
     */
    public function setParameters(PrestashopRestClientParameters $clientParameters, $defaultStoreView)
    {
        foreach ($this->getOrderedMappers() as $mapper) {
            $mapper->setParameters($clientParameters, $defaultStoreView);
        }

        $this->hasParametersSet = true;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationField()
    {
        return [
            $this->name.'Mapping' => [
                'type'    => 'textarea',
                'options' => [
                    'required' => false,
                    'attr'     => [
                        'class' => 'mapping-field',
                        'data-sources' => json_encode($this->getAllSources()),
                        'data-targets' => json_encode($this->getAllTargets()),
                        'data-name'    => $this->name,
                    ],
                    'label' => 'pim_prestashop_connector.'.$this->direction.'.'.$this->name.'Mapping.label',
                    'help'  => 'pim_prestashop_connector.'.$this->direction.'.'.$this->name.'Mapping.help',
                ],
            ],
        ];
    }
}
