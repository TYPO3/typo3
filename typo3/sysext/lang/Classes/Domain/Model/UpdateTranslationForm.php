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

?>