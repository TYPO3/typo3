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
 * Looking for double files
 */
class DoubleFilesCommand extends CleanerCommand
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
        $this->cli_help['name'] = 'double_files -- Looking for files from TYPO3 managed records which are referenced more than one time (only one time allowed)';
        $this->cli_help['description'] = trim('
Assumptions:
- a perfect integrity of the reference index table (always update the reference index table before using this tool!)
- files found in deleted records are included (otherwise you would see a false list of lost files)

Files attached to records in TYPO3 using a "group" type configuration in TCA or FlexForm DataStructure are managed exclusively by the system and there must always exist a 1-1 reference between the file and the reference in the record.
This tool will expose when such files are referenced from multiple locations which is considered an integrity error.
If a multi-reference is found it was typically created because the record was copied or modified outside of TCEmain which will otherwise maintain the relations correctly.
Multi-references should be resolved to 1-1 references as soon as possible. The danger of keeping multi-references is that if the file is removed from one of the refering records it will actually be deleted in the file system, leaving missing files for the remaining referers!

Automatic Repair of Errors:
- The multi-referenced file is copied under a new name and references updated.

Manual repair suggestions:
- None that can not be handled by the automatic repair.
');
        $this->cli_help['examples'] = '/.../cli_dispatch.phpsh lowlevel_cleaner double_files -s -r
This will check the system for double files relations.';
    }

    /**
     * Find managed files which are referred to more than one time
     * Fix methods: API in \TYPO3\CMS\Core\Database\ReferenceIndex that allows to
     * change the value of a reference (we could copy the file) or remove reference
     *
     * @return array
     */
    public function main()
    {
        // Initialize result array:
        $resultArray = [
            'message' => $this->cli_help['name'] . LF . LF . $this->cli_help['description'],
            'headers' => [
                'multipleReferencesList_count' => ['Number of multi-reference files', '(See below)', 0],
                'singleReferencesList_count' => ['Number of files correctly referenced', 'The amount of correct 1-1 references', 0],
                'multipleReferencesList' => ['Entries with files having multiple references', 'These are serious problems that should be resolved ASAP to prevent data loss! ' . $this->label_infoString, 3],
                'dirname_registry' => ['Registry of directories in which files are found.', 'Registry includes which table/field pairs store files in them plus how many files their store.', 0],
                'missingFiles' => ['Tracking missing files', '(Extra feature, not related to tracking of double references. Further, the list may include more files than found in the missing_files()-test because this list includes missing files from deleted records.)', 0],
                'warnings' => ['Warnings picked up', '', 2]
            ],
            'multipleReferencesList_count' => ['count' => 0],
            'singleReferencesList_count' => ['count' => 0],
            'multipleReferencesList' => [],
            'dirname_registry' => [],
            'missingFiles' => [],
            'warnings' => []
        ];
        // Select all files in the reference table not found by a soft reference parser (thus TCA configured)
        $recs = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_refindex', 'ref_table=' . $GLOBALS['TYPO3_DB']->fullQuoteStr('_FILE', 'sys_refindex') . ' AND softref_key=' . $GLOBALS['TYPO3_DB']->fullQuoteStr('', 'sys_refindex'), '', 'sorting DESC');
        // Traverse the files and put into a large table:
        $tempCount = [];
        if (is_array($recs)) {
            foreach ($recs as $rec) {
                // Compile info string for location of reference:
                $infoString = $this->infoStr($rec);
                // Registering occurencies in directories:
                $resultArray['dirname_registry'][dirname($rec['ref_string'])][$rec['tablename'] . ':' . $rec['field']]++;
                // Handle missing file:
                if (!@is_file((PATH_site . $rec['ref_string']))) {
                    $resultArray['missingFiles'][$rec['ref_string']][$rec['hash']] = $infoString;
                    ksort($resultArray['missingFiles'][$rec['ref_string']]);
                }
                // Add entry if file has multiple references pointing to it:
                if (isset($tempCount[$rec['ref_string']])) {
                    if (!is_array($resultArray['multipleReferencesList'][$rec['ref_string']])) {
                        $resultArray['multipleReferencesList'][$rec['ref_string']] = [];
                        $resultArray['multipleReferencesList'][$rec['ref_string']][$tempCount[$rec['ref_string']][1]] = $tempCount[$rec['ref_string']][0];
                    }
                    $resultArray['multipleReferencesList'][$rec['ref_string']][$rec['hash']] = $infoString;
                    ksort($resultArray['multipleReferencesList'][$rec['ref_string']]);
                } else {
                    $tempCount[$rec['ref_string']] = [$infoString, $rec['hash']];
                }
            }
        }
        ksort($resultArray['missingFiles']);
        ksort($resultArray['multipleReferencesList']);
        // Add count for multi-references:
        $resultArray['multipleReferencesList_count']['count'] = count($resultArray['multipleReferencesList']);
        $resultArray['singleReferencesList_count']['count'] = count($tempCount) - $resultArray['multipleReferencesList_count']['count'];
        // Sort dirname registry and add warnings for directories outside uploads/
        ksort($resultArray['dirname_registry']);
        foreach ($resultArray['dirname_registry'] as $dir => $temp) {
            ksort($resultArray['dirname_registry'][$dir]);
            if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($dir, 'uploads/')) {
                $resultArray['warnings'][\TYPO3\CMS\Core\Utility\GeneralUtility::shortmd5($dir)] = 'Directory "' . $dir . '" was outside uploads/ which is unusual practice in TYPO3 although not forbidden. Directory used by the following table:field pairs: ' . implode(',', array_keys($temp));
            }
        }
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
        foreach ($resultArray['multipleReferencesList'] as $key => $value) {
            $absFileName = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($key);
            if ($absFileName && @is_file($absFileName)) {
                echo 'Processing file: ' . $key . LF;
                $c = 0;
                foreach ($value as $hash => $recReference) {
                    if ($c == 0) {
                        echo '	Keeping ' . $key . ' for record "' . $recReference . '"' . LF;
                    } else {
                        // Create unique name for file:
                        $fileFunc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Utility\File\BasicFileUtility::class);
                        $newName = $fileFunc->getUniqueName(basename($key), dirname($absFileName));
                        echo '	Copying ' . $key . ' to ' . \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($newName) . ' for record "' . $recReference . '": ';
                        if ($bypass = $this->cli_noExecutionCheck($recReference)) {
                            echo $bypass;
                        } else {
                            \TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move($absFileName, $newName);
                            clearstatcache();
                            if (@is_file($newName)) {
                                $sysRefObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ReferenceIndex::class);
                                $error = $sysRefObj->setReferenceValue($hash, basename($newName));
                                if ($error) {
                                    echo '	ERROR:	TYPO3\\CMS\\Core\\Database\\ReferenceIndex::setReferenceValue(): ' . $error . LF;
                                    die;
                                } else {
                                    echo 'DONE';
                                }
                            } else {
                                echo '	ERROR: File "' . $newName . '" was not created!';
                            }
                        }
                        echo LF;
                    }
                    $c++;
                }
            } else {
                echo '	ERROR: File "' . $absFileName . '" was not found!';
            }
        }
    }
}
