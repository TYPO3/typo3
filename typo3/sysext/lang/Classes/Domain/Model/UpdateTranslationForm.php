<?php
namespace TYPO3\CMS\Lang\Domain\Model;
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
 * Model to contain all information from the form requesting to update translations
 *
 * @author Sebastian Fischer <typo3@evoweb.de>
 */
class UpdateTranslationForm extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * @var array
	 */
	protected $selectedLanguages = array();

	/**
	 * @var array
	 */
	protected $extensions = array();

	/**
	 * Setter for the extensions
	 *
	 * @param array $extensions of extensions that were requested
	 * @return void
	 */
	public function setExtensions(array $extensions) {
		$this->extensions = $extensions;
	}

	/**
	 * Getter for the extensions
	 *
	 * @return array
	 */
	public function getExtensions() {
		return $this->extensions;
	}

	/**
	 * Setter for the selected languages
	 *
	 * @param array $selectedLanguages selected languages that were requested
	 * @return void
	 */
	public function setSelectedLanguages(array $selectedLanguages) {
		$this->selectedLanguages = $selectedLanguages;
	}

	/**
	 * Getter for the selected languages
	 *
	 * @return array
	 */
	public function getSelectedLanguages() {
		return $this->selectedLanguages;
	}
}
