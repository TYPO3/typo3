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

class AlphabeticValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'element' => ['', 'The name of the element', 'string', true],
        'errorMessage' => ['', 'The error message', 'array', true],
        'allowWhiteSpace' => ['', 'Whitespaces are allowed', 'boolean', false],
    ];

    /**
     * Constant for localisation
     *
     * @var string
     */
    const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_alphabetic';

    /**
     * Check if $value is valid. If it is not valid, needs to add an error
     * to result.
     *
     * @param mixed $value
     * @return void
     */
    public function isValid($value)
    {
        if (
            !isset($this->options['allowWhiteSpace'])
            || $this->options['allowWhiteSpace'] === ''
            || (int)$this->options['allowWhiteSpace'] === 0
        ) {
            $this->options['allowWhiteSpace'] = false;
        } else {
            $this->options['allowWhiteSpace'] = true;
        }

        $whiteSpace = $this->options['allowWhiteSpace'] ? '\\s' : '';
        $pattern = '/[^\pL' . $whiteSpace . ']/u';
        $compareValue = preg_replace($pattern, '', (string)$value);

        if ($compareValue !== $value) {
            $this->addError(
                $this->renderMessage(
                    $this->options['errorMessage'][0],
                    $this->options['errorMessage'][1],
                    'error'
                ),
                1442004245
            );
        }
    }

    /**
     * Get the local language label(s) for the message
     * Overrides the abstract
     *
     * @param string $type The type
     * @return string The local language message label
     * @see \TYPO3\CMS\Form\Validation\AbstractValidator::_getLocalLanguageLabel()
     */
    public function getLocalLanguageLabel($type = '')
    {
        $label = static::LOCALISATION_OBJECT_NAME . '.message';
        $messages[] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label, 'form');
        if ($this->options['allowWhiteSpace']) {
            $messages[] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label . 2, 'form');
        }
        $message = implode(', ', $messages);
        return $message;
    }
}
