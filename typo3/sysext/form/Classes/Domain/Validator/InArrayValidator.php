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
    protected $supportedOptions = [
        'element' => ['', 'The name of the element', 'string', true],
        'errorMessage' => ['', 'The error message', 'array', true],
        'array' => ['', 'The array values from the wizard configuration (array = test1,test2)', 'string', false],
        'array.' => ['', 'The array values from the documented configuration', 'array', false],
        'strict' => ['', 'Compare types', 'boolean', false],
        'ignorecase' => ['', 'Ignore cases', 'boolean', false]
    ];

    /**
     * @var CharsetConverter
     */
    protected $charsetConverter;

    /**
     * Constructor
     *
     * Creates charsetConverter object if option ignorecase is set
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
     * Check if $value is valid. If it is not valid, add an error to result.
     *
     * @param mixed $value
     * @return void
     */
    public function isValid($value)
    {
        if (empty($value)) {
            return;
        }

        /**
         * A single select results in a string,
         * a multiselect in an array.
         * In both cases, the operations will be processed on an array.
         */
        if (is_string($value)) {
            $value = [$value];
        }

        /**
         * The form wizard generates the following configuration:
         *   array = test1,test2
         * In this case the string has to be exploded.
         * The following configuration was documented:
         *   array {
         *     1 = TYPO3 4.5 LTS
         *     2 = TYPO3 6.2 LTS
         *     3 = TYPO3 7 LTS
         *   }
         * In this case there is already an array but the "options" key differs.
         */
        $allowedOptionsArray = [];
        if (!empty($this->options['array']) && is_string($this->options['array'])) {
            $allowedOptionsArray = GeneralUtility::trimExplode(',', $this->options['array'], true);
        } elseif (!empty($this->options['array.']) && is_array($this->options['array.'])) {
            $allowedOptionsArray = $this->options['array.'];
        }

        if (!empty($this->options['ignorecase'])) {
            foreach ($value as &$incomingArrayValue) {
                $incomingArrayValue = $this->charsetConverter->conv_case('utf-8', $incomingArrayValue, 'toLower');
            }
            foreach ($allowedOptionsArray as &$option) {
                $option = $this->charsetConverter->conv_case('utf-8', $option, 'toLower');
            }
        }

        foreach ($value as $incomingArrayValue) {
            if (!in_array($incomingArrayValue, $allowedOptionsArray, !empty($this->options['strict']))) {
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
}
