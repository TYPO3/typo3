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

namespace TYPO3\CMS\Extensionmanager\Task;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Update extension list from TER task
 * @internal This class is a specific EXT:scheduler task implementation and is not part of the Public TYPO3 API.
 */
class UpdateExtensionListTask extends AbstractTask
{
    /**
     * Public method, called by scheduler.
     *
     * @return bool TRUE on success
     */
    public function execute(): bool
    {
        // Throws exceptions if something went wrong
        $remoteRegistry = GeneralUtility::makeInstance(RemoteRegistry::class);
        foreach ($remoteRegistry->getListableRemotes() as $remote) {
            $remote->getAvailablePackages();
        }
        return true;
    }
}
