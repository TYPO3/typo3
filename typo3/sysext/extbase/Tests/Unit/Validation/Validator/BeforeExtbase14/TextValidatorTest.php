<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\BeforeExtbase14;

/**
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

/**
 * Test case
 *
 * This testcase checks the expected behavior for Extbase < 1.4.0, to make sure
 * we do not break backwards compatibility.
 */
class TextValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function textValidatorReturnsTrueForASimpleString() {
		$textValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\TextValidator', array('addError'), array(), '', FALSE);
		$textValidator->expects($this->never())->method('addError');
		$textValidator->isValid('this is a very simple string');
	}

	/**
	 * @test
	 */
	public function textValidatorAllowsTheNewLineCharacter() {
		$sampleText = 'Ierd Frot uechter mÃ¤ get, Kirmesdag Milliounen all en, sinn main StrÃ©i mÃ¤ och.
Vu dan durch jÃ©ngt grÃ©ng, ze rou Monn voll stolz.
Ke kille Minutt d\'Kirmes net. Hir Wand Lann Gaas da, wÃ¤r hu Heck Gart zÃ«nter, Welt Ronn grousse der ke. Wou fond eraus Wisen am. Hu dÃ©nen d\'Gaassen eng, eng am virun geplot d\'LÃ«tzebuerger, get botze rÃ«scht Blieder si. Dat Dauschen schÃ©inste Milliounen fu. Ze riede mÃ©ngem Keppchen dÃ©i, si gÃ©t fergiess erwaacht, rÃ¤ich jÃ©ngt duerch en nun. GÃ«tt Gaas d\'Vullen hie hu, laacht GrÃ©nge der dÃ©. Gemaacht gehÃ©iert da aus, gutt gudden d\'wÃ¤iss mat wa.';
		$textValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\TextValidator', array('addError'), array(), '', FALSE);
		$textValidator->expects($this->never())->method('addError');
		$textValidator->isValid($sampleText);
	}

	/**
	 * @test
	 */
	public function textValidatorAllowsCommonSpecialCharacters() {
		$sampleText = '3% of most people tend to use semikolae; we need to check & allow that. And hashes (#) are not evil either, nor is the sign called \'quote\'.';
		$textValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\TextValidator', array('addError'), array(), '', FALSE);
		$textValidator->expects($this->never())->method('addError');
		$textValidator->isValid($sampleText);
	}

	/**
	 * @test
	 */
	public function textValidatorReturnsFalseForAStringWithHtml() {
		$textValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\TextValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		$textValidator->expects($this->once())->method('addError');
		$textValidator->isValid('<span style="color: #BBBBBB;">a nice text</span>');
	}

	/**
	 * @test
	 */
	public function textValidatorCreatesTheCorrectErrorIfTheSubjectContainsHtmlEntities() {
		$textValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\TextValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		// we only test for the error key, after the translation method is mocked.
		$textValidator->expects($this->once())->method('addError')->with(NULL, 1221565786);
		$textValidator->isValid('<span style="color: #BBBBBB;">a nice text</span>');
	}
}
