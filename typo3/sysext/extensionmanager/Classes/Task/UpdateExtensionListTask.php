<?php
namespace TYPO3\CMS\Extensionmanager\Task;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extensionmanager\Utility\Repository\Helper;
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
    public function execute()
    {
        // Throws exceptions if something went wrong
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $objectManager->get(Helper::class)->updateExtList();
        $objectManager->get(PersistenceManager::class)->persistAll();
        return true;
    }
}
