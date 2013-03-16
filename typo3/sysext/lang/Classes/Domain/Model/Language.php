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
 * Language model
 *
 * @author Sebastian Fischer <typo3@evoweb.de>
 */
class Language extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * @var string
	 */
	protected $locale = '';

	/**
	 * @var string
	 */
	protected $language = '';

	/**
	 * @var boolean
	 */
	protected $selected = FALSE;

	/**
	 * Constructor of the language model
	 *
	 * @param string $locale
	 * @param string $language
	 * @param boolean $selected
	 */
	public function __construct($locale = '', $language = '', $selected = FALSE) {
		$this->setLocale($locale);
		$this->setLanguage($language);
		$this->setSelected($selected);
	}

	/**
	 * Setter for the language
	 *
	 * @param string $language the label of the language
	 * @return void
	 */
	public function setLanguage($language) {
		$this->language = $language;
	}

	/**
	 * Getter for the language
	 *
	 * @return string
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * Setter for the locale
	 *
	 * @param string $locale the locale for the language like da, nl or de
	 * @return void
	 */
	public function setLocale($locale) {
		$this->locale = $locale;
	}

	/**
	 * Getter for the locale
	 *
	 * @return string
	 */
	public function getLocale() {
		return $this->locale;
	}

	/**
	 * Setter for the selected
	 *
	 * @param boolean $selected wether the language is available or not
	 * @return void
	 */
	public function setSelected($selected) {
		$this->selected = $selected ? TRUE : FALSE;
	}

	/**
	 * Getter for the selected
	 *
	 * @return boolean
	 */
	public function getSelected() {
		return $this->selected;
	}
}

?>