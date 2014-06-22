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
class DocumentTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Documentation\Domain\Model\Document
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Documentation\Domain\Model\Document();
	}

	/**
	 * @test
	 */
	public function setPackageKeyForStringSetsPackageKey() {
		$this->fixture->setPackageKey('Conceived at T3DD13');

		$this->assertSame(
			'Conceived at T3DD13',
			$this->fixture->getPackageKey()
		);
	}

	/**
	 * @test
	 */
	public function setIconForStringSetsTitle() {
		$this->fixture->setIcon('Conceived at T3DD13');

		$this->assertSame(
			'Conceived at T3DD13',
			$this->fixture->getIcon()
		);
	}

	/**
	 * @test
	 */
	public function getTranslationsReturnsInitialValueForDocumentTranslation() {
		$newObjectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->assertEquals(
			$newObjectStorage,
			$this->fixture->getTranslations()
		);
	}

	/**
	 * @test
	 */
	public function setTranslationsForObjectStorageContainingDocumentTranslationSetsTranslations() {
		$translation = new \TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation();
		$objectStorageHoldingExactlyOneTranslations = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$objectStorageHoldingExactlyOneTranslations->attach($translation);
		$this->fixture->setTranslations($objectStorageHoldingExactlyOneTranslations);

		$this->assertSame(
			$objectStorageHoldingExactlyOneTranslations,
			$this->fixture->getTranslations()
		);
	}

	/**
	 * @test
	 */
	public function addTranslationToObjectStorageHoldingTranslations() {
		$translation = new \TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation();
		$objectStorageHoldingExactlyOneTranslation = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$objectStorageHoldingExactlyOneTranslation->attach($translation);
		$this->fixture->addTranslation($translation);

		$this->assertEquals(
			$objectStorageHoldingExactlyOneTranslation,
			$this->fixture->getTranslations()
		);
	}

	/**
	 * @test
	 */
	public function removeTranslationFromObjectStorageHoldingTranslations() {
		$translation = new \TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation();
		$localObjectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$localObjectStorage->attach($translation);
		$localObjectStorage->detach($translation);
		$this->fixture->addTranslation($translation);
		$this->fixture->removeTranslation($translation);

		$this->assertEquals(
			$localObjectStorage,
			$this->fixture->getTranslations()
		);
	}

}
