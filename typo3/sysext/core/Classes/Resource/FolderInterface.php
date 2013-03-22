<?php
namespace TYPO3\CMS\Core\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Andreas Wolf <andreas.wolf@typo3.org>
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
interface FolderInterface extends ResourceInterface
{
	/**
	 * Roles for folders
	 */
	const ROLE_DEFAULT = 'default';
	const ROLE_RECYCLER = 'recycler';
	const ROLE_PROCESSING = 'processing';
	const ROLE_TEMPORARY = 'temporary';
	const ROLE_USERUPLOAD = 'userupload';

	/**
	 * Returns a list of all subfolders
	 *
	 * @return Folder[]
	 */
	public function getSubfolders();

	/**
	 * Returns the object for a subfolder of the current folder, if it exists.
	 *
	 * @param string $name Name of the subfolder
	 * @return Folder
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
	 * @return Folder
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