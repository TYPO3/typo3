<?php
namespace TYPO3\CMS\Lowlevel;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Cleaner module: syslog
 * User function called from tx_lowlevel_cleaner_core configured in ext_localconf.php
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * syslog
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class SyslogCommand extends CleanerCommand {

	/**
	 * Constructor
	 *
	 * @todo Define visibility
	 */
	public function __construct() {
		parent::__construct();
		$this->cli_help['name'] = 'syslog -- Show entries from syslog';
		$this->cli_help['description'] = trim('
Showing last 25 hour entries from the syslog. More features pending. This is the most basic and can be useful for nightly check test reports.
');
		$this->cli_help['examples'] = '';
	}

	/**
	 * Find syslog
	 *
	 * @return array
	 * @todo Define visibility
	 */
	public function main() {
		global $TYPO3_DB;
		// Initialize result array:
		$resultArray = array(
			'message' => $this->cli_help['name'] . LF . LF . $this->cli_help['description'],
			'headers' => array(
				'listing' => array('', '', 1),
				'allDetails' => array('', '', 0)
			),
			'listing' => array(),
			'allDetails' => array()
		);
		$rows = $TYPO3_DB->exec_SELECTgetRows('*', 'sys_log', 'tstamp>' . ($GLOBALS['EXEC_TIME'] - 25 * 3600));
		foreach ($rows as $r) {
			$l = unserialize($r['log_data']);
			$explained = '#' . $r['uid'] . ' ' . \TYPO3\CMS\Backend\Utility\BackendUtility::datetime($r['tstamp']) . ' USER[' . $r['userid'] . ']: ' . sprintf($r['details'], $l[0], $l[1], $l[2], $l[3], $l[4], $l[5]);
			$resultArray['listing'][$r['uid']] = $explained;
			$resultArray['allDetails'][$r['uid']] = array($explained, \TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString($r, 'uid,userid,action,recuid,tablename,recpid,error,tstamp,type,details_nr,IP,event_pid,NEWid,workspace'));
		}
		return $resultArray;
	}

	/**
	 * Mandatory autofix function
	 * Will run auto-fix on the result array. Echos status during processing.
	 *
	 * @param array Result array from main() function
	 * @return void
	 * @todo Define visibility
	 */
	public function main_autoFix($resultArray) {

	}

}


?>