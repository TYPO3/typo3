<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 * Interface for FAL Publisher
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
interface t3lib_file_Service_Publishing_Publisher {
	public function __construct(t3lib_file_Storage $publishingTarget, array $configuration);

	/**
	 * Publishes a file to this publisher's public space
	 *
	 * @abstract
	 * @param t3lib_file_FileInterface $fileObject
	 * @return string The URI of the published file
	 */
	public function publishFile(t3lib_file_FileInterface $fileObject);

	/**
	 * Publishes a collection of files, if necessary also recursively.
	 *
	 * @abstract
	 * @param t3lib_file_Folder $folder
	 * @return void
	 */
	public function publishFolder(t3lib_file_Folder $folder);

	/**
	 * Returns TRUE if a file has been published.
	 *
	 * @abstract
	 * @param t3lib_file_FileInterface $fileObject
	 * @return bool
	 */
	public function isPublished(t3lib_file_FileInterface $fileObject);

	/**
	 * Returns the public URL of a given file. Will throw an exception if the file is not public.
	 *
	 * @abstract
	 * @param t3lib_file_FileInterface $fileObject
	 * @return string
	 */
	public function getPublicUrl(t3lib_file_FileInterface $fileObject);
}

?>