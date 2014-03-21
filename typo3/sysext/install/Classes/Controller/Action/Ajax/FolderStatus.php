<?php
namespace TYPO3\CMS\Install\Controller\Action\Ajax;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Wouter Wolters <typo3@wouterwolters.nl>
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

/**
 * Folder status check for errors
 */
class FolderStatus extends AbstractAjaxAction {

	/**
	 * Get folder status errors
	 *
	 * @return string
	 */
	protected function executeAction() {
		// Count of folder structure errors are displayed in left navigation menu
		/** @var $folderStructureFacade \TYPO3\CMS\Install\FolderStructure\StructureFacade */
		$folderStructureFacade = $this->objectManager->get('TYPO3\\CMS\\Install\\FolderStructure\\DefaultFactory')->getStructure();
		$folderStatus = $folderStructureFacade->getStatus();

		/** @var $permissionCheck \TYPO3\CMS\Install\FolderStructure\DefaultPermissionsCheck */
		$permissionCheck = $this->objectManager->get('TYPO3\\CMS\\Install\\FolderStructure\\DefaultPermissionsCheck');
		$folderStatus[] = $permissionCheck->getMaskStatus('fileCreateMask');
		$folderStatus[] = $permissionCheck->getMaskStatus('folderCreateMask');

		/** @var \TYPO3\CMS\Install\Status\StatusUtility $statusUtility */
		$statusUtility = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\StatusUtility');

		$folderStructureErrors = array_merge(
			$statusUtility->filterBySeverity($folderStatus, 'error'),
			$statusUtility->filterBySeverity($folderStatus, 'warning')
		);

		return count($folderStructureErrors);
	}
}