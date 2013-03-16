<?php

namespace TYPO3\CMS\Lang\Tests\Unit\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Wouter Wolters <typo3@wouterwolters.nl>
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
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase for Language
 *
 * @author Wouter Wolters <typo3@wouterwolters.nl>
 */
class LanguageTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

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
	 * Tear down
	 */
	public function tearDown() {
		$this->fixture = NULL;
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
?>