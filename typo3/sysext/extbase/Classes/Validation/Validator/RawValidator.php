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
 * A validator which accepts any input.
 *
 * @api
 */
class RawValidator extends AbstractValidator
{
    /**
     * This validator is always valid.
     *
     * @param mixed $value The value that should be validated (not used here)
     * @return void
     * @api
     */
    public function isValid($value)
    {
    }
}
