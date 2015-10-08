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

/**
 * Update extension list from TER task
 */
class UpdateExtensionListTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{
    /**
     * Public method, called by scheduler.
     *
     * @return bool TRUE on success
     */
    public function execute()
    {
        // Throws exceptions if something went wrong
        $this->updateExtensionList();

        return true;
    }

    /**
     * Update extension list
     *
     * @TODO: Adapt to multiple repositories if the Helper can handle this
     * @return void
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
     */
    protected function updateExtensionList()
    {
        /** @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);

        /** @var $repositoryHelper \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper */
        $repositoryHelper = $objectManager->get(\TYPO3\CMS\Extensionmanager\Utility\Repository\Helper::class);
        $repositoryHelper->updateExtList();

        /** @var $persistenceManager \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager */
        $persistenceManager = $objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class);
        $persistenceManager->persistAll();
    }
}
