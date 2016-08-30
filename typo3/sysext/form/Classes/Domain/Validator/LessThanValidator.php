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

class LessThanValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'element' => ['', 'The name of the element', 'string', true],
        'errorMessage' => ['', 'The error message', 'array', true],
        'maximum' => ['', 'The maximum value', 'integer', true],
    ];

    /**
     * Constant for localisation
     *
     * @var string
     */
    const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_lessthan';

    /**
     * Check if $value is valid. If it is not valid, needs to add an error
     * to result.
     *
     * @param mixed $value
     * @return void
     */
    public function isValid($value)
    {
        if ($value >= $this->options['maximum'] || !is_numeric($value)) {
            $this->addError(
                $this->renderMessage(
                    $this->options['errorMessage'][0],
                    $this->options['errorMessage'][1],
                    'error'
                ),
                1441997981
            );
        }
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
        $message = str_replace('%maximum', $this->options['maximum'], $message);
        return $message;
    }
}
