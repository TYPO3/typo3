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
 * Show system environment check results
 */
class SystemEnvironment extends Action\AbstractAction {

	/**
	 * Executes the tool
	 *
	 * @return string Rendered content
	 */
	protected function executeAction() {
		/** @var $statusCheck \TYPO3\CMS\Install\SystemEnvironment\Check */
		$statusCheck = $this->objectManager->get('TYPO3\\CMS\\Install\\SystemEnvironment\\Check');
		$statusObjects = $statusCheck->getStatus();

		/** @var $statusUtility \TYPO3\CMS\Install\Status\StatusUtility */
		$statusUtility = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\StatusUtility');
		$sortedStatusObjects = $statusUtility->sortBySeverity($statusObjects);
		$this->view->assign('statusObjectsBySeverity', $sortedStatusObjects);

		return $this->view->render();
	}

}
