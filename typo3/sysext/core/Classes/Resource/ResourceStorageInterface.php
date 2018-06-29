<?php
namespace TYPO3\CMS\Core\Resource;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * The interface for a resource storage containing all constants
 */
interface ResourceStorageInterface
{
    const SIGNAL_SanitizeFileName = 'sanitizeFileName';
    const SIGNAL_PreFileAdd = 'preFileAdd';
    const SIGNAL_PostFileAdd = 'postFileAdd';
    const SIGNAL_PreFileCreate = 'preFileCreate';
    const SIGNAL_PostFileCreate = 'postFileCreate';
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
    const SIGNAL_PreFileSetContents = 'preFileSetContents';
    const SIGNAL_PostFileSetContents = 'postFileSetContents';
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
