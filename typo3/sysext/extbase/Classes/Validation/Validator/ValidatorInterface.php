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

use TYPO3\CMS\Extbase\Error\Result;

/**
 * Contract for a validator
 */
interface ValidatorInterface
{
    /**
     * Checks if the given value is valid according to the validator, and returns
     * the Error Messages object which occurred.
     *
     * @param mixed $value The value that should be validated
     */
    public function validate(mixed $value): Result;

    /**
     * Receive validator options from framework.
     */
    public function setOptions(array $options): void;

    /**
     * Returns the options of this validator which can be specified by setOptions().
     */
    public function getOptions(): array;
}
