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
     * @return \TYPO3\CMS\Extbase\Error\Result
     * @todo: Return type 'Result' will be added in v12. Extensions should add this for v11 & v12 compatible
     *        extensions if they override validate(). AbstractValidator will add 'Result' return in v12.
     * @todo: Argument signature will be 'mixed $value' in v12, but AbstractValidator adds this starting
     *        with v13 only to simplify compat for extensions supporting v11 & v12 and thus PHP < 8.1.
     */
    public function validate($value);

    /**
     * Receive validator options from framework.
     *
     * @todo: Will be activated in v12 and implemented in AbstractValidator. Extensions *may* implement
     *        this for v10 & v11 compatible extensions *if* they need dependency injection in v11. If extending AbstractValidator
     *        in v11, a setOptions() implementation should call initializeDefaultOptions(), which will be done in AbstractValidator
     *        v12 automatically.
     */
    // public function setOptions(array $options): void;

    /**
     * Returns the options of this validator which can be specified in the constructor
     *
     * @return array
     * @todo: Return type 'array' will be added in v12. Extensions should add this for v11 & v12 compatible
     *        extensions if they override getOptions(). AbstractValidator will add 'Result' return in v12.
     */
    public function getOptions();
}
