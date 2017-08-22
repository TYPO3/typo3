<?php
namespace TYPO3\CMS\Extbase\Validation\Validator;

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
 * Validator for "plain" text.
 *
 * @api
 */
class TextValidator extends AbstractValidator
{
    /**
     * Checks if the given value is a valid text (contains no XML tags).
     *
     * Be aware that the value of this check entirely depends on the output context.
     * The validated text is not expected to be secure in every circumstance, if you
     * want to be sure of that, use a customized regular expression or filter on output.
     *
     * See http://php.net/filter_var for details.
     *
     * @param mixed $value The value that should be validated
     * @api
     */
    public function isValid($value)
    {
        if ($value !== filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)) {
            $this->addError(
                $this->translateErrorMessage(
                    'validator.text.notvalid',
                    'extbase'
                ),
                1221565786
            );
        }
    }
}
