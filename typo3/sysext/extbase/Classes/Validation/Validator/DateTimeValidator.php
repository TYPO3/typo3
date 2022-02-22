<?php

declare(strict_types=1);

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

/**
 * Validator for DateTime/DateTimeImmutable objects.
 */
final class DateTimeValidator extends AbstractValidator
{
    /**
     * Checks if the given value is a valid DateTime object. If this is not
     * the case, the function adds an error.
     */
    public function isValid(mixed $value): void
    {
        $this->result->clear();
        if ($value instanceof \DateTimeInterface) {
            return;
        }
        $this->addError(
            $this->translateErrorMessage(
                'validator.datetime.notvalid',
                'extbase',
                [
                    gettype($value),
                ]
            ),
            1238087674,
            [gettype($value)]
        );
    }
}
