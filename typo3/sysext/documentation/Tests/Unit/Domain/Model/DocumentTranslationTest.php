<?php
namespace TYPO3\CMS\Documentation\Tests\Unit\Domain\Model;

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
class DocumentTranslationTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation();
	}

	/**
	 * @test
	 */
	public function setLanguageForStringSetsLocale() {
		$this->fixture->setLanguage('Conceived at T3DD13');

		$this->assertSame(
			'Conceived at T3DD13',
			$this->fixture->getLanguage()
		);
	}

	/**
	 * @test
	 */
	public function setTitleForStringSetsTitle() {
		$this->fixture->setTitle('Conceived at T3DD13');

		$this->assertSame(
			'Conceived at T3DD13',
			$this->fixture->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function getFormatsReturnsInitialValueForDocumentFormat() {
		$newObjectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->assertEquals(
			$newObjectStorage,
			$this->fixture->getFormats()
		);
	}

	/**
	 * @test
	 */
	public function setFormatsForObjectStorageContainingDocumentFormatSetsFormats() {
		$format = new \TYPO3\CMS\Documentation\Domain\Model\DocumentFormat();
		$objectStorageHoldingExactlyOneFormats = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$objectStorageHoldingExactlyOneFormats->attach($format);
		$this->fixture->setFormats($objectStorageHoldingExactlyOneFormats);

		$this->assertSame(
			$objectStorageHoldingExactlyOneFormats,
			$this->fixture->getFormats()
		);
	}

	/**
	 * @test
	 */
	public function addFormatToObjectStorageHoldingFormats() {
		$format = new \TYPO3\CMS\Documentation\Domain\Model\DocumentFormat();
		$objectStorageHoldingExactlyOneFormat = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$objectStorageHoldingExactlyOneFormat->attach($format);
		$this->fixture->addFormat($format);

		$this->assertEquals(
			$objectStorageHoldingExactlyOneFormat,
			$this->fixture->getFormats()
		);
	}

	/**
	 * @test
	 */
	public function removeFormatFromObjectStorageHoldingFormats() {
		$format = new \TYPO3\CMS\Documentation\Domain\Model\DocumentFormat();
		$localObjectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$localObjectStorage->attach($format);
		$localObjectStorage->detach($format);
		$this->fixture->addFormat($format);
		$this->fixture->removeFormat($format);

		$this->assertEquals(
			$localObjectStorage,
			$this->fixture->getFormats()
		);
	}

}
