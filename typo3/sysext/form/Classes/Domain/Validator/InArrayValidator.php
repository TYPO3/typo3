<?php
namespace TYPO3\CMS\Form\Domain\Validator;

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

use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class InArrayValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = array(
        'element' => array('', 'The name of the element', 'string', true),
        'errorMessage' => array('', 'The error message', 'array', true),
        'array' => array('', 'The array value', 'array', true),
        'strict' => array('', 'Compare types', 'boolean', false),
        'ignorecase' => array('', 'Ignore cases', 'boolean', false)
    );

    /**
     * @var CharsetConverter
     */
    protected $charsetConverter;

    /**
     * constructor
     *
     * creates charsetConverter object if option ignorecase is set
     *
     * @param array $options
     * @throws \TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
        if (!empty($this->options['ignorecase'])) {
            $this->charsetConverter = GeneralUtility::makeInstance(CharsetConverter::class);
        }
    }

    /**
     * Constant for localisation
     *
     * @var string
     */
    const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_inarray';

    /**
     * Check if $value is valid. If it is not valid, needs to add an error
     * to result.
     *
     * @param mixed $value
     * @return void
     */
    public function isValid($value)
    {
        if (empty($value) || !is_string($value)) {
            return;
        }
        $allowedOptionsArray = GeneralUtility::trimExplode(',', $this->options['array'], true);
        if (!empty($this->options['ignorecase'])) {
            $value = $this->charsetConverter->conv_case('utf-8', $value, 'toLower');
            foreach ($allowedOptionsArray as &$option) {
                $option = $this->charsetConverter->conv_case('utf-8', $option, 'toLower');
            }
        }

        if (!in_array($value, $allowedOptionsArray, !empty($this->options['strict']))) {
            $this->addError(
                $this->renderMessage(
                    $this->options['errorMessage'][0],
                    $this->options['errorMessage'][1],
                    'error'
                ),
                1442002594
            );
        }
    }
}
