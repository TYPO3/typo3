<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

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
 * Test case for the Abstract Validator
 * @deprecated
 */
abstract class AbstractValidatorTestcase extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @var string
     */
    protected $validatorClassName;

    /**
     * @var \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface
     */
    protected $validator;

    /**
     * @deprecated
     */
    protected function setUp()
    {
        $this->validator = $this->getValidator();
    }

    /**
     * @param array $options
     * @return mixed
     * @deprecated
     */
    protected function getValidator($options = [])
    {
        trigger_error(
            __CLASS__ . ' is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );

        $validator = new $this->validatorClassName($options);
        return $validator;
    }

    /**
     * @param array $options
     * @deprecated
     */
    protected function validatorOptions($options)
    {
        trigger_error(
            __CLASS__ . ' is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );

        $this->validator = $this->getValidator($options);
    }
}
