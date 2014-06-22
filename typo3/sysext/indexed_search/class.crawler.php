<?php
/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Crawler hook for indexed search. Works with the "crawler" extension
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
// To make sure the backend charset is available:
if (!is_object($GLOBALS['LANG'])) {
	$GLOBALS['LANG'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Lang\\LanguageService');
	$GLOBALS['LANG']->init($GLOBALS['BE_USER']->uc['lang']);
}
