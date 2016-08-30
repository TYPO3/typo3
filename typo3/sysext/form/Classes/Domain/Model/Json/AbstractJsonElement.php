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
 * JSON element abstract
 */
class AbstractJsonElement
{
    /**
     * The ExtJS xtype of the element
     *
     * @var string
     */
    public $xtype = '';

    /**
     * The configuration array for the xtype
     *
     * @var array
     */
    public $configuration = [];

    /**
     * Allowed attributes for this object
     *
     * @var array
     */
    protected $allowedAttributes = [];

    /**
     * Child elements allowed withing this element
     *
     * Some elements like select handle their own child elements
     *
     * @var bool
     */
    protected $childElementsAllowed = true;

    /**
     * Set all the parameters for this object
     *
     * @param array $parameters Configuration array
     * @return void
     */
    public function setParameters(array $parameters)
    {
        foreach ($this->configuration as $key => $value) {
            switch ($key) {
                case 'attributes':
                    $this->setAttributes($parameters);
                    break;
                case 'filters':
                    $this->setFilters($parameters);
                    break;
                case 'label':
                    $this->setLabel($parameters);
                    break;
                case 'layout':
                    $this->setLayout($parameters);
                    break;
                case 'validation':
                    $this->setValidation($parameters);
                    break;
                }
        }
    }

    /**
     * Check if child elements are allowed within this element
     *
     * @return bool TRUE if allowed
     */
    public function childElementsAllowed()
    {
        return $this->childElementsAllowed;
    }

    /**
     * Set the attributes according to the allowed attributes of this element
     *
     * @param array $parameters Configuration array
     * @return void
     */
    protected function setAttributes(array $parameters)
    {
        foreach ($this->allowedAttributes as $allowedAttribute) {
            if (isset($parameters[$allowedAttribute])) {
                $this->configuration['attributes'][$allowedAttribute] = $parameters[$allowedAttribute];
            } elseif (!isset($this->configuration['attributes'][$allowedAttribute])) {
                $this->configuration['attributes'][$allowedAttribute] = '';
            }
        }
    }

    /**
     * Set the filters of the element
     *
     * @param array $parameters Configuration array
     * @return void
     */
    protected function setFilters(array $parameters)
    {
        if (isset($parameters['filters.']) && is_array($parameters['filters.'])) {
            $filters = $parameters['filters.'];
            foreach ($filters as $key => $filterName) {
                if ((int)$key && strpos($key, '.') === false) {
                    $filterConfiguration = [];
                    if (isset($filters[$key . '.'])) {
                        $filterConfiguration = $filters[$key . '.'];
                    }
                    $this->configuration['filters'][$filterName] = $filterConfiguration;
                }
            }
        } else {
            $this->configuration['filters'] = new \stdClass();
        }
    }

    /**
     * Set the label of the element
     *
     * @param array $parameters Configuration array
     * @return void
     */
    protected function setLabel(array $parameters)
    {
        if (isset($parameters['label']) && !isset($parameters['label.'])) {
            $this->configuration['label']['value'] = $parameters['label'];
        } elseif (!isset($parameters['label']) && isset($parameters['label.'])) {
            $this->configuration['label']['value'] = $parameters['label.']['value'];
        }
    }

    /**
     * Set the layout of the element
     *
     * @param array $parameters Configuration array
     * @return void
     */
    protected function setLayout(array $parameters)
    {
        if (isset($parameters['layout'])) {
            if ($this->configuration['layout'] === 'front') {
                $this->configuration['layout'] = 'back';
            } else {
                $this->configuration['layout'] = 'front';
            }
        }
    }

    /**
     * Set the validation rules for the element
     *
     * @param array $parameters Configuration array
     * @return void
     */
    protected function setValidation(array $parameters)
    {
        if (isset($parameters['validation']) && is_array($parameters['validation'])) {
            $this->configuration['validation'] = $parameters['validation'];
        } else {
            $this->configuration['validation'] = new \stdClass();
        }
    }
}
