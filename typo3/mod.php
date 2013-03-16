<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2006-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Module Dispatch script
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
unset($MCONF);
require 'init.php';
// Find module path:
$temp_M = (string) \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('M');
$isDispatched = FALSE;
if ($temp_path = $TBE_MODULES['_PATHS'][$temp_M]) {
	$MCONF['_'] = 'mod.php?M=' . rawurlencode($temp_M);
	require $temp_path . 'conf.php';
	$BACK_PATH = '';
	require $temp_path . 'index.php';
	$isDispatched = TRUE;
} else {
	if (is_array($TBE_MODULES['_dispatcher'])) {
		foreach ($TBE_MODULES['_dispatcher'] as $dispatcherClassName) {
			$dispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get($dispatcherClassName);
			if ($dispatcher->callModule($temp_M) === TRUE) {
				$isDispatched = TRUE;
				break;
			}
		}
	}
}
if ($isDispatched === FALSE) {
	throw new UnexpectedValueException('No module "' . htmlspecialchars($temp_M) . '" could be found.', 1294585070);
}
\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->shutdown();
?>