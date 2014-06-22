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
	 * @param boolean $selected whether the language is available or not
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
