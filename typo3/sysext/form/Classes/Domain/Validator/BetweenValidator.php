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

class BetweenValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'element' => ['', 'The name of the element', 'string', true],
        'errorMessage' => ['', 'The error message', 'array', true],
        'minimum' => ['', 'The minimum value', 'integer', true],
        'maximum' => ['', 'The maximum value', 'integer', true],
        'inclusive' => ['', 'Minimum and maximum value are inclusive in comparison', 'integer', false],
    ];

    /**
     * Constant for localisation
     *
     * @var string
     */
    const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_between';

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
            !isset($this->options['inclusive'])
            || $this->options['inclusive'] === ''
            || (int)$this->options['inclusive'] === 0
        ) {
            if ($value <= $this->options['minimum'] || $value >= $this->options['maximum']) {
                $this->addError(
                    $this->renderMessage(
                        $this->options['errorMessage'][0],
                        $this->options['errorMessage'][1],
                        'error'
                    ),
                    1442003544
                );
            }
        } else {
            if ($value < $this->options['minimum'] || $value > $this->options['maximum']) {
                $this->addError(
                    $this->renderMessage(
                        $this->options['errorMessage'][0],
                        $this->options['errorMessage'][1],
                        'error'
                    ),
                    1442003545
                );
            }
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
        $label = static::LOCALISATION_OBJECT_NAME . '.' . $type;
        $messages[] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label, 'form');
        if ($this->inclusive) {
            $messages[] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label . 2, 'form');
        }
        $message = implode(', ', $messages);
        return $message;
    }

    /**
     * Substitute makers in the message text
     * Overrides the abstract
     *
     * @param string $message Message text with markers
     * @return string Message text with substituted markers
     */
    public function substituteMarkers($message)
    {
        return str_replace(
            ['%minimum', '%maximum'],
            [$this->options['minimum'], $this->options['maximum']],
            $message
        );
    }
}
