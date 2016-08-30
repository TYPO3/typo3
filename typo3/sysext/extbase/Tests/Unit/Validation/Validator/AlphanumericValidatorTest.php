<?php
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

/**
 * Test case
 */
class AlphanumericValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function alphanumericValidatorShouldReturnNoErrorsForAnAlphanumericString()
    {
        /** @var \TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator::class, ['translateErrorMessage']);
        $this->assertFalse($subject->validate('12ssDF34daweidf')->hasErrors());
    }

    /**
     * @test
     */
    public function alphanumericValidatorReturnsErrorsForAStringWithSpecialCharacters()
    {
        /** @var \TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator::class, ['translateErrorMessage']);
        $this->assertTrue($subject->validate('adsf%&/$jklsfdö')->hasErrors());
    }

    /**
     * @test
     */
    public function alphanumericValidatorCreatesTheCorrectErrorForAnInvalidSubject()
    {
        /** @var \TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator::class, ['translateErrorMessage']);
        $this->assertEquals(1, count($subject->validate('adsf%&/$jklsfdö')->getErrors()));
    }

    /**
     * @test
     */
    public function alphanumericValidatorShouldReturnNoErrorsForAnAlphanumericUnicodeString()
    {
        /** @var \TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator::class, ['translateErrorMessage']);
        $this->assertFalse($subject->validate('12ssDF34daweidfäøüößØœ你好')->hasErrors());
    }
}
