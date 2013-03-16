<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 François Suter <francois@typo3.org>
 *  (c) 2005-2013 Christian Jul Jensen <julle@typo3.org>
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
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('scheduler') . 'interfaces/interface.tx_scheduler_additionalfieldprovider.php';
$LANG->includeLLFile('EXT:scheduler/mod1/locallang.xml');
$BE_USER->modAccess($MCONF, 1);
// This checks permissions and exits if the users has no permission for entry.
// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Scheduler\\Controller\\SchedulerModuleController');
$SOBE->init();
// Include files?
foreach ($SOBE->include_once as $INC_FILE) {
	include_once $INC_FILE;
}
$SOBE->main();
$SOBE->render();
?>