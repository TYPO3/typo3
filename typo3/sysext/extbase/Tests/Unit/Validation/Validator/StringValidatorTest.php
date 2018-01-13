<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

/*                                                                        *
 * This script belongs to the Extbase framework.                          *
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

use TYPO3\CMS\Extbase\Validation\Validator\StringValidator;

/**
 * Testcase for the string length validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class StringValidatorTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @test
     */
    public function stringValidatorShouldValidateString()
    {
        $this->assertFalse((new StringValidator())->validate('Hello World')->hasErrors());
    }

    /**
     * @test
     */
    public function stringValidatorShouldReturnErrorIfNumberIsGiven()
    {
        /** @var StringValidator $validator */
        $validator = $this->getMockBuilder(StringValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->getMock();

        $this->assertTrue($validator->validate(42)->hasErrors());
    }

    /**
     * @test
     */
    public function stringValidatorShouldReturnErrorIfObjectWithToStringMethodStringIsGiven()
    {
        /** @var StringValidator $validator */
        $validator = $this->getMockBuilder(StringValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->getMock();

        $object = new class() {
            /** @return string */
            public function __toString()
            {
                return 'ASDF';
            }
        };

        $this->assertTrue($validator->validate($object)->hasErrors());
    }
}
