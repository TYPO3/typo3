<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\BeforeExtbase14;

/***************************************************************
 *  Copyright notice
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Testcase for the text validator.
 *
 * This testcase checks the expected behavior for Extbase < 1.4.0, to make sure
 * we do not break backwards compatibility.
 */
class TextValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 */
	public function textValidatorReturnsTrueForASimpleString() {
		$textValidator = new \TYPO3\CMS\Extbase\Validation\Validator\TextValidator();
		$this->assertTrue($textValidator->isValid('this is a very simple string'));
	}

	/**
	 * @test
	 */
	public function textValidatorAllowsTheNewLineCharacter() {
		$sampleText = 'Ierd Frot uechter mÃ¤ get, Kirmesdag Milliounen all en, sinn main StrÃ©i mÃ¤ och.
Vu dan durch jÃ©ngt grÃ©ng, ze rou Monn voll stolz.
Ke kille Minutt d\'Kirmes net. Hir Wand Lann Gaas da, wÃ¤r hu Heck Gart zÃ«nter, Welt Ronn grousse der ke. Wou fond eraus Wisen am. Hu dÃ©nen d\'Gaassen eng, eng am virun geplot d\'LÃ«tzebuerger, get botze rÃ«scht Blieder si. Dat Dauschen schÃ©inste Milliounen fu. Ze riede mÃ©ngem Keppchen dÃ©i, si gÃ©t fergiess erwaacht, rÃ¤ich jÃ©ngt duerch en nun. GÃ«tt Gaas d\'Vullen hie hu, laacht GrÃ©nge der dÃ©. Gemaacht gehÃ©iert da aus, gutt gudden d\'wÃ¤iss mat wa.';
		$textValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\TextValidator', array('addError'), array(), '', FALSE);
		$this->assertTrue($textValidator->isValid($sampleText));
	}

	/**
	 * @test
	 */
	public function textValidatorAllowsCommonSpecialCharacters() {
		$sampleText = '3% of most people tend to use semikolae; we need to check & allow that. And hashes (#) are not evil either, nor is the sign called \'quote\'.';
		$textValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\TextValidator', array('addError'), array(), '', FALSE);
		$this->assertTrue($textValidator->isValid($sampleText));
	}

	/**
	 * @test
	 */
	public function textValidatorReturnsFalseForAStringWithHtml() {
		$textValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\TextValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($textValidator->isValid('<span style="color: #BBBBBB;">a nice text</span>'));
	}

	/**
	 * @test
	 */
	public function textValidatorCreatesTheCorrectErrorIfTheSubjectContainsHtmlEntities() {
		$textValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\TextValidator', array('addError'), array(), '', FALSE);
		$textValidator->expects($this->once())->method('addError')->with('The given subject was not a valid text (e.g. contained XML tags).', 1221565786);
		$textValidator->isValid('<span style="color: #BBBBBB;">a nice text</span>');
	}
}

?>