<?php
namespace TYPO3\CMS\Lowlevel;

/*
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
 * syslog
 */
class SyslogCommand extends CleanerCommand
{
    /**
     * Constructor
     */
    public function __construct()
    {
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
     */
    public function main()
    {
        // Initialize result array:
        $resultArray = [
            'message' => $this->cli_help['name'] . LF . LF . $this->cli_help['description'],
            'headers' => [
                'listing' => ['', '', 1],
                'allDetails' => ['', '', 0]
            ],
            'listing' => [],
            'allDetails' => []
        ];
        $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_log', 'tstamp>' . ($GLOBALS['EXEC_TIME'] - 25 * 3600));
        foreach ($rows as $r) {
            $l = unserialize($r['log_data']);
            $userInformation = $r['userid'];
            if (!empty($l['originalUser'])) {
                $userInformation .= ' via ' . $l['originalUser'];
            }
            $explained = '#' . $r['uid'] . ' ' . \TYPO3\CMS\Backend\Utility\BackendUtility::datetime($r['tstamp']) . ' USER[' . $userInformation . ']: ' . sprintf($r['details'], $l[0], $l[1], $l[2], $l[3], $l[4], $l[5]);
            $resultArray['listing'][$r['uid']] = $explained;
            $resultArray['allDetails'][$r['uid']] = [$explained, \TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString($r, 'uid,userid,action,recuid,tablename,recpid,error,tstamp,type,details_nr,IP,event_pid,NEWid,workspace')];
        }
        return $resultArray;
    }

    /**
     * Mandatory autofix function
     * Will run auto-fix on the result array. Echos status during processing.
     *
     * @param array Result array from main() function
     * @return void
     */
    public function main_autoFix($resultArray)
    {
    }
}
