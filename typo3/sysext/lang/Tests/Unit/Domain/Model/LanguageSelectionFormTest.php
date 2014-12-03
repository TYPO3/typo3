<?php

namespace TYPO3\CMS\Lang\Tests\Unit\Domain\Model;
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
 */
class LanguageSelectionFormTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Lang\Domain\Model\LanguageSelectionForm
	 */
	protected $fixture = NULL;

	/**
	 * Set up
	 */
	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Lang\Domain\Model\LanguageSelectionForm();
	}

	/**
	 * @test
	 */
	public function getLanguagesInitiallyReturnsEmptyArray() {
		$this->assertSame(
			array(),
			$this->fixture->getLanguages()
		);
	}

	/**
	 * @test
	 */
	public function setLanguagesSetsLanguage() {
		$language = array(
			'nl' => '1',
			'de' => '0',
		);
		$this->fixture->setLanguages($language);

		$this->assertSame(
			$language,
			$this->fixture->getLanguages()
		);
	}

	/**
	 * @test
	 */
	public function getSelectedLanguagesInitiallyReturnsEmptyArray() {
		$this->assertSame(
			array(),
			$this->fixture->getSelectedLanguages()
		);
	}

	/**
	 * @test
	 */
	public function setSelectedLanguagesSetsSelectedLanguages() {
		$selectedLanguages = array('', '', '', '', 'de', '', '', 'nl');
		$cleanedSelectedLanguages = array('de', 'nl');
		$this->fixture->setSelectedLanguages($selectedLanguages);

		$this->assertSame(
			$cleanedSelectedLanguages,
			$this->fixture->getSelectedLanguages()
		);
	}
}
