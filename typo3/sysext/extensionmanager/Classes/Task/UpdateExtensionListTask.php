<?php
namespace TYPO3\CMS\Extensionmanager\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Update extension list from TER task
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class UpdateExtensionListTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

	/**
	 * Public method, called by scheduler.
	 *
	 * @return boolean TRUE on success
	 */
	public function execute() {
		// Throws exceptions if something went wrong
		$this->updateExtensionlist();

		return TRUE;
	}

	/**
	 * Update extension list
	 *
	 * @TODO: Adapt to multiple repositories if the Helper can handle this
	 * @return void
	 */
	protected function updateExtensionlist() {
		/** @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		/** @var $repositoryHelper \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper */
		$repositoryHelper = $objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\Repository\\Helper');
		$repositoryHelper->updateExtList();
	}
}
?>