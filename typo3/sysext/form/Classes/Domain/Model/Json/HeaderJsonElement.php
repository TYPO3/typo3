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
 * JSON header
 */
class HeaderJsonElement extends \TYPO3\CMS\Form\Domain\Model\Json\AbstractJsonElement
{
    /**
     * The ExtJS xtype of the element
     *
     * @var string
     */
    public $xtype = 'typo3-form-wizard-elements-content-header';

    /**
     * The configuration array for the xtype
     *
     * @var array
     */
    public $configuration = [
        'attributes' => [],
        'various' => [
            'headingSize' => 'h1',
            'content' => ''
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
        'style',
        'title'
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
        $this->setVarious($parameters);
    }

    /**
     * Set the various properties for the element
     *
     * For this element this is the headingsize and the value
     *
     * @param array $parameters Configuration array
     * @return void
     */
    protected function setVarious(array $parameters)
    {
        if (isset($parameters['headingSize'])) {
            if (preg_match('#^h[1-5]$#', $parameters['headingSize'])) {
                $this->configuration['various']['headingSize'] = $parameters['headingSize'];
            }
        }
        if (isset($parameters['content'])) {
            $this->configuration['various']['content'] = $parameters['content'];
        }
    }
}
