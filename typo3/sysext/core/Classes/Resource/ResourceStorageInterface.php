<?php
namespace TYPO3\CMS\Core\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Thomas Maroschik <tmaroschik@dfau.de>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
 * The interface for a resource storage containing all constants
 *
 */
interface ResourceStorageInterface {

	const SIGNAL_PreFileAdd = 'preFileAdd';
	const SIGNAL_PostFileAdd = 'postFileAdd';
	const SIGNAL_PreFileCopy = 'preFileCopy';
	const SIGNAL_PostFileCopy = 'postFileCopy';
	const SIGNAL_PreFileMove = 'preFileMove';
	const SIGNAL_PostFileMove = 'postFileMove';
	const SIGNAL_PreFileDelete = 'preFileDelete';
	const SIGNAL_PostFileDelete = 'postFileDelete';
	const SIGNAL_PreFileRename = 'preFileRename';
	const SIGNAL_PostFileRename = 'postFileRename';
	const SIGNAL_PreFileReplace = 'preFileReplace';
	const SIGNAL_PostFileReplace = 'postFileReplace';
	const SIGNAL_PreFolderAdd = 'preFolderAdd';
	const SIGNAL_PostFolderAdd = 'postFolderAdd';
	const SIGNAL_PreFolderCopy = 'preFolderCopy';
	const SIGNAL_PostFolderCopy = 'postFolderCopy';
	const SIGNAL_PreFolderMove = 'preFolderMove';
	const SIGNAL_PostFolderMove = 'postFolderMove';
	const SIGNAL_PreFolderDelete = 'preFolderDelete';
	const SIGNAL_PostFolderDelete = 'postFolderDelete';
	const SIGNAL_PreFolderRename = 'preFolderRename';
	const SIGNAL_PostFolderRename = 'postFolderRename';
	const SIGNAL_PreGeneratePublicUrl = 'preGeneratePublicUrl';

	/**
	 * Capability for being browsable by (backend) users
	 */
	const CAPABILITY_BROWSABLE = 1;
	/**
	 * Capability for publicly accessible storages (= accessible from the web)
	 */
	const CAPABILITY_PUBLIC = 2;
	/**
	 * Capability for writable storages. This only signifies writability in
	 * general - this might also be further limited by configuration.
	 */
	const CAPABILITY_WRITABLE = 4;
	/**
	 * Name of the default processing folder
	 */
	const DEFAULT_ProcessingFolder = '_processed_';

}
