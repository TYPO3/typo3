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
 * Test case for Language
 */
class LanguageTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Lang\Domain\Model\Language
	 */
	protected $fixture = NULL;

	/**
	 * Set up
	 */
	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Lang\Domain\Model\Language();
	}

	/**
	 * @test
	 */
	public function getLocaleInitiallyReturnsEmptyString() {
		$this->assertSame(
			'',
			$this->fixture->getLocale()
		);
	}

	/**
	 * @test
	 */
	public function getLocaleInitiallyReturnsGivenLocaleFromConstruct() {
		$locale = 'nl';
		$this->fixture = new \TYPO3\CMS\Lang\Domain\Model\Language($locale);

		$this->assertSame(
			$locale,
			$this->fixture->getLocale()
		);
	}

	/**
	 * @test
	 */
	public function setLocaleSetsLocale() {
		$locale = 'nl';
		$this->fixture->setLocale($locale);

		$this->assertSame(
			$locale,
			$this->fixture->getLocale()
		);
	}

	/**
	 * @test
	 */
	public function getLanguageInitiallyReturnsEmptyString() {
		$this->assertSame(
			'',
			$this->fixture->getLanguage()
		);
	}

	/**
	 * @test
	 */
	public function getLanguageInitiallyReturnsGivenLanguageFromConstruct() {
		$language = 'nl';
		$this->fixture = new \TYPO3\CMS\Lang\Domain\Model\Language('', $language);

		$this->assertSame(
			$language,
			$this->fixture->getLanguage()
		);
	}

	/**
	 * @test
	 */
	public function setLanguageSetsLanguage() {
		$language = 'nl';
		$this->fixture->setLanguage($language);

		$this->assertSame(
			$language,
			$this->fixture->getLanguage()
		);
	}

	/**
	 * @test
	 */
	public function getSelectedInitiallyReturnsFalse() {
		$this->assertSame(
			FALSE,
			$this->fixture->getSelected()
		);
	}

	/**
	 * @test
	 */
	public function getSelectedInitiallyReturnsGivenSelectedFromConstruct() {
		$selected = FALSE;
		$this->fixture = new \TYPO3\CMS\Lang\Domain\Model\Language('', '', FALSE);

		$this->assertSame(
			$selected,
			$this->fixture->getSelected()
		);
	}

	/**
	 * @test
	 */
	public function setSelectedSetsSelected() {
		$selected = TRUE;
		$this->fixture->setSelected($selected);

		$this->assertSame(
			$selected,
			$this->fixture->getSelected()
		);
	}

}
