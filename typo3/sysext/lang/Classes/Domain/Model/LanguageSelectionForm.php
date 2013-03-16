<?php
namespace TYPO3\CMS\Lang\Domain\Model;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Sebastian Fischer <typo3@evoweb.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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
?>