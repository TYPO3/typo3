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

class LengthValidator extends AbstractValidator
{
    /**
     * TYPO3 charset encoding object
     *
     * @var \TYPO3\CMS\Core\Charset\CharsetConverter
     */
    protected $charsetConverter = null;

    /**
     * @param \TYPO3\CMS\Core\Charset\CharsetConverter $charsetConverter
     * @return void
     */
    public function injectCharsetConverter(\TYPO3\CMS\Core\Charset\CharsetConverter $charsetConverter)
    {
        $this->charsetConverter = $charsetConverter;
    }

    /**
     * @var array
     */
    protected $supportedOptions = [
        'element' => ['', 'The name of the element', 'string', true],
        'errorMessage' => ['', 'The error message', 'array', true],
        'minimum' => ['', 'The minimum value', 'integer', true],
        'maximum' => ['', 'The maximum value', 'integer', false],
    ];

    /**
     * Constant for localisation
     *
     * @var string
     */
    const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_length';

    /**
     * Check if $value is valid. If it is not valid, needs to add an error
     * to result.
     *
     * @param mixed $value
     * @return void
     */
    public function isValid($value)
    {
        $length = $this->charsetConverter->strlen('utf-8', $value);
        if ($length < (int)$this->options['minimum']) {
            $this->addError(
                $this->renderMessage(
                    $this->options['errorMessage'][0],
                    $this->options['errorMessage'][1],
                    'error'
                ),
                1441999425
            );
            return;
        }
        if (
            !isset($this->options['maximum'])
            || $this->options['maximum'] === ''
        ) {
            $this->options['maximum'] = null;
        }
        if (
            $this->options['maximum'] !== null
            && $length > (int)$this->options['maximum']
        ) {
            $this->addError(
                $this->renderMessage(
                    $this->options['errorMessage'][0],
                    $this->options['errorMessage'][1],
                    'error'
                ),
                1441999425
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
        $label = static::LOCALISATION_OBJECT_NAME . '.' . $type;
        $messages[] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label, 'form');
        if ($this->options['maximum'] !== null) {
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
