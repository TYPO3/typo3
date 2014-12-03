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
 * Model to contain all information from the form changing the selection of available languages
 *
 * @author Sebastian Fischer <typo3@evoweb.de>
 */
class LanguageSelectionForm extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * @var array
	 */
	protected $languages = array();

	/**
	 * @var array
	 */
	protected $selectedLanguages = array();

	/**
	 * Setter for the languages
	 *
	 * @param array $locale Selected languages
	 * @return void
	 */
	public function setLanguages(array $languages) {
		$this->languages = $languages;
	}

	/**
	 * Getter for the languages
	 *
	 * @return array
	 */
	public function getLanguages() {
		return $this->languages;
	}

	/**
	 * Setter for the selected languages
	 *
	 * @param array $locale Selected languages
	 * @return void
	 */
	public function setSelectedLanguages(array $selectedLanguages) {
		$this->selectedLanguages = array_values(array_filter($selectedLanguages));
	}

	/**
	 * Getter for the selected languages
	 *
	 * @return array
	 */
	public function getSelectedLanguages() {
		return $this->selectedLanguages;
	}

	/**
	 * Returns a comma separated list of selected languages
	 *
	 * @return string
	 */
	public function getSelectedLanguagesLocaleList() {
		if (!empty($this->selectedLanguages) && is_array($this->selectedLanguages)) {
			$locales = array();
			foreach ($this->selectedLanguages as $language) {
				$locales[] = $language->getLocale();
			}

			return implode(',', $locales);
		}
		return '';
	}

}
