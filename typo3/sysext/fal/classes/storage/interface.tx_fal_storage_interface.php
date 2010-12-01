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
 * File Abtraction Layer Storage Interface
 *
 * @todo Andy Grunwald, 01.12.2010, update needed methods
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id$
 */
interface tx_fal_storage_Interface {

	/**
	 * DESCRIPTION
	 */
	public function getBasePath();

	/**
	 * DESCRIPTION
	 *
	 * @param	[to be defined]		$path	DESCRIPTION
	 * @param	[to be defined]		$mode	DESCRIPTION
	 */
	public function open($path, $mode);

	/**
	 * Read the contents of a file
	 *
	 * @param	string	$path	Absolute path to the file
	 * @return	string			File contents
	 */
	public function read($path);

	/**
	 * Write data into a file
	 *
	 * @param	string		$path		Absolute path to the file
	 * @param	string		$content	File contents
	 * @return	boolean					Success of operation
	 */
	public function write($path, $content);

	/**
	 * Delete the file
	 *
	 * @param	string	$path	Absolute path to the file
	 * @return	void
	 */
	public function delete($path);

	/**
	 * Check if file or directory exists
	 *
	 * @param	string	$path	Absolute path to the file
	 * @return	boolean			True if the file or folder exists, false otherwise
	 */
	public function exists($path);

	/**
	 * Get timestamp of when the file was last modified
	 *
	 * @param	string	$path	Absolute path to the file
	 * @return	integer			Timestamp
	 */
	public function getModificationTime($path);

	/**
	 * Get the sha1 hash of the file
	 *
	 * @param	string	$path	Absolute path to the file
	 * @return	string			Hash of the content
	 */
	public function getFileHash($path);

	/**
	 * Get the file size of the content
	 *
	 * @param	string	$path	Absolute path to the file
	 * @return	integer			Size of the file in bytes
	 */
	public function getSize($path);

	/**
	 * DESCRIPTION
	 *
	 * @param	[to be defined]		$path		DESCRIPTION
	 * @param	[to be defined]		$newPath	DESCRIPTION
	 * @return	[to be defined]					DESCRIPTION
	 */
	public function copyFile($path, $newPath);

	/**
	 * DESCRIPTION
	 *
	 * @param	[to be defined]		$oldPath	DESCRIPTION
	 * @param	[to be defined]		$newPath	DESCRIPTION
	 * @return	[to be defined]					DESCRIPTION
	 */
	public function moveFile($oldPath, $newPath);

	/**
	 * Creates a directory inside a path
	 *
	 * @param	[to be defined]		$path			DESCRIPTION
	 * @param	[to be defined]		$directoryName	DESCRIPTION
	 * @return	boolean						TRUE if the directory could be created
	 */
	public function createDirectory($path, $directoryName);

	/**
	 * Returns a list of all files and directories inside a path
	 *
	 * @param	string	$path	DESCRIPTION
	 * @return	void
	 */
	public function getListing($path);

	/**
	 * Returns a list of directories in a path.
	 *
	 * @param  $path
	 * @return array
	 */
	public function getDirectoriesInPath($path);

	/**
	 * Returns a list of files in a path.
	 *
	 * @param  $path
	 * @return array
	 */
	public function getFilesInPath($path);
}
?>