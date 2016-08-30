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
 */
abstract class AbstractValidatorTestcase extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    protected $validatorClassName;

    /**
     * @var \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface
     */
    protected $validator;

    protected function setUp()
    {
        $this->validator = $this->getValidator();
    }

    /**
     * @param array $options
     * @return mixed
     */
    protected function getValidator($options = [])
    {
        $validator = new $this->validatorClassName($options);
        return $validator;
    }

    /**
     * @param array $options
     */
    protected function validatorOptions($options)
    {
        $this->validator = $this->getValidator($options);
    }
}
