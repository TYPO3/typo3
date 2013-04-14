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
require_once __DIR__ . '/AbstractValidatorTestcase.php';

/**
 * Testcase for the text validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TextValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\TextValidator';

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function textValidatorReturnsNoErrorForASimpleString() {
		$this->assertFalse($this->validator->validate('this is a very simple string')->hasErrors());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function textValidatorAllowsTheNewLineCharacter() {
		$sampleText = 'Ierd Frot uechter mä get, Kirmesdag Milliounen all en, sinn main Stréi mä och. nVu dan durch jéngt gréng, ze rou Monn voll stolz. nKe kille Minutt d\'Kirmes net. Hir Wand Lann Gaas da, wär hu Heck Gart zënter, Welt Ronn grousse der ke. Wou fond eraus Wisen am. Hu dénen d\'Gaassen eng, eng am virun geplot d\'Lëtzebuerger, get botze rëscht Blieder si. Dat Dauschen schéinste Milliounen fu. Ze riede méngem Keppchen déi, si gét fergiess erwaacht, räich jéngt duerch en nun. Gëtt Gaas d\'Vullen hie hu, laacht Grénge der dé. Gemaacht gehéiert da aus, gutt gudden d\'wäiss mat wa.';
		$this->assertFalse($this->validator->validate($sampleText)->hasErrors());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function textValidatorAllowsCommonSpecialCharacters() {
		$sampleText = '3% of most people tend to use semikolae; we need to check & allow that. And hashes (#) are not evil either, nor is the sign called \'quote\'.';
		$this->assertFalse($this->validator->validate($sampleText)->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function textValidatorReturnsErrorForAStringWithHtml() {
		$this->assertTrue($this->validator->validate('<span style="color: #BBBBBB;">a nice text</span>')->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function textValidatorCreatesTheCorrectErrorIfTheSubjectContainsHtmlEntities() {
		$expected = array(new \TYPO3\CMS\Extbase\Validation\Error('The given subject was not a valid text (e.g. contained XML tags).', 1221565786));
		$this->assertEquals($expected, $this->validator->validate('<span style="color: #BBBBBB;">a nice text</span>')->getErrors());
	}
}

?>