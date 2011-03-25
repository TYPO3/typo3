<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Publishing pages to static
 *
 * Is included from index_ts.php
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 */


 /*

 TODO:

- Show publish-dir in interface
- enabled checkboxes to select pages / frames
- which-frames selecteble by TS
- disable publishing of hidden/starttime/endtime/fe_group pages.
- remove published files option
- enable writing of images
- Policy: HTML-files overridden always, mediafiles are only overwritten if mtime is different.

 */



if (!is_object($TSFE))	{die('You cannot execute this file directly. It\'s meant to be included from index_ts.php');}


	// Storing the TSFE object
$temp_publish_TSFE = $TSFE;
$TT->push('Publishing','');
$temp_publish_pages = explode(',', $BE_USER->adminPanel->getExtPublishList());
$temp_publish_imagesTotal = array();
$temp_publish_array = array();	// Collects the rendered pages.

foreach ($temp_publish_pages as $temp_publish_id) {
	$TT->push('Page '.$temp_publish_id,'');
//debug($temp_publish_id);
		$TSFE = t3lib_div::makeInstance('tslib_fe', $TYPO3_CONF_VARS, $temp_publish_id, 0);

		$TSFE->initFEuser();
		$TSFE->clear_preview();
		$TSFE->determineId();
		$TSFE->initTemplate();
		$TSFE->getFromCache();

		$TSFE->getConfigArray();
		$TSFE->setUrlIdToken();
		if ($TSFE->isGeneratePage())	{
				$TSFE->generatePage_preProcessing();
				$temp_theScript=$TSFE->generatePage_whichScript();
				if ($temp_theScript)	{
					include($temp_theScript);
				} else {
					include(PATH_tslib.'pagegen.php');
				}
				$TSFE->generatePage_postProcessing();
		} elseif ($TSFE->isINTincScript())	{
			include(PATH_tslib.'pagegen.php');
		}

		// ********************************
		// $GLOBALS['TSFE']->config['INTincScript']
		// *******************************
		if ($TSFE->isINTincScript())		{
			$TT->push('Internal PHP-scripts','');
				$TSFE->INTincScript();
			$TT->pull();
		}

			// Get filename
		$temp_fileName = $TSFE->getSimulFileName();

		if (!isset($temp_publish_array[$temp_fileName]))	{	// If the page is not rendered allready, which will happen if a hidden page is 'published'
				// Images file
//			$temp_publish_row = $TSFE->getSearchCache();
//			$temp_publish_imagesOnPage= unserialize($temp_publish_row['tempFile_data']);
//			$temp_publish_imagesTotal = array_merge($temp_publish_imagesTotal, $temp_publish_imagesOnPage);
				// Store the data for this page:
			$temp_publish_array[$temp_fileName]= array($temp_publish_id, $temp_publish_imagesOnPage, $TSFE->content);
		}
	$TT->pull();
}
//debug($temp_publish_imagesTotal);
//debug(array_unique($temp_publish_imagesTotal));


// ***************************
// Publishing, writing files
// ***************************
$publishDir = $TYPO3_CONF_VARS['FE']['publish_dir'];
if ($publishDir && @is_dir($publishDir))	{
	$publishDir = rtrim($publishDir, '/').'/';
	debug('Publishing in: '.$publishDir, 'Publish');
	foreach ($temp_publish_array as $key => $val) {
		$file = $publishDir.$key;
		t3lib_div::writeFile($file,$val[2]);
		debug('Writing: '.$file, 'Publish');
	}
//	debug($temp_publish_array);
} else {
	debug('No publish_dir specified...');
}


$TT->pull();
	// Restoring the TSFE object
$TSFE = $temp_publish_TSFE;

?>