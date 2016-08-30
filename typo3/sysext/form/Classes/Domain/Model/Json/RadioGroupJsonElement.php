<?php
namespace TYPO3\CMS\Form\Domain\Model\Json;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * JSON radiogroup
 */
class RadioGroupJsonElement extends \TYPO3\CMS\Form\Domain\Model\Json\FieldsetJsonElement
{
    /**
     * The ExtJS xtype of the element
     *
     * @var string
     */
    public $xtype = 'typo3-form-wizard-elements-predefined-radiogroup';

    /**
     * The configuration array for the xtype
     *
     * @var array
     */
    public $configuration = [
        'attributes' => [],
        'legend' => [
            'value' => ''
        ],
        'options' => [],
        'various' => [
            'name' => ''
        ],
        'validation' => []
    ];

    /**
     * Allowed attributes for this object
     *
     * @var array
     */
    protected $allowedAttributes = [
        'class',
        'dir',
        'id',
        'lang',
        'style'
    ];

    /**
     * Set all the parameters for this object
     *
     * @param array $parameters Configuration array
     * @return void
     * @see \TYPO3\CMS\Form\Domain\Model\Json\FieldsetJsonElement::setParameters()
     */
    public function setParameters(array $parameters)
    {
        parent::setParameters($parameters);
        $this->setOptions($parameters);
        $this->setVarious($parameters);
    }

    /**
     * Set the options for this object
     *
     * @param array $parameters Configuration array
     * @return void
     */
    protected function setOptions(array $parameters)
    {
        if (is_array($parameters)) {
            $keys = \TYPO3\CMS\Core\TypoScript\TemplateService::sortedKeyList($parameters);
            foreach ($keys as $key) {
                $class = $parameters[$key];
                if ((int)$key && strpos($key, '.') === false) {
                    if (isset($parameters[$key . '.']) && $class === 'RADIO') {
                        $childElementArguments = $parameters[$key . '.'];
                        if (isset($childElementArguments['checked'])) {
                            $childElementArguments['attributes']['selected'] = 'selected';
                            unset($childElementArguments['checked']);
                        }
                        if (isset($childElementArguments['value'])) {
                            $childElementArguments['attributes']['value'] = $childElementArguments['value'];
                            unset($childElementArguments['value']);
                        }
                        if (isset($childElementArguments['label.'])) {
                            $childElementArguments['text'] = $childElementArguments['label.']['value'];
                            unset($childElementArguments['label.']);
                        }
                        $this->configuration['options'][] = $childElementArguments;
                    }
                }
            }
        }
    }

    /**
     * Set the various properties for this object
     *
     * @param array $parameters Configuration array
     * @return void
     */
    protected function setVarious(array $parameters)
    {
        if (isset($parameters['name'])) {
            $this->configuration['various']['name'] = $parameters['name'];
        }
    }
}
