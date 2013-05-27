<?php
namespace TYPO3\CMS\Extbase\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * This model represents a file mount.
 *
 * @api
 */
class FileMount extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * Title of the file mount.
	 *
	 * @var string
	 * @validate notEmpty
	 */
	protected $title = '';

	/**
	 * Path of the file mount.
	 *
	 * @var string
	 * @validate notEmpty
	 */
	protected $path = '';

	/**
	 * Determines whether the value of the path field is to be recognized as an absolute
	 * path on the server or a path relative to the fileadmin/ subfolder to the website.
	 *
	 * If the value is true the path is an absolute one, otherwise the path is relative
	 * the fileadmin.
	 *
	 * @var boolean
	 */
	protected $isAbsolutePath = FALSE;

	/**
	 * Getter for the title of the file mount.
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Setter for the title of the file mount.
	 *
	 * @param string $value
	 * @return void
	 */
	public function setTitle($value) {
		$this->title = $value;
	}

	/**
	 * Getter for the path of the file mount.
	 *
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Setter for the path of the file mount.
	 *
	 * @param string $value
	 * @return void
	 */
	public function setPath($value) {
		$this->path = $value;
	}

	/**
	 * Getter for the is absolute path of the file mount.
	 *
	 * @return boolean
	 */
	public function getIsAbsolutePath() {
		return $this->isAbsolutePath;
	}

	/**
	 * Setter for is absolute path of the file mount.
	 *
	 * @param boolean $value
	 * @return void
	 */
	public function setIsAbsolutePath($value) {
		$this->isAbsolutePath = $value;
	}
}

?>