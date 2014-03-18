<?php
namespace TYPO3\CMS\Install\Controller\Action\Ajax;

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

/**
 * Abstract core update class contains general core update
 * related methods
 */
abstract class AbstractCoreUpdate extends AbstractAjaxAction {

	/**
	 * @var \TYPO3\CMS\Install\View\JsonView
	 * @inject
	 */
	protected $view = NULL;

	/**
	 * @var \TYPO3\CMS\Install\Service\CoreUpdateService
	 * @inject
	 */
	protected $coreUpdateService;

	/**
	 * @var \TYPO3\CMS\Install\Status\StatusUtility
	 * @inject
	 */
	protected $statusUtility;

	/**
	 * @var \TYPO3\CMS\Install\Service\CoreVersionService
	 * @inject
	 */
	protected $coreVersionService;

	/**
	 * Initialize the handle action, sets up fluid stuff and assigns default variables.
	 *
	 * @return void
	 * @throws \TYPO3\CMS\Install\Controller\Exception
	 */
	protected function initializeHandle() {
		if (!$this->coreUpdateService->isCoreUpdateEnabled()) {
			throw new \TYPO3\CMS\Install\Controller\Exception(
				'Core Update disabled in this environment',
				1381609294
			);
		}
		$this->loadExtLocalconfDatabaseAndExtTables();
	}

	/**
	 * Find out which version upgrade should be handled. This may
	 * be different depending on whether development or regular release.
	 *
	 * @throws \TYPO3\CMS\Install\Controller\Exception
	 * @return string Version to handle, eg. 6.2.2
	 */
	protected function getVersionToHandle() {
		$getVars = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('install');
		if (!isset($getVars['type'])) {
			throw new \TYPO3\CMS\Install\Controller\Exception(
				'Type must be set to either "regular" or "development"',
				1380975303
			);
		}
		$type = $getVars['type'];
		if ($type === 'development') {
			$versionToHandle = $this->coreVersionService->getYoungestPatchDevelopmentRelease();
		} else {
			$versionToHandle = $this->coreVersionService->getYoungestPatchRelease();
		}
		return $versionToHandle;
	}
}