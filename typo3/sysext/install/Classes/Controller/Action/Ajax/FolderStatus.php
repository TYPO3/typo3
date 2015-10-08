<?php
namespace TYPO3\CMS\Install\Controller\Action\Ajax;

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
 * Folder status check for errors
 */
class FolderStatus extends AbstractAjaxAction
{
    /**
     * Get folder status errors
     *
     * @return string
     */
    protected function executeAction()
    {
        // Count of folder structure errors are displayed in left navigation menu
        /** @var $folderStructureFacade \TYPO3\CMS\Install\FolderStructure\StructureFacade */
        $folderStructureFacade = $this->objectManager->get(\TYPO3\CMS\Install\FolderStructure\DefaultFactory::class)->getStructure();
        $folderStatus = $folderStructureFacade->getStatus();

        /** @var $permissionCheck \TYPO3\CMS\Install\FolderStructure\DefaultPermissionsCheck */
        $permissionCheck = $this->objectManager->get(\TYPO3\CMS\Install\FolderStructure\DefaultPermissionsCheck::class);
        $folderStatus[] = $permissionCheck->getMaskStatus('fileCreateMask');
        $folderStatus[] = $permissionCheck->getMaskStatus('folderCreateMask');

        /** @var \TYPO3\CMS\Install\Status\StatusUtility $statusUtility */
        $statusUtility = $this->objectManager->get(\TYPO3\CMS\Install\Status\StatusUtility::class);

        $folderStructureErrors = array_merge(
            $statusUtility->filterBySeverity($folderStatus, 'error'),
            $statusUtility->filterBySeverity($folderStatus, 'warning')
        );

        return count($folderStructureErrors);
    }
}
