<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Fabien Udriot <fabien.udriot@typo3.org>
 *  Lorenz Ulrich <lorenz.ulrich@visol.ch>
 *
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
 * Value Object for representing the Mime Type of a File.
 * http://en.wikipedia.org/wiki/MIME
 *
 * @author  Fabien Udriot <fabien.udriot@ecodev.ch>
 * @package	TYPO3
 * @subpackage	t3lib
 */
class t3lib_file_MimeType {

	/**
	 * MIME type
	 *
	 * @var string
	 */
	protected $mimeType;

	/**
	 * Media type
	 *
	 * @var int
	 */
	protected $fileType;

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {

	}

	/**
	 * Returns the mimeType
	 *
	 * @return string $mimeType
	 */
	public function getMimeType() {
		return $this->mimeType;
	}

	/**
	 * Sets the mimeType
	 *
	 * @param string $mimeType
	 * @return void
	 */
	public function setMimeType($mimeType) {
		$this->mimeType = $mimeType;
	}

	/**
	 * Returns the fileType
	 *
	 * @return int $fileType
	 */
	public function getFileType() {
		return $this->fileType;
	}

	/**
	 * Sets the fileType
	 *
	 * @param int $fileType
	 * @return void
	 */
	public function setFileType($fileType) {
		$this->fileType = $fileType;
	}

}
?>