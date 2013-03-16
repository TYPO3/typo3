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
 * Extension model
 *
 * @author Sebastian Fischer <typo3@evoweb.de>
 */
class Extension extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

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
	 * Construtor of the extension model.
	 *
	 * @param string $key The extension key
	 * @param string $title Title of the extension
	 * @param string $icon Icon representing the extension
	 */
	public function __construct($key = '', $title= '', $icon = '') {
		$this->setKey($key);
		$this->setTitle($title);
		$this->setIcon($icon);
	}

	/**
	 * Setter for the icon
	 *
	 * @param string $icon ext_icon path relative to typo3 folder like ../typo3conf/ext/extensionkey/ext_icon.gif
	 * @return void
	 */
	public function setIcon($icon) {
		$this->icon = $icon;
	}

	/**
	 * Getter for the icon
	 *
	 * @return string ext_icon path relative to typo3 folder
	 */
	public function getIcon() {
		return $this->icon;
	}

	/**
	 * Setter for the key
	 *
	 * @param string $key
	 * @return void
	 */
	public function setKey($key) {
		$this->key = $key;
	}

	/**
	 * Getter for the key
	 *
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * Setter for the title
	 *
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Getter for the title
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Setter for the version
	 *
	 * @param integer $version Needs to have a valid version format like 1003007
	 * @return void
	 */
	public function setVersion($version) {
		$this->version = $version;
	}

	/**
	 * Setter for the version from string
	 *
	 * @param string $version Needs to have a format like '1.3.7' and converts it into an integer like 1003007 before setting the version
	 * @see \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger
	 * @return void
	 */
	public function setVersionFromString($version) {
		$this->version = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger($version);
	}

	/**
	 * Getter for the version
	 *
	 * @return integer interpretion of the extension version
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * Setter for updateResult
	 *
	 * @param array $updateResult Needs to be in a structure like array('icon' => '', 'message' => '')
	 * @return void
	 */
	public function setUpdateResult($updateResult) {
		$this->updateResult = (array) $updateResult;
	}

	/**
	 * Getter for updateResult
	 *
	 * @return array returns the update result as an arry in the structure like array('icon' => '', 'message' => '')
	 */
	public function getUpdateResult() {
		return $this->updateResult;
	}
}

?>