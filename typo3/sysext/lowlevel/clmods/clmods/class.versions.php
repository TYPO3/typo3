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
 * Cleaner module: Versions of records
 * User function called from tx_lowlevel_cleaner_core configured in ext_localconf.php
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   56: class tx_lowlevel_versions extends tx_lowlevel_cleaner_core
 *   63:     function tx_lowlevel_versions()
 *   88:     function main()
 *  122:     function main_autoFix($resultArray)
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * Looking for versions of records
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_lowlevel
 */
class tx_lowlevel_versions extends tx_lowlevel_cleaner_core {

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	function tx_lowlevel_versions()	{
		parent::tx_lowlevel_cleaner_core();

			// Setting up help:
		$this->cli_options[] = array('--echotree level', 'When "level" is set to 1 or higher you will see the page of the page tree outputted as it is traversed. A value of 2 for "level" will show even more information.');
		$this->cli_options[] = array('--pid id', 'Setting start page in page tree. Default is the page tree root, 0 (zero)');
		$this->cli_options[] = array('--depth int', 'Setting traversal depth. 0 (zero) will only analyse start page (see --pid), 1 will traverse one level of subpages etc.');
		
		$this->cli_options[] = array('--flush-live', 'If set, not only published versions from Live workspace are flushed, but ALL versions from Live workspace (which are offline of course)');

		$this->cli_help['name'] = 'versions -- To find information about versions and workspaces in the system';
		$this->cli_help['description'] = trim('
Traversing page tree and finding versions, categorizing them by various properties.
Published versions from the Live workspace are registered. So are all offline versions from Live workspace in general. Further, versions in non-existing workspaces are found.

Automatic Repair:
- Deleting (completely) published versions from LIVE workspace OR _all_ offline versions from Live workspace (toogle by --flush-live)
- Resetting workspace for versions where workspace is deleted. (You might want to run this tool again after this operation to clean out those new elements in the Live workspace)
- Deleting unused placeholders
');

		$this->cli_help['examples'] = '';
	}

	/**
	 * Find orphan records
	 * VERY CPU and memory intensive since it will look up the whole page tree!
	 *
	 * @return	array
	 */
	function main() {
		global $TYPO3_DB;

			// Initialize result array:
		$resultArray = array(
			'message' => $this->cli_help['name'].chr(10).chr(10).$this->cli_help['description'],
			'headers' => array(
				'versions' => array('All versions','Showing all versions of records found',0),
				'versions_published' => array('All published versions','This is all records that has been published and can therefore be removed permanently',1),
				'versions_liveWS' => array('All versions in Live workspace','This is all records that are offline versions in the Live workspace. You may wish to flush these if you only use workspaces for versioning since then you might find lots of versions piling up in the live workspace which have simply been disconnected from the workspace before they were published.',1),
				'versions_lost_workspace' => array('Versions outside a workspace','Versions that has lost their connection to a workspace in TYPO3.',3),
				'versions_inside_versioned_page' => array('Versions in versions','Versions inside an already versioned page. Something that is confusing to users and therefore should not happen but is technically possible.',2),
				'versions_unused_placeholders' => array('Unused placeholder records','Placeholder records which are not used anymore by offline versions.',2)
			),
			'versions' => array(),
		);

		$startingPoint = $this->cli_isArg('--pid') ? t3lib_div::intInRange($this->cli_argValue('--pid'),0) : 0;
		$depth = $this->cli_isArg('--depth') ? t3lib_div::intInRange($this->cli_argValue('--depth'),0) : 1000;
		$this->genTree($startingPoint,$depth,(int)$this->cli_argValue('--echotree'));

		$resultArray['versions'] = $this->recStats['versions'];
		$resultArray['versions_published'] = $this->recStats['versions_published'];
		$resultArray['versions_liveWS'] = $this->recStats['versions_liveWS'];
		$resultArray['versions_lost_workspace'] = $this->recStats['versions_lost_workspace'];
		$resultArray['versions_inside_versioned_page'] = $this->recStats['versions_inside_versioned_page'];

			// Finding all placeholders with no records attached!
		$resultArray['versions_unused_placeholders'] = array(); 
		foreach($GLOBALS['TCA'] as $table => $cfg)	{
			if ($cfg['ctrl']['versioningWS'])	{
				$placeHolders = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,pid',$table,'t3ver_state=1 AND pid>=0'.t3lib_BEfunc::deleteClause($table));
				foreach($placeHolders as $phrec)	{
					if (count(t3lib_BEfunc::selectVersionsOfRecord($table, $phrec['uid'], 'uid'))<=1)	{
						$resultArray['versions_unused_placeholders'][] = $table.':'.$phrec['uid']; 
					}
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

		$kk = $this->cli_isArg('--flush-live') ? 'versions_liveWS' : 'versions_published';

			// Putting "pages" table in the bottom:
		if (isset($resultArray[$kk]['pages']))	{
			$_pages = $resultArray[$kk]['pages'];
			unset($resultArray[$kk]['pages']);
			$resultArray[$kk]['pages'] = $_pages;
		}

			// Traversing records:
		foreach($resultArray[$kk] as $table => $list)	{
			echo 'Flushing published records from table "'.$table.'":'.chr(10);
			foreach($list as $uid)	{
				echo '	Flushing record "'.$table.':'.$uid.'": ';

				if ($bypass = $this->cli_noExecutionCheck($table.':'.$uid))	{
					echo $bypass;
				} else {

						// Execute CMD array:
					$tce = t3lib_div::makeInstance('t3lib_TCEmain');
					$tce->stripslashes_values = FALSE;
					$tce->start(array(),array());
					$tce->deleteEl($table,$uid, TRUE, TRUE);

						// Return errors if any:
					if (count($tce->errorLog))	{
						echo '	ERROR from "TCEmain":'.chr(10).'TCEmain:'.implode(chr(10).'TCEmain:',$tce->errorLog);
					} else echo 'DONE';
				}
				echo chr(10);
			}
		}

			// Traverse workspace:
		foreach($resultArray['versions_lost_workspace'] as $table => $list)	{
			echo 'Resetting workspace to zero for records from table "'.$table.'":'.chr(10);
			foreach($list as $uid)	{
				echo '	Flushing record "'.$table.':'.$uid.'": ';
				if ($bypass = $this->cli_noExecutionCheck($table.':'.$uid))	{
					echo $bypass;
				} else {
					$fields_values = array(
						't3ver_wsid' => 0
					);
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table,'uid='.intval($uid),$fields_values);
					echo 'DONE';
				}
				echo chr(10);
			}
		}
		
			// Delete unused placeholders
		foreach($resultArray['versions_unused_placeholders'] as $recID)	{
			list($table,$uid)	= explode(':',$recID);
			echo 'Deleting unused placeholder (soft) "'.$table.':'.$uid.'": ';
			if ($bypass = $this->cli_noExecutionCheck($table.':'.$uid))	{
				echo $bypass;
			} else {

					// Execute CMD array:
				$tce = t3lib_div::makeInstance('t3lib_TCEmain');
				$tce->stripslashes_values = FALSE;
				$tce->start(array(),array());
				$tce->deleteAction($table, $uid);

					// Return errors if any:
				if (count($tce->errorLog))	{
					echo '	ERROR from "TCEmain":'.chr(10).'TCEmain:'.implode(chr(10).'TCEmain:',$tce->errorLog);
				} else echo 'DONE';			
			}
			echo chr(10);
		}		
	}
}

?>