<?php
namespace TYPO3\CMS\Install\Controller\Action\Tool;

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

use TYPO3\CMS\Install\Controller\Action;

/**
 * Handle folder structure
 */
class FolderStructure extends Action\AbstractAction
{
    /**
     * Executes the tool
     *
     * @return string Rendered content
     */
    protected function executeAction()
    {
        /** @var $folderStructureFactory \TYPO3\CMS\Install\FolderStructure\DefaultFactory */
        $folderStructureFactory = $this->objectManager->get(\TYPO3\CMS\Install\FolderStructure\DefaultFactory::class);
        /** @var $structureFacade \TYPO3\CMS\Install\FolderStructure\StructureFacade */
        $structureFacade = $folderStructureFactory->getStructure();

        $fixedStatusObjects = [];
        if (isset($this->postValues['set']['fix'])) {
            $fixedStatusObjects = $structureFacade->fix();
        }

        $statusObjects = $structureFacade->getStatus();
        /** @var $statusUtility \TYPO3\CMS\Install\Status\StatusUtility */
        $statusUtility = $this->objectManager->get(\TYPO3\CMS\Install\Status\StatusUtility::class);

        $errorStatus = array_merge(
            $statusUtility->filterBySeverity($statusObjects, 'error'),
            $statusUtility->filterBySeverity($statusObjects, 'warning')
        );
        $okStatus = array_merge(
            $statusUtility->filterBySeverity($statusObjects, 'notice'),
            $statusUtility->filterBySeverity($statusObjects, 'information'),
            $statusUtility->filterBySeverity($statusObjects, 'ok')
        );

        /** @var \TYPO3\CMS\Install\FolderStructure\DefaultPermissionsCheck $permissionCheck */
        $permissionCheck = $this->objectManager->get(\TYPO3\CMS\Install\FolderStructure\DefaultPermissionsCheck::class);
        $filePermissionStatus = $permissionCheck->getMaskStatus('fileCreateMask');
        $directoryPermissionStatus = $permissionCheck->getMaskStatus('folderCreateMask');

        $this->view
            ->assign('filePermissionStatus', $filePermissionStatus)
            ->assign('directoryPermissionStatus', $directoryPermissionStatus)
            ->assign('fixedStatus', $fixedStatusObjects)
            ->assign('errorStatus', $errorStatus)
            ->assign('okStatus', $okStatus);

        return $this->view->render();
    }
}
