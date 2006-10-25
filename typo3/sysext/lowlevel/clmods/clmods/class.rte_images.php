<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Cleaner module: RTE magicc images
 * User function called from tx_lowlevel_cleaner_core configured in ext_localconf.php
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   56: class tx_lowlevel_rte_images extends tx_lowlevel_cleaner_core
 *   65:     function tx_lowlevel_rte_images()
 *   99:     function main()
 *  181:     function main_autoFix($resultArray)
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * Looking for RTE images integrity
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_lowlevel
 */
class tx_lowlevel_rte_images extends tx_lowlevel_cleaner_core {

	var $checkRefIndex = TRUE;

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	function tx_lowlevel_rte_images()	{
		parent::tx_lowlevel_cleaner_core();

			// Setting up help:
		$this->cli_help['name'] = 'rte_images -- Looking up all occurencies of RTEmagic images in the database and check existence of parent and copy files on the file system plus report possibly lost files of this type.';
		$this->cli_help['description'] = trim('
Assumptions:
- a perfect integrity of the reference index table (always update the reference index table before using this tool!)
- that all RTEmagic image files in the database are registered with the soft reference parser "images"
- images found in deleted records are included (means that you might find lost RTEmagic images after flushing deleted records)

The assumptions are not requirements by the TYPO3 API but reflects the de facto implementation of most TYPO3 installations.
However, many custom fields using an RTE will probably not have the "images" soft reference parser registered and so the index will be incomplete and not listing all RTEmagic image files.
The consequence of this limitation is that you should be careful if you wish to delete lost RTEmagic images - they could be referenced from a field not parsed by the "images" soft reference parser!
Another limitation: In theory a RTEmagic image should be used from only one record, however TCEmain does not support this (unfortunately!) so when a record is copied or versioned no new version will be produced. This leads to a usage count of more than one for many RTEmagic images which is also shown in the overview. At this point in time its not considered a bug and there is no fix for it.

Automatic Repair of Errors:
- There is currently no automatic repair available

Manual repair suggestions:
- Missing files: Re-insert missing files or edit record where the reference is found.
- Lost files: Delete them if you do not recognize them as used somewhere the system does not know about.
');

		$this->cli_help['examples'] = '/.../cli_dispatch.phpsh lowlevel_cleaner rte_images -s -r
Reports problems with RTE images';
	}

	/**
	 * Analyse situation with RTE magic images. (still to define what the most useful output is).
	 * Fix methods: API in t3lib_refindex that allows to change the value of a reference (we could copy the files) or remove reference
	 *
	 * @return	array
	 */
	function main() {
			global $TYPO3_DB;

			// Initialize result array:
		$resultArray = array(
			'message' => $this->cli_help['name'].chr(10).chr(10).$this->cli_help['description'],
			'headers' => array(
				'completeFileList' => array('Complete list of used RTEmagic files','Both parent and copy are listed here including usage count (which should in theory all be "1"). This list does not exclude files that might be missing.',1),
				'RTEmagicFilePairs' => array('Statistical info about RTEmagic files','(copy used as index)',0),
				'missingFiles' => array('Missing RTEmagic image files','These files are not found in the file system! Should be corrected!',3),
				'lostFiles' => array('Lost RTEmagic files from uploads/','These files you might be able to delete but only if _all_ RTEmagic images are found by the soft reference parser. If you are using the RTE in third-party extensions it is likely that the soft reference parser is not applied correctly to their RTE and thus these "lost" files actually represent valid RTEmagic images, just not registered.',2),
				'warnings' => array('Warnings picked up','',2)
			),
			'RTEmagicFilePairs' => array(),
			'completeFileList' => array(),
			'missingFiles' => array(),
			'lostFiles' => array(),
			'warnings' => array()
		);

			// Select all RTEmagic files in the reference table (only from soft references of course)
		$recs = $TYPO3_DB->exec_SELECTgetRows(
			'*',
			'sys_refindex',
			'ref_table='.$TYPO3_DB->fullQuoteStr('_FILE', 'sys_refindex').
				' AND ref_string LIKE '.$TYPO3_DB->fullQuoteStr('%/RTEmagic%', 'sys_refindex').
				' AND softref_key='.$TYPO3_DB->fullQuoteStr('images', 'sys_refindex'),
			'',
			'sorting DESC'
		);

			// Traverse the files and put into a large table:
		if (is_array($recs)) {
			foreach($recs as $rec)	{
				$filename = basename($rec['ref_string']);
				if (t3lib_div::isFirstPartOfStr($filename,'RTEmagicC_'))	{
					$original = 'RTEmagicP_'.ereg_replace('\.[[:alnum:]]+$','',substr($filename,10));
					$infoString = $this->infoStr($rec);

						// Build index:
					$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['exists'] = @is_file(PATH_site.$rec['ref_string']);
					$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['original'] = substr($rec['ref_string'],0,-strlen($filename)).$original;
					$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['original_exists'] = @is_file(PATH_site.$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['original']);
					$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['count']++;
					$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['usedIn'][$rec['hash']] = $infoString;

					$resultArray['completeFileList'][$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['original']]++;
					$resultArray['completeFileList'][$rec['ref_string']]++;

						// Missing files:
					if (!$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['exists'])	{
						$resultArray['missingFiles'][$rec['ref_string']] = $resultArray['RTEmagicFilePairs'][$rec['ref_string']]['usedIn'];
					}
					if (!$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['original_exists'])	{
						$resultArray['missingFiles'][$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['original']] = $resultArray['RTEmagicFilePairs'][$rec['ref_string']]['usedIn'];
					}
				}
			}
		}

			// Now, ask for RTEmagic files inside uploads/ folder:
		$cleanerModules = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lowlevel']['cleanerModules'];
		$cleanerMode = &t3lib_div::getUserObj($cleanerModules['lost_files'][0]);
		$resLostFiles = $cleanerMode->main(array(),FALSE,TRUE);
		if (is_array($resLostFiles['RTEmagicFiles']))	{
			foreach($resLostFiles['RTEmagicFiles'] as $fileName) {
				if (!isset($resultArray['completeFileList'][$fileName])) 	{
					$resultArray['lostFiles'][] = $fileName;
				}
			}
		}

		return $resultArray;
	}

	/**
	 * Mandatory autofix function
	 * Will run auto-fix on the result array. Echos status during processing.
	 *
	 * @param	array		Result array from main() function
	 * @return	void
	 */
	function main_autoFix($resultArray)	{
		echo "There is currently no automatic repair available\n";
	}
}

?>