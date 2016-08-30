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
 * JSON fieldset
 */
class FieldsetJsonElement extends \TYPO3\CMS\Form\Domain\Model\Json\ContainerJsonElement
{
    /**
     * The ExtJS xtype of the element
     *
     * @var string
     */
    public $xtype = 'typo3-form-wizard-elements-basic-fieldset';

    /**
     * The configuration array for the xtype
     *
     * @var array
     */
    public $configuration = [
        'attributes' => [],
        'legend' => [
            'value' => ''
        ]
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
     * @see \TYPO3\CMS\Form\Domain\Model\Json\AbstractJsonElement::setParameters()
     */
    public function setParameters(array $parameters)
    {
        parent::setParameters($parameters);
        $this->setLegend($parameters);
    }

    /**
     * Set the legend for the element
     *
     * @param array $parameters Configuration array
     * @return void
     */
    protected function setLegend(array $parameters)
    {
        if (isset($parameters['legend']) && !isset($parameters['legend.'])) {
            $this->configuration['legend']['value'] = $parameters['legend'];
        } elseif (!isset($parameters['legend']) && isset($parameters['legend.'])) {
            $this->configuration['legend']['value'] = $parameters['legend.']['value'];
        }
    }
}
