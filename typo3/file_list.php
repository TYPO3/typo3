<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Steffen Kamper (steffen@typo3.com)
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
 * This is a wrapper file for direct calls to filelist module.
 * It's deprecated since 4.5, use proper link generation.
 *
 * @author	Steffen Kamper <steffen@typo3.com>
 * @deprecated since 4.5, will be removed in 6.0.
 *
 */

require ('init.php');

$query = t3lib_div::getIndpEnv('QUERY_STRING');
t3lib_div::deprecationLog('file_list.php is deprecated since TYPO3 4.5, this file will be removed in TYPO3 6.0. The filelist module is a system extension now, do not link to this file.' .
	LF .
	'Referer: ' . t3lib_div::getIndpEnv('HTTP_REFERER')
);
if (t3lib_extMgm::isLoaded('filelist')) {
	t3lib_utility_Http::redirect(t3lib_extMgm::extRelPath('filelist') . 'mod1/file_list.php?' . $query);
} else {
	$title = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:extension.not.installed'), 'list');
	$message = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:link.to.filelist.correctly');
	throw new RuntimeException($title . ': ' . $message, 1294586842);
}
?>