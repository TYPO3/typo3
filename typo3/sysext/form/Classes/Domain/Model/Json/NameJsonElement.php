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
 * JSON name
 */
class NameJsonElement extends \TYPO3\CMS\Form\Domain\Model\Json\FieldsetJsonElement
{
    /**
     * The ExtJS xtype of the element
     *
     * @var string
     */
    public $xtype = 'typo3-form-wizard-elements-predefined-name';

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
        'various' => [
            'prefix' => false,
            'suffix' => false,
            'middleName' => false
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
     * @see \TYPO3\CMS\Form\Domain\Model\Json\FieldsetJsonElement::setParameters()
     */
    public function setParameters(array $parameters)
    {
        parent::setParameters($parameters);
        $this->setVarious($parameters);
    }

    /**
     * Set the various properties for the element
     *
     * For this element this is the prefix, suffix and middleName if they will
     * be shown in the form
     *
     * @param array $parameters Configuration array
     * @return void
     */
    protected function setVarious(array $parameters)
    {
        if (is_array($parameters)) {
            $keys = \TYPO3\CMS\Core\TypoScript\TemplateService::sortedKeyList($parameters);
            foreach ($keys as $key) {
                $class = $parameters[$key];
                if ((int)$key && strpos($key, '.') === false) {
                    if (isset($parameters[$key . '.'])) {
                        $childElementArguments = $parameters[$key . '.'];
                        if (in_array($childElementArguments['name'], ['prefix', 'suffix', 'middleName'])) {
                            $this->configuration['various'][$childElementArguments['name']] = true;
                        }
                    }
                }
            }
        }
    }
}
