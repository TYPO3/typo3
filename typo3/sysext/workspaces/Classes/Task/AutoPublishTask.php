<?php
namespace TYPO3\CMS\Workspaces\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
 * This class provides a wrapper around the autopublication
 * mechanism of workspaces, as a Scheduler task
 *
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 */
class AutoPublishTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

	/**
	 * Method executed from the Scheduler.
	 * Call on the workspace logic to publish workspaces whose publication date
	 * is in the past
	 *
	 * @return 	boolean
	 */
	public function execute() {
		$autopubObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\AutoPublishService');
		// Publish the workspaces that need to be
		$autopubObj->autoPublishWorkspaces();
		// There's no feedback from the publishing process,
		// so there can't be any failure.
		// TODO: This could certainly be improved.
		return TRUE;
	}

}


?>