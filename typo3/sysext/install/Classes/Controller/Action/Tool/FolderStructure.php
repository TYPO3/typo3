<?php
namespace TYPO3\CMS\Install\Controller\Action\Tool;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Install\Controller\Action;

/**
 * Handle folder structure
 */
class FolderStructure extends Action\AbstractAction {

	/**
	 * Executes the tool
	 *
	 * @return string Rendered content
	 */
	protected function executeAction() {
		/** @var $folderStructureFactory \TYPO3\CMS\Install\FolderStructure\DefaultFactory */
		$folderStructureFactory = $this->objectManager->get('TYPO3\\CMS\\Install\\FolderStructure\\DefaultFactory');
		/** @var $structureFacade \TYPO3\CMS\Install\FolderStructure\StructureFacade */
		$structureFacade = $folderStructureFactory->getStructure();

		$fixedStatusObjects = array();
		if (isset($this->postValues['set']['fix'])) {
			$fixedStatusObjects = $structureFacade->fix();
		}

		$statusObjects = $structureFacade->getStatus();
		/** @var $statusUtility \TYPO3\CMS\Install\Status\StatusUtility */
		$statusUtility = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\StatusUtility');

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
		$permissionCheck = $this->objectManager->get('TYPO3\\CMS\\Install\\FolderStructure\\DefaultPermissionsCheck');
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
