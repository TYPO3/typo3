<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Testcase for the text validator
 *
 * @package Extbase
 * @subpackage extbase
 * @version $Id: TextValidator_testcase.php 1408 2009-10-08 13:15:09Z jocrau $
 */
class Tx_Extbase_Validation_Validator_TextValidator_testcase extends Tx_Extbase_BaseTestCase {

	/**
	 * @test
	 */
	public function textValidatorReturnsTrueForASimpleString() {
		$textValidator = new Tx_Extbase_Validation_Validator_TextValidator();
		$this->assertTrue($textValidator->isValid('this is a very simple string'));
	}

	/**
	 * @test
	 */
	public function textValidatorAllowsTheNewLineCharacter() {
		$sampleText = "Ierd Frot uechter mÃ¤ get, Kirmesdag Milliounen all en, sinn main StrÃ©i mÃ¤ och. \nVu dan durch jÃ©ngt grÃ©ng, ze rou Monn voll stolz. \nKe kille Minutt d'Kirmes net. Hir Wand Lann Gaas da, wÃ¤r hu Heck Gart zÃ«nter, Welt Ronn grousse der ke. Wou fond eraus Wisen am. Hu dÃ©nen d'Gaassen eng, eng am virun geplot d'LÃ«tzebuerger, get botze rÃ«scht Blieder si. Dat Dauschen schÃ©inste Milliounen fu. Ze riede mÃ©ngem Keppchen dÃ©i, si gÃ©t fergiess erwaacht, rÃ¤ich jÃ©ngt duerch en nun. GÃ«tt Gaas d'Vullen hie hu, laacht GrÃ©nge der dÃ©. Gemaacht gehÃ©iert da aus, gutt gudden d'wÃ¤iss mat wa.";
		$textValidator = $this->getMock('Tx_Extbase_Validation_Validator_TextValidator', array('addError'), array(), '', FALSE);
		$this->assertTrue($textValidator->isValid($sampleText));
	}

	/**
	 * @test
	 */
	public function textValidatorAllowsCommonSpecialCharacters() {
		$sampleText = "3% of most people tend to use semikolae; we need to check & allow that. And hashes (#) are not evil either, nor is the sign called 'quote'.";
		$textValidator = $this->getMock('Tx_Extbase_Validation_Validator_TextValidator', array('addError'), array(), '', FALSE);
		$this->assertTrue($textValidator->isValid($sampleText));
	}

	/**
	 * @test
	 */
	public function textValidatorReturnsFalseForAStringWithHtml() {
		$textValidator = $this->getMock('Tx_Extbase_Validation_Validator_TextValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($textValidator->isValid('<span style="color: #BBBBBB;">a nice text</span>'));
	}

	/**
	 * @test
	 */
	public function textValidatorReturnsFalseForAStringWithPercentEncodedHtml() {
		$this->markTestIncomplete('The text validator currently allows percent encoded HTML!');
		$textValidator = $this->getMock('Tx_Extbase_Validation_Validator_TextValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($textValidator->isValid('%3cspan style="color: #BBBBBB;"%3ea nice text%3c/span%3e'));
	}

	/**
	 * @test
	 */
	public function textValidatorCreatesTheCorrectErrorIfTheSubjectContainsHtmlEntities() {
		$textValidator = $this->getMock('Tx_Extbase_Validation_Validator_TextValidator', array('addError'), array(), '', FALSE);
		$textValidator->expects($this->once())->method('addError')->with('The given subject was not a valid text (e.g. contained XML tags).', 1221565786);
		$textValidator->isValid('<span style="color: #BBBBBB;">a nice text</span>');
	}
}

?>