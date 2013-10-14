<?php
namespace TYPO3\CMS\Documentation\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Andrea Schmuttermair <spam@schmutt.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * An extension helper model to be used in ext:documentation context
 *
 * @entity
 * @author Andrea Schmuttermair <spam@schmutt.de>
 */
class Document extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * packageKey
	 *
	 * @var string
	 * @validate NotEmpty
	 */
	protected $packageKey;

	/**
	 * extensionKey
	 *
	 * @var string
	 * @validate NotEmpty
	 */
	protected $extensionKey;

	/**
	 * icon
	 *
	 * @var string
	 */
	protected $icon;

	/**
	 * translations
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation>
	 */
	protected $translations;

	/**
	 * Default constructor.
	 */
	public function __construct() {
		// Do not remove the next line: It would break the functionality
		$this->initStorageObjects();
	}

	/**
	 * Initializes all ObjectStorage properties.
	 *
	 * @return void
	 */
	protected function initStorageObjects() {
		/**
		 * Do not modify this method!
		 * It will be rewritten on each save in the extension builder
		 * You may modify the constructor of this class instead
		 */
		$this->translations = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * Returns the package key.
	 *
	 * @return string $packageKey
	 */
	public function getPackageKey() {
		return $this->packageKey;
	}

	/**
	 * Sets the package key.
	 *
	 * @param string $packageKey
	 * @return Document
	 */
	public function setPackageKey($packageKey) {
		$this->packageKey = $packageKey;
		return $this;
	}

	/**
	 * Returns the extension key.
	 *
	 * @return string $extensionKey
	 */
	public function getExtensionKey() {
		return $this->extensionKey;
	}

	/**
	 * Sets the extension key.
	 *
	 * @param string $extensionKey
	 * @return Document
	 */
	public function setExtensionKey($extensionKey) {
		$this->extensionKey = $extensionKey;
		return $this;
	}

	/**
	 * Returns the icon.
	 *
	 * @return string $icon
	 */
	public function getIcon() {
		return $this->icon;
	}

	/**
	 * Sets the icon.
	 *
	 * @param string $icon
	 * @return Document
	 */
	public function setIcon($icon) {
		$this->icon = $icon;
		return $this;
	}

	/**
	 * Adds a document translation.
	 *
	 * @param \TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation $translation
	 * @return Document
	 */
	public function addTranslation(\TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation $translation) {
		$this->translations->attach($translation);
		return $this;
	}

	/**
	 * Removes a document translation.
	 *
	 * @param \TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation $translationToRemove The DocumentTranslation to be removed
	 * @return Document
	 */
	public function removeTranslation(\TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation $translationToRemove) {
		$this->translations->detach($translationToRemove);
		return $this;
	}

	/**
	 * Returns the translations.
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation> $translations
	 */
	public function getTranslations() {
		return $this->translations;
	}

	/**
	 * Sets the translations.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Documentation\Domain\Model\DocumentTranslation> $translations
	 * @return Document
	 */
	public function setTranslations(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $translations) {
		$this->translations = $translations;
		return $this;
	}

}
