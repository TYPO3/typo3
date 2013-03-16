<?php
namespace TYPO3\CMS\Core\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Ingmar Schlecht <ingmar@typo3.org>
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
 * File Interface
 *
 * @author Ingmar Schlecht <ingmar@typo3.org>
 */
interface FileInterface extends ResourceInterface {
	/*******************************
	 * VARIOUS FILE PROPERTY GETTERS
	 *******************************/
	/**
	 * Returns true if the given key exists for this file.
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function hasProperty($key);

	/**
	 * Get the value of the $key property.
	 *
	 * @param string $key
	 * @return string
	 */
	public function getProperty($key);

	/**
	 * Returns the size of this file
	 *
	 * @return integer
	 */
	public function getSize();

	/**
	 * Returns the Sha1 of this file
	 *
	 * @return string
	 */
	public function getSha1();

	/**
	 * Returns the basename (the name without extension) of this file.
	 *
	 * @return string
	 *
	public function getNameWithoutExtension();

	/**
	 * Get the file extension
	 *
	 * @return string The file extension
	 */
	public function getExtension();

	/**
	 * Get the MIME type of this file
	 *
	 * @return array file information
	 */
	public function getMimeType();

	/**
	 * Returns the modification time of the file as Unix timestamp
	 *
	 * @return integer
	 */
	public function getModificationTime();

	/**
	 * Returns the creation time of the file as Unix timestamp
	 *
	 * @return integer
	 */
	public function getCreationTime();

	/******************
	 * CONTENTS RELATED
	 ******************/
	/**
	 * Get the contents of this file
	 *
	 * @return string File contents
	 */
	public function getContents();

	/**
	 * Replace the current file contents with the given string.
	 *
	 * @TODO : Consider to remove this function from the interface, as its
	 * @TODO : At the same time, it could be considered whether to make the whole
	 * @param string $contents The contents to write to the file.
	 * @return File The file object (allows chaining).
	 */
	public function setContents($contents);

	/****************************************
	 * STORAGE AND MANAGEMENT RELATED METHDOS
	 ****************************************/
	/**
	 * Deletes this file from its storage. This also means that this object becomes useless.
	 *
	 * @return boolean TRUE if deletion succeeded
	 */
	public function delete();

	/**
	 * Renames this file.
	 *
	 * @param string $newName The new file name
	 * @return File
	 */
	public function rename($newName);

	/*****************
	 * SPECIAL METHODS
	 *****************/
	/**
	 * Returns a publicly accessible URL for this file
	 *
	 * WARNING: Access to the file may be restricted by further means, e.g.
	 * some web-based authentication. You have to take care of this yourself.
	 *
	 * @param bool $relativeToCurrentScript Determines whether the URL returned should be relative to the current script, in case it is relative at all (only for the LocalDriver)
	 * @return string
	 */
	public function getPublicUrl($relativeToCurrentScript = FALSE);

	/**
	 * Returns TRUE if this file is indexed
	 *
	 * @return boolean
	 */
	public function isIndexed();

	/**
	 * Returns a path to a local version of this file to process it locally (e.g. with some system tool).
	 * If the file is normally located on a remote storages, this creates a local copy.
	 * If the file is already on the local system, this only makes a new copy if $writable is set to TRUE.
	 *
	 * @param bool $writable Set this to FALSE if you only want to do read operations on the file.
	 * @return string
	 */
	public function getForLocalProcessing($writable = TRUE);

	/**
	 * Returns an array representation of the file.
	 * (This is used by the generic listing module vidi when displaying file records.)
	 *
	 * @return array Array of main data of the file. Don't rely on all data to be present here, it's just a selection of the most relevant information.
	 */
	public function toArray();

}

?>