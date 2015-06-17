<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Reader\ORM;

/**
 * Reads group option for attributes at once.
 *
 * @author    Julien Sanchez <gildas@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class GroupedOptionReader extends BulkEntityReader
{
    /** @var array */
    protected $groupedOptions;

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        if (!$this->groupedOptions) {
            $options = parent::read();

            if (!is_array($options)) {
                return $options;
            }

            $this->groupedOptions = $this->getGroupedOptions($options);
        }

        return is_array($this->groupedOptions) ? array_shift($this->groupedOptions) : null;
    }

    /**
     * Get grouped options.
     *
     * @param array $options
     *
     * @return array
     */
    protected function getGroupedOptions(array $options)
    {
        $groupedOptions = [];

        foreach ($options as $option) {
            $attributeCode = $option->getAttribute()->getCode();

            if (!in_array($attributeCode, $this->getIgnoredAttributes()) &&
                !($option->getAttribute()->getFamilies() === null)
            ) {
                $groupedOptions[$attributeCode] =
                    isset($groupedOptions[$attributeCode]) ?
                        array_merge($groupedOptions[$attributeCode], [$option]) :
                        [$option];
            }
        }

        return $groupedOptions;
    }

    /**
     * Get all ignored attributes.
     *
     * @return string[]
     */
    protected function getIgnoredAttributes()
    {
        return [
            'visibility',
        ];
    }
}
