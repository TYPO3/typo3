<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 FAL development team <fal@wmdb.de>
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
 * File Abtraction Layer File
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id$
 */
class tx_fal_File {

	/**
	 * The uid of this file's record
	 *
	 * @var integer
	 */
	protected $uid;

	/**
	 * The name of this file. This is only the filename without the path
	 *
	 * @var string
	 */
	protected $filename;

	/**
	 * The mount this file is located in
	 *
	 * @var t3lib_file_Mount
	 */
	protected $mount;

	/**
	 * The path to this file (inside the file mount)
	 *
	 * @var string
	 */
	protected $path = NULL;

	/**
	 * The file size
	 *
	 * @var integer
	 */
	protected $size;

	/**
	 * The SHA-1 hash sum of the file
	 *
	 * @var string
	 */
	protected $hash;

	/**
	 * DESCRIPTION
	 *
	 * @param	tx_fal_Mount	$mount		DESCRIPTION
	 * @param	array			$fileData	DESCRIPTION
	 * @return	void
	 */
	public function  __construct(tx_fal_Mount $mount, array $fileData) {
		$this->mount = $mount;
		$this->uid = $fileData['uid'];
		$this->name = $fileData['file_name'];
		$this->path = $fileData['file_path'];
		$this->size = $fileData['file_size'];
		$this->hash = $fileData['file_hash'];
	}

	/**
	 * Getter for thumbnail
	 *
	 * @todo Implement tx_fal_File::getThumbnail
	 *
	 * @return	[to be defined]
	 */
	public function getThumbnail() {
	}

	/**
	 * Getter for thumbnail url
	 *
	 * @todo Implement tx_fal_File::getThumbnailUrl
	 *
	 * @return	[to be defined]
	 */
	public function getThumbnailUrl() {
	}

	/**
	 * Setter for relations
	 *
	 * @todo Implement tx_fal_File::setRelation
	 *
	 * @param	string	$table	DESCRIPTION
	 * @param	integer	$uid	DESCRIPTION
	 * @return	void
	 */
	public function setRelation($table, $uid) {
	}

	/**
	 * Getter for related records
	 *
	 * @todo Implement tx_fal_File::getRelatedRecords
	 *
	 * @param	string	$table	DESCRIPTION
	 * @return	[to be defined]
	 */
	public function getRelatedRecords($table = '') {
	}

	/**
	 * Getter for url of file
	 *
	 * @return	string		DESCRIPTION
	 */
	public function getUrl() {
		return $this->path . $this->name;
	}

	/**
	 *
	 * DESCRIPTION
	 *
	 * @todo Implement tx_fal_File::writeFile
	 *
	 * @param	[to be defined]	$content	DESCRIPTION
	 * @return	[to be defined]
	 */
	public function writeFile($content) {
	}

	/**
	 * Getter for uid of file in sys_files
	 *
	 * @return	integer		Uid of file in sys_files
	 */
	public function getUid() {
		return $this->uid;
	}

	/**
	 * Getter for name of file in sys_files
	 *
	 * @return	string		Name of file in sys_files
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Getter for path of file
	 *
	 * @return	string		Path of file
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Getter for size in bytes of file
	 *
	 * @return	integer		Size in bytes of file
	 */
	public function getSize() {
		return $this->size;
	}

	/**
	 * Getter for hash (which one? SHA? CRC? MD5?) of file
	 *
	 * @return	[to be defined]		Hash (which one? SHA? CRC? MD5?) of file
	 */
	public function getHash() {
		return $this->hash;
	}

	/**
	 * DESCRIPTION
	 *
	 * @return	[to be defined]		DESCRIPTION
	 */
	public function delete() {
		$result = $this->mount->getStorageBackend()->delete($this->getUrl());


		// @todo:  @todo: <rupert.germann>, 01.12.2010  check if the file was actually deleted


		tx_fal_Indexer::deleteFileFromIndex($this->uid);

		return $result;
	}

	/**
	 * Renames the file. This can not change the path! Use moveInsideMount() or moveToMount() for that
	 *
	 * @param string $newName
	 * @return boolean TRUE if renaming succeeded
	 */
	public function rename($newName) {
		$newPath = $this->path . $newName;

		$this->moveInsideMount($newPath);
	}

	/**
	 * DESCRIPTION
	 *
	 * @param	string		$filePath	DESCRIPTION
	 * @return	[to be defined]			DESCRIPTION
	 */
	public function replaceWithLocalFile($filePath) {
	}

	/**
	 * DESCRIPTION
	 *
	 * @return	[to be defined]			DESCRIPTION
	 */
	public function getFileCopyForLocalProcessing() {
	}

	/**
	 * DESCRIPTION
	 *
	 * @param	string		$newPath	DESCRIPTION
	 * @return	[to be defined]			DESCRIPTION
	 */
	public function copyInsideMount($newPath) {
	}

	/**
	 * DESCRIPTION
	 *
	 * @param	tx_fal_Mount	$mount		DESCRIPTION
	 * @param	string			$newPath	DESCRIPTION
	 * @return	[to be defined]				DESCRIPTION
	 */
	public function copyToMount(tx_fal_Mount $mount, $newPath) {
	}

	/**
	 * Moves the file to a new location inside its current mount.
	 *
	 * @param string $newPath The path to move the file to
	 * @return boolean
	 */
	public function moveInsideMount($newPath) {
		$oldPath = $this->path . $this->name;

		$result = $this->mount->getStorageBackend()->moveFile($oldPath, $newPath);

		// @todo: <rupert.germann>, 26.11.2010 check if the file was actually moved

		$this->name = basename($newPath);
		$this->path = dirname($newPath);
		tx_fal_Indexer::updateFileIndex($this);

		return $result;
	}

	/**
	 * DESCRIPTION
	 *
	 * @param	string		$newPath		DESCRIPTION
	 * @return	[to be defined]				DESCRIPTION
	 */
	public function moveFileToFolder($newPath) {

		$basePath = str_replace(PATH_site, '', $this->mount->getStorageBackend()->getBasePath());

		$oldPath = $this->path . $this->name;
		$relNewPath = str_replace(PATH_site . $basePath, '', $newPath) . $this->name;

		$result = $this->mount->getStorageBackend()->moveFile($oldPath, $relNewPath, 'move');

		// @todo: <rupert.germann>, 26.11.2010 check if the file was actually moved

		$this->path = str_replace(PATH_site . $basePath, '', $newPath);
		tx_fal_Indexer::updateFileIndex($this);

		return $result;
	}

	/**
	 * DESCRIPTION
	 *
	 * @todo Implement tx_fal_File::moveToMount
	 *
	 * @param 	tx_fal_Mount	$mount		DESCRIPTION
	 * @param	string			$newPath	DESCRIPTION
	 * @return	[to be defined]		DESCRIPTION
	 */
	public function moveToMount(tx_fal_Mount $mount, $newPath) {
	}

	/**
	 * DESCRIPTION
	 *
	 * @todo Implement tx_fal_File::getContents
	 *
	 * @return	[to be defined]		DESCRIPTION
	 */
	public function getContents() {
	}

	/**
	 * DESCRIPTION
	 *
	 * @param	[to be defined]		$mode	DESCRIPTION
	 * @return	[to be defined]				DESCRIPTION
	 */
	public function getFileHandle($mode) {
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/class.tx_fal_File.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/class.tx_fal_File.php']);
}
?>