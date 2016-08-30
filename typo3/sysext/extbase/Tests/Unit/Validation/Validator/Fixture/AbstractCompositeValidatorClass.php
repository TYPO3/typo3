<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\Fixture;

/*                                                                        *
 * This script belongs to the Extbase framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the abstract base-class of vvalidators
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AbstractCompositeValidatorClass extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractCompositeValidator
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
     * @return void
     */
    protected function isValid($value)
    {
        // dummy
    }

    /**
     * Checks if the given value is valid according to the validator, and returns
     * the Error Messages object which occurred.
     *
     * @param mixed $value The value that should be validated
     * @return \TYPO3\CMS\Extbase\Error\Result
     * @api
     */
    public function validate($value)
    {
        return new \TYPO3\CMS\Extbase\Error\Result();
    }
}
