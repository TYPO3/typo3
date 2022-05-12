<?php

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

namespace TYPO3\CMS\Extbase\Validation\Validator;

use TYPO3\CMS\Extbase\Error\Result;

/**
 * Validator to chain many validators in a conjunction (logical and).
 */
class ConjunctionValidator extends AbstractCompositeValidator
{
    /**
     * Checks if the given value is valid according to the validators of the conjunction.
     * Every validator has to be valid, to make the whole conjunction valid.
     *
     * @param mixed $value The value that should be validated
     * @return Result
     */
    public function validate($value)
    {
        $result = new Result();
        /** @var AbstractValidator $validator */
        foreach ($this->getValidators() as $validator) {
            $result->merge($validator->validate($value));
        }

        return $result;
    }
}
