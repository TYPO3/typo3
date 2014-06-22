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
class UpdateTranslationFormTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Lang\Domain\Model\UpdateTranslationForm
	 */
	protected $fixture = NULL;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Lang\Domain\Model\UpdateTranslationForm();
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
		$languages = array(
			'nl',
			'de',
		);
		$this->fixture->setSelectedLanguages($languages);

		$this->assertSame(
			$languages,
			$this->fixture->getSelectedLanguages()
		);
	}

	/**
	 * @test
	 */
	public function getExtensionsInitiallyReturnsEmptyArray() {
		$this->assertSame(
			array(),
			$this->fixture->getExtensions()
		);
	}

	/**
	 * @test
	 */
	public function setExtensionsSetsExtensions() {
		$extensions = array(
			1 => 'about',
			2 => 'aboutmodules',
			3 => 'adodb',
		);
		$this->fixture->setExtensions($extensions);

		$this->assertSame(
			$extensions,
			$this->fixture->getExtensions()
		);
	}
}
