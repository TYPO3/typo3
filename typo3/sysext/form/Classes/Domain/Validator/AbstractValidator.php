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

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Form\Utility\FormUtility;

abstract class AbstractValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator
{
    /**
     * Constant for localisation
     *
     * @var string
     */
    const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate';

    /**
     * @var FormUtility
     */
    protected $formUtility;

    /**
     * @var mixed
     */
    protected $rawArgument;

    /**
     * @var array
     */
    protected $supportedOptions = [
        'element' => ['', 'The name of the element', 'string', true],
        'errorMessage' => ['', 'The error message', 'array', true],
    ];

    /**
     * This validator always needs to be executed even if the given value is empty.
     * See AbstractValidator::validate()
     *
     * @var bool
     */
    protected $acceptsEmptyValues = false;

    /**
     * @param mixed $rawArgument
     */
    public function setRawArgument($rawArgument)
    {
        $this->rawArgument = $rawArgument;
    }

    /**
     * @param FormUtility $formUtility
     */
    public function setFormUtility(FormUtility $formUtility)
    {
        $this->formUtility = $formUtility;
    }

    /**
     * Substitute makers in the message text
     * In some cases this method will be override by rule class
     *
     * @param string $message Message text with markers
     * @return string Message text with substituted markers
     */
    public function substituteMarkers($message)
    {
        return $message;
    }

    /**
     * Get the local language label(s) for the message
     * In some cases this method will be override by rule class
     *
     * @param string $type The type
     * @return string The local language message label
     */
    public function getLocalLanguageLabel($type = '')
    {
        $label = static::LOCALISATION_OBJECT_NAME . '.' . $type;
        $message = LocalizationUtility::translate($label, 'form');
        return $message;
    }

    /**
     * Set the message, like 'required' for the validation rule
     * and substitutes markers for values, like %maximum
     *
     *
     * @param mixed $message Message as string or TS
     * @param NULL|string $type Name of the cObj
     * @param string $messageType message or error
     * @return string
     */
    public function renderMessage($message = null, $type = null, $messageType = 'message')
    {
        $message = $this->formUtility->renderItem(
            $message,
            $type,
            $this->getLocalLanguageLabel($messageType)
        );
        return $this->substituteMarkers($message);
    }
}
