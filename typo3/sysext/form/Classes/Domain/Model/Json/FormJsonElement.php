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
 * JSON form
 */
class FormJsonElement extends \TYPO3\CMS\Form\Domain\Model\Json\ContainerJsonElement
{
    /**
     * The ExtJS xtype of the element
     *
     * @var string
     */
    public $xtype = 'typo3-form-wizard-elements-basic-form';

    /**
     * The configuration array for the xtype
     *
     * @var array
     */
    public $configuration = [
        'attributes' => [],
        'prefix' => 'tx_form',
        'confirmation' => true,
        'postProcessor' => []
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
        'accept',
        'accept-charset',
        'action',
        'autocomplete',
        'enctype',
        'method',
        'novalidate'
    ];

    /**
     * Set all the parameters for this object
     *
     * @param array $parameters Configuration array
     * @return void
     * @see \TYPO3\CMS\Form\Domain\Model\Json\ContainerJsonElement::setParameters()
     */
    public function setParameters(array $parameters)
    {
        parent::setParameters($parameters);
        $this->setPrefix($parameters);
        $this->setConfirmation($parameters);
        $this->setPostProcessors($parameters);
    }

    /**
     * Set the confirmation message boolean
     *
     * @param array $parameters Configuration array
     * @return void
     */
    protected function setConfirmation(array $parameters)
    {
        if (isset($parameters['confirmation'])) {
            $this->configuration['confirmation'] = $parameters['confirmation'];
        }
    }

    /**
     * Set the post processors and their configuration
     *
     * @param array $parameters Configuration array
     * @return void
     */
    protected function setPostProcessors(array $parameters)
    {
        if (isset($parameters['postProcessor.']) && is_array($parameters['postProcessor.'])) {
            $postProcessors = $parameters['postProcessor.'];
            foreach ($postProcessors as $key => $postProcessorName) {
                if ((int)$key && strpos($key, '.') === false) {
                    $postProcessorConfiguration = [];
                    if (isset($postProcessors[$key . '.'])) {
                        $postProcessorConfiguration = $postProcessors[$key . '.'];
                    }
                    $this->configuration['postProcessor'][$postProcessorName] = $postProcessorConfiguration;
                }
            }
        } else {
            $this->configuration['postProcessor'] = [
                'mail' => [
                    'recipientEmail' => '',
                    'senderEmail' => ''
                ]
            ];
        }
    }

    /**
     * Set the prefix
     *
     * @param array $parameters Configuration array
     * @return void
     */
    protected function setPrefix(array $parameters)
    {
        if (isset($parameters['prefix'])) {
            $this->configuration['prefix'] = $parameters['prefix'];
        }
    }
}
