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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Looking for missing relations.
 */
class MissingRelationsCommand extends CleanerCommand
{
    /**
     * @var bool
     */
    public $checkRefIndex = true;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        // Setting up help:
        $this->cli_help['name'] = 'missing_relations -- Find all record references pointing to a non-existing record.';
        $this->cli_help['description'] = trim('
Assumptions:
- a perfect integrity of the reference index table (always update the reference index table before using this tool!)
- all database references to check are integers greater than zero
- does not check if a referenced record is inside an offline branch, another workspace etc. which could make the reference useless in reality or otherwise question integrity
Records may be missing for these reasons (except software bugs):
- someone deleted the record which is technically not an error although it might be a mistake that someone did so.
- after flushing published versions and/or deleted-flagged records a number of new missing references might appear; those were pointing to records just flushed.

Automatic Repair of Errors:
- Only managed references are repaired (TCA-configured).
- Offline Version Records and Non Existing Records: Reference is removed

Manual repair suggestions:
- For soft references you should investigate each case and edit the content accordingly.
- References to deleted records can theoretically be removed since a deleted record cannot be selected and hence your website should not be affected by removal of the reference. On the other hand it does not hurt to ignore it for now. To have this automatically fixed you must first flush the deleted records after which remaining references will appear as pointing to Non Existing Records and can now be removed with the automatic fix.

NOTICE: Uses the Reference Index Table (sys_refindex) for analysis. Update it before use!
');
        $this->cli_help['examples'] = '/.../cli_dispatch.phpsh lowlevel_cleaner missing_relations -s -r
Reports missing relations';
    }

    /**
     * Find relations pointing to non-existing records
     * Fix methods: API in \TYPO3\CMS\Core\Database\ReferenceIndex that allows to
     * change the value of a reference (or remove it) [Only for managed relations!]
     *
     * @return array
     */
    public function main()
    {
        // Initialize result array:
        $listExplain = ' Shows the missing record as header and underneath a list of record fields in which the references are found. ' . $this->label_infoString;
        $resultArray = [
            'message' => $this->cli_help['name'] . LF . LF . $this->cli_help['description'],
            'headers' => [
                'offlineVersionRecords_m' => ['Offline version records (managed)', 'These records are offline versions having a pid=-1 and references should never occur directly to their uids.' . $listExplain, 3],
                'deletedRecords_m' => ['Deleted-flagged records (managed)', 'These records are deleted with a flag but references are still pointing at them. Keeping the references is useful if you undelete the referenced records later, otherwise the references are lost completely when the deleted records are flushed at some point. Notice that if those records listed are themselves deleted (marked with "DELETED") it is not a problem.' . $listExplain, 2],
                'nonExistingRecords_m' => ['Non-existing records to which there are references (managed)', 'These references can safely be removed since there is no record found in the database at all.' . $listExplain, 3],
                // 3 = error
                'offlineVersionRecords_s' => ['Offline version records (softref)', 'See above.' . $listExplain, 2],
                'deletedRecords_s' => ['Deleted-flagged records (softref)', 'See above.' . $listExplain, 2],
                'nonExistingRecords_s' => ['Non-existing records to which there are references (softref)', 'See above.' . $listExplain, 2]
            ],
            'offlineVersionRecords_m' => [],
            'deletedRecords_m' => [],
            'nonExistingRecords_m' => [],
            'offlineVersionRecords_s' => [],
            'deletedRecords_s' => [],
            'nonExistingRecords_s' => []
        ];
        // Select DB relations from reference table
        /** @var DatabaseConnection $db */
        $db = $GLOBALS['TYPO3_DB'];
        $result = $db->exec_SELECTquery('ref_uid,ref_table,softref_key,hash,tablename,recuid,field,flexpointer,deleted', 'sys_refindex', 'ref_table <> ' . $db->fullQuoteStr('_FILE', 'sys_refindex') . ' AND ref_uid > 0', '', 'sorting DESC');
        if ($result) {
            $tempExists = [];
            while ($rec = $db->sql_fetch_assoc($result)) {
                $suffix = $rec['softref_key'] != '' ? '_s' : '_m';
                $idx = $rec['ref_table'] . ':' . $rec['ref_uid'];
                // Get referenced record:
                if (!isset($tempExists[$idx])) {
                    $tempExists[$idx] = BackendUtility::getRecordRaw($rec['ref_table'], 'uid=' . (int)$rec['ref_uid'], 'uid,pid' . ($GLOBALS['TCA'][$rec['ref_table']]['ctrl']['delete'] ? ',' . $GLOBALS['TCA'][$rec['ref_table']]['ctrl']['delete'] : ''));
                }
                // Compile info string for location of reference:
                $infoString = $this->infoStr($rec);
                // Handle missing file:
                if ($tempExists[$idx]['uid']) {
                    if ($tempExists[$idx]['pid'] == -1) {
                        $resultArray['offlineVersionRecords' . $suffix][$idx][$rec['hash']] = $infoString;
                        ksort($resultArray['offlineVersionRecords' . $suffix][$idx]);
                    } elseif ($GLOBALS['TCA'][$rec['ref_table']]['ctrl']['delete'] && $tempExists[$idx][$GLOBALS['TCA'][$rec['ref_table']]['ctrl']['delete']]) {
                        $resultArray['deletedRecords' . $suffix][$idx][$rec['hash']] = $infoString;
                        ksort($resultArray['deletedRecords' . $suffix][$idx]);
                    }
                } else {
                    $resultArray['nonExistingRecords' . $suffix][$idx][$rec['hash']] = $infoString;
                    ksort($resultArray['nonExistingRecords' . $suffix][$idx]);
                }
            }
            $db->sql_free_result($result);
        }
        ksort($resultArray['offlineVersionRecords_m']);
        ksort($resultArray['deletedRecords_m']);
        ksort($resultArray['nonExistingRecords_m']);
        ksort($resultArray['offlineVersionRecords_s']);
        ksort($resultArray['deletedRecords_s']);
        ksort($resultArray['nonExistingRecords_s']);
        return $resultArray;
    }

    /**
     * Mandatory autofix function
     * Will run auto-fix on the result array. Echos status during processing.
     *
     * @param array $resultArray Result array from main() function
     * @return void
     */
    public function main_autoFix($resultArray)
    {
        $trav = ['offlineVersionRecords_m', 'nonExistingRecords_m'];
        foreach ($trav as $tk) {
            echo 'Processing managed "' . $tk . '"...' . LF;
            foreach ($resultArray[$tk] as $key => $value) {
                foreach ($value as $hash => $recReference) {
                    echo '	Removing reference to ' . $key . ' in record "' . $recReference . '": ';
                    if ($bypass = $this->cli_noExecutionCheck($recReference)) {
                        echo $bypass;
                    } else {
                        $sysRefObj = GeneralUtility::makeInstance(ReferenceIndex::class);
                        $error = $sysRefObj->setReferenceValue($hash, null);
                        if ($error) {
                            echo '		TYPO3\\CMS\\Core\\Database\\ReferenceIndex::setReferenceValue(): ' . $error . LF;
                        } else {
                            echo 'DONE';
                        }
                    }
                    echo LF;
                }
            }
        }
    }
}
