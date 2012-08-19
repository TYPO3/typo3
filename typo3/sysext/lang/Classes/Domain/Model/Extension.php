<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Sebastian fischer, <typo3@evoweb.de>
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
 * An exception when something is wrong within the lang
 *
 * @author Sebastian fischer, <typo3@evoweb.de>
 * @package lang
 * @subpackage Extension
 */
class Tx_Lang_Domain_Model_Extension extends Tx_Extbase_DomainObject_AbstractEntity {
	/**
	 * @var string
	 */
	protected $key = '';

	/**
	 * @var string
	 */
	protected $title = '';

	/**
	 * @var string
	 */
	protected $icon = '';

	/**
	 * @var integer
	 */
	protected $version = '';

	/**
	 * @var string
	 */
	protected $stateCls = '';

	/**
	 * @var string
	 */
	protected $versionislower = '';

	/**
	 * @var string
	 */
	protected $maxversion = '';

	/**
	 * @var array
	 */
	protected $updateResult = array();


	/**
	 * @param string $key
	 * @param string $title
	 * @param string $icon
	 * @return Tx_Lang_Domain_Model_Extension
	 */
	public function __construct($key, $title, $icon) {
		$this->setKey($key);
		$this->setTitle($title);
		$this->setIcon($icon);
	}


	/**
	 * @param string $icon
	 */
	public function setIcon($icon) {
		$this->icon = $icon;
	}

	/**
	 * @return string
	 */
	public function getIcon() {
		return $this->icon;
	}

	/**
	 * @param string $key
	 */
	public function setKey($key) {
		$this->key = $key;
	}

	/**
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @param integer $version
	 */
	public function setVersion($version) {
		$this->version = $version;
	}

	/**
	 * @param string $version
	 */
	public function setVersionFromString($version) {
		$this->version = t3lib_utility_VersionNumber::convertVersionNumberToInteger($version);
	}

	/**
	 * @return int
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * @param string $updateResult
	 */
	public function setUpdateResult($updateResult) {
		$this->updateResult = (array) $updateResult;
	}

	/**
	 * @return string
	 */
	public function getUpdateResult() {
		return $this->updateResult;
	}
}

?>