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
 * JSON textarea
 */
class TextareaJsonElement extends \TYPO3\CMS\Form\Domain\Model\Json\AbstractJsonElement
{
    /**
     * The ExtJS xtype of the element
     *
     * @var string
     */
    public $xtype = 'typo3-form-wizard-elements-basic-textarea';

    /**
     * The configuration array for the xtype
     *
     * @var array
     */
    public $configuration = [
        'attributes' => [
            'cols' => 40,
            'rows' => 5
        ],
        'filters' => [],
        'label' => [
            'value' => ''
        ],
        'layout' => 'front',
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
        'cols',
        'disabled',
        'inputmode',
        'maxlength',
        'minlength',
        'name',
        'placeholder',
        'readonly',
        'required',
        'rows',
        'selectionDirection',
        'selectionEnd',
        'selectionStart',
        'text',
        'wrap'
    ];

    /**
     * Set the attributes according to the allowed attributes of this element
     *
     * @param array $parameters Configuration array
     * @return void
     */
    protected function setAttributes(array $parameters)
    {
        // preserve backward compatibility by rewriting data to text
        if (isset($parameters['data'])) {
            $this->configuration['attributes']['text'] = $parameters['data'];
        }
        parent::setAttributes($parameters);
    }
}
