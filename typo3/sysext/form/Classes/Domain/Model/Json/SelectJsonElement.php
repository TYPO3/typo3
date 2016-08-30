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
 * JSON select
 */
class SelectJsonElement extends \TYPO3\CMS\Form\Domain\Model\Json\AbstractJsonElement
{
    /**
     * The ExtJS xtype of the element
     *
     * @var string
     */
    public $xtype = 'typo3-form-wizard-elements-basic-select';

    /**
     * The configuration array for the xtype
     *
     * @var array
     */
    public $configuration = [
        'attributes' => [],
        'filters' => [],
        'label' => [
            'value' => ''
        ],
        'layout' => 'front',
        'options' => [],
        'validation' => []
    ];

    /**
     * Allowed attributes for this object
     *
     * @var array
     */
    protected $allowedAttributes = [
        'accesskey',
        'class',
        'contenteditable',
        'contextmenu',
        'dir',
        'draggable',
        'dropzone',
        'hidden',
        'id',
        'lang',
        'spellcheck',
        'style',
        'tabindex',
        'title',
        'translate',
        /* element specific attributes */
        'autofocus',
        'disabled',
        'multiple',
        'name',
        'required',
        'size'
    ];

    protected $childElementsAllowed = false;

    /**
     * Set all the parameters for this object
     *
     * @param array $parameters Configuration array
     * @return void
     * @see \TYPO3\CMS\Form\Domain\Model\Json\AbstractJsonElement::setParameters()
     */
    public function setParameters(array $parameters)
    {
        parent::setParameters($parameters);
        $this->setOptions($parameters);
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
                    if (isset($parameters[$key . '.']) && $class === 'OPTION') {
                        $childElementArguments = $parameters[$key . '.'];
                        if (isset($childElementArguments['selected'])) {
                            $childElementArguments['attributes']['selected'] = $childElementArguments['selected'];
                            unset($childElementArguments['selected']);
                        }
                        if (isset($childElementArguments['value'])) {
                            $childElementArguments['attributes']['value'] = $childElementArguments['value'];
                            unset($childElementArguments['value']);
                        }
                        if (isset($childElementArguments['data']) && !isset($childElementArguments['text'])) {
                            // preserve backward compatibility by rewriting data to text
                            $childElementArguments['text'] = $childElementArguments['data'];
                        }
                        $this->configuration['options'][] = $childElementArguments;
                    }
                }
            }
        }
    }
}
