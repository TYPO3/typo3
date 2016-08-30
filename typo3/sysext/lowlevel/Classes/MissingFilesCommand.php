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
 * Looking for missing files.
 */
class MissingFilesCommand extends CleanerCommand
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
        $this->cli_help['name'] = 'missing_files -- Find all file references from records pointing to a missing (non-existing) file.';
        $this->cli_help['description'] = trim('
Assumptions:
- a perfect integrity of the reference index table (always update the reference index table before using this tool!)
- relevant soft reference parsers applied everywhere file references are used inline

Files may be missing for these reasons (except software bugs):
- someone manually deleted the file inside fileadmin/ or another user maintained folder. If the reference was a soft reference (opposite to a TCEmain managed file relation from "group" type fields), technically it is not an error although it might be a mistake that someone did so.
- someone manually deleted the file inside the uploads/ folder (typically containing managed files) which is an error since no user interaction should take place there.

Automatic Repair of Errors:
- Managed files (TCA/FlexForm attachments): Will silently remove the reference from the record since the file is missing. For this reason you might prefer a manual approach instead.
- Soft References: Requires manual fix if you consider it an error.

Manual repair suggestions:
- Managed files: You might be able to locate the file and re-insert it in the correct location. However, no automatic fix can do that for you.
- Soft References: You should investigate each case and edit the content accordingly. A soft reference to a file could be in an HTML image tag (for example <img src="missing_file.jpg" />) and you would have to either remove the whole tag, change the filename or re-create the missing file.
');
        $this->cli_help['examples'] = '/.../cli_dispatch.phpsh lowlevel_cleaner missing_files -s -r
This will show you missing files in the TYPO3 system and only report back if errors were found.';
    }

    /**
     * Find file references that points to non-existing files in system
     * Fix methods: API in \TYPO3\CMS\Core\Database\ReferenceIndex that allows to
     * change the value of a reference (or remove it)
     *
     * @return array
     */
    public function main()
    {
        // Initialize result array:
        $listExplain = ' Shows the relative filename of missing file as header and under a list of record fields in which the references are found. ' . $this->label_infoString;
        $resultArray = [
            'message' => $this->cli_help['name'] . LF . LF . $this->cli_help['description'],
            'headers' => [
                'managedFilesMissing' => ['List of missing files managed by TCEmain', $listExplain, 3],
                'softrefFilesMissing' => ['List of missing files registered as a soft reference', $listExplain, 2]
            ],
            'managedFilesMissing' => [],
            'softrefFilesMissing' => []
        ];
        // Select all files in the reference table
        $recs = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_refindex', 'ref_table=' . $GLOBALS['TYPO3_DB']->fullQuoteStr('_FILE', 'sys_refindex'), '', 'sorting DESC');
        // Traverse the files and put into a large table:
        if (is_array($recs)) {
            foreach ($recs as $rec) {
                // Compile info string for location of reference:
                $infoString = $this->infoStr($rec);
                // Handle missing file:
                if (!@is_file((PATH_site . $rec['ref_string']))) {
                    if ((string)$rec['softref_key'] == '') {
                        $resultArrayIndex = 'managedFilesMissing';
                    } else {
                        $resultArrayIndex = 'softrefFilesMissing';
                    }
                    $resultArray[$resultArrayIndex][$rec['ref_string']][$rec['hash']] = $infoString;
                    ksort($resultArray[$resultArrayIndex][$rec['ref_string']]);
                }
            }
        }
        ksort($resultArray['managedFilesMissing']);
        ksort($resultArray['softrefFilesMissing']);
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
        foreach ($resultArray['managedFilesMissing'] as $key => $value) {
            echo 'Processing file: ' . $key . LF;
            $c = 0;
            foreach ($value as $hash => $recReference) {
                echo '	Removing reference in record "' . $recReference . '": ';
                if ($bypass = $this->cli_noExecutionCheck($recReference)) {
                    echo $bypass;
                } else {
                    $sysRefObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ReferenceIndex::class);
                    $error = $sysRefObj->setReferenceValue($hash, null);
                    if ($error) {
                        echo '		TYPO3\\CMS\\Core\\Database\\ReferenceIndex::setReferenceValue(): ' . $error . LF;
                        echo 'missing_files: exit on error' . LF;
                        die;
                    } else {
                        echo 'DONE';
                    }
                }
                echo LF;
            }
        }
    }
}
