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

class DigitValidator extends AbstractValidator
{
    /**
     * Constant for localisation
     *
     * @var string
     */
    const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_digit';

    /**
     * Check if $value is valid. If it is not valid, needs to add an error
     * to result.
     *
     * @param mixed $value
     * @return void
     */
    public function isValid($value)
    {
        $compareValue = preg_replace('/[^0-9]/', '', (string)$value);
        if ($compareValue !== $value) {
            $this->addError(
                $this->renderMessage(
                    $this->options['errorMessage'][0],
                    $this->options['errorMessage'][1],
                    'error'
                ),
                1442000838
            );
        }
    }
}
