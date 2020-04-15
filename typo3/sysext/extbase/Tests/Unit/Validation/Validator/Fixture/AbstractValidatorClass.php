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

namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\Fixture;

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Testcase for the abstract base-class of validators
 */
class AbstractValidatorClass extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'requiredOption' => [0, 'Some value', 'integer', true],
        'demoOption' => [PHP_INT_MAX, 'Some value', 'integer'],
    ];

    /**
     * Check if $value is valid. If it is not valid, needs to add an error
     * to Result.
     *
     * @param mixed $value
     */
    protected function isValid($value)
    {
        // dummy
    }
}
