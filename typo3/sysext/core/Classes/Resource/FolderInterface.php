<?php
namespace TYPO3\CMS\Core\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Andreas Wolf <andreas.wolf@typo3.org>
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
 * Interface for folders
 *
 * @author Andreas Wolf <andreas.wolf@typo3.org>
 */
interface FolderInterface extends \TYPO3\CMS\Core\Resource\ResourceInterface
{
	/**
	 * Returns a list of all subfolders
	 *
	 * @return \TYPO3\CMS\Core\Resource\Folder[]
	 */
	public function getSubfolders();

	/**
	 * Returns the object for a subfolder of the current folder, if it exists.
	 *
	 * @param string $name Name of the subfolder
	 * @return \TYPO3\CMS\Core\Resource\Folder
	 */
	public function getSubfolder($name);

	/**
	 * Checks if a folder exists in this folder.
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function hasFolder($name);

	/**
	 * Checks if a file exists in this folder
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function hasFile($name);

	/**
	 * Renames this folder.
	 *
	 * @param string $newName
	 * @return \TYPO3\CMS\Core\Resource\Folder
	 */
	public function rename($newName);

	/**
	 * Deletes this folder from its storage. This also means that this object becomes useless.
	 *
	 * @return boolean TRUE if deletion succeeded
	 */
	public function delete();

}

?>