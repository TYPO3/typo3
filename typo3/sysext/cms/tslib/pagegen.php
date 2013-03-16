<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Generating the TypoScript based page.
 * Must be included from index_ts.php
 * Revised for TYPO3 3.6 June/2003 by Kasper Skårhøj
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
if (!is_object($TSFE)) {
	die('You cannot execute this file directly. It\'s meant to be included from index_ts.php');
}
$TT->push('pagegen.php, initialize');
// Initialization of some variables
\TYPO3\CMS\Frontend\Page\PageGenerator::pagegenInit();
// Global content object...
$GLOBALS['TSFE']->newCObj();
// LIBRARY INCLUSION, TypoScript
$temp_incFiles = \TYPO3\CMS\Frontend\Page\PageGenerator::getIncFiles();
foreach ($temp_incFiles as $temp_file) {
	include_once './' . $temp_file;
}
$TT->pull();
// Content generation
// If this is an array, it's a sign that this script is included in order to include certain INT-scripts
if (!$GLOBALS['TSFE']->isINTincScript()) {
	$TT->push('pagegen.php, render');
	\TYPO3\CMS\Frontend\Page\PageGenerator::renderContent();
	$GLOBALS['TSFE']->setAbsRefPrefix();
	$TT->pull();
}
?>