<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Georg Ringer <typo3@ringerge.org>
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
$LANG->includeLLFile('EXT:taskcenter/task/locallang.xlf');
$BE_USER->modAccess($MCONF, 1);
/*
 * @deprecated since 6.0, the classname SC_mod_user_task_index and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/taskcenter/Classes/Controller/TaskModuleController.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('taskcenter') . 'Classes/Controller/TaskModuleController.php';
// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Taskcenter\\Controller\\TaskModuleController');
// Include files?
foreach ($SOBE->include_once as $INC_FILE) {
	include_once $INC_FILE;
}
$SOBE->main();
$SOBE->printContent();
?>