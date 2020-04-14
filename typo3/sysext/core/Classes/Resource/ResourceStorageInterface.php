<?php

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

namespace TYPO3\CMS\Core\Resource;

/**
 * The interface for a resource storage containing all constants
 */
interface ResourceStorageInterface
{
    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_SanitizeFileName = 'sanitizeFileName';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PreFileAdd = 'preFileAdd';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PostFileAdd = 'postFileAdd';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PreFileCreate = 'preFileCreate';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PostFileCreate = 'postFileCreate';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PreFileCopy = 'preFileCopy';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PostFileCopy = 'postFileCopy';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PreFileMove = 'preFileMove';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PostFileMove = 'postFileMove';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PreFileDelete = 'preFileDelete';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PostFileDelete = 'postFileDelete';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PreFileRename = 'preFileRename';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PostFileRename = 'postFileRename';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PreFileReplace = 'preFileReplace';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PostFileReplace = 'postFileReplace';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PreFileSetContents = 'preFileSetContents';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PostFileSetContents = 'postFileSetContents';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PreFolderAdd = 'preFolderAdd';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PostFolderAdd = 'postFolderAdd';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PreFolderCopy = 'preFolderCopy';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PostFolderCopy = 'postFolderCopy';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PreFolderMove = 'preFolderMove';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PostFolderMove = 'postFolderMove';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PreFolderDelete = 'preFolderDelete';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PostFolderDelete = 'postFolderDelete';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PreFolderRename = 'preFolderRename';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
    const SIGNAL_PostFolderRename = 'postFolderRename';

    /**
     * @deprecated since TYPO3 v10.2, will be removed in TYPO3 v11. Use the PSR-14 event instead.
     */
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
     * Whether identifiers contain hierarchy information (folder structure).
     */
    const CAPABILITY_HIERARCHICAL_IDENTIFIERS = 8;
    /**
     * Name of the default processing folder
     */
    const DEFAULT_ProcessingFolder = '_processed_';
}
