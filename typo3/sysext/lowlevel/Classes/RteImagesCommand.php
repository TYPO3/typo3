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

use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;

/**
 * Looking for RTE images integrity
 */
class RteImagesCommand extends CleanerCommand
{
    /**
     * @var bool
     */
    public $checkRefIndex = true;

    /**
     * @var ExtendedFileUtility
     */
    protected $fileProcObj = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
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

Automatic Repair of Errors:
- Will search for double-usages of RTEmagic images and make copies as required.
- Lost files can be deleted automatically by setting the value "lostFiles" as an optional parameter to --AUTOFIX, but otherwise delete them manually if you do not recognize them as used somewhere the system does not know about.

Manual repair suggestions:
- Missing files: Re-insert missing files or edit record where the reference is found.
');
        $this->cli_help['examples'] = '/.../cli_dispatch.phpsh lowlevel_cleaner rte_images -s -r
Reports problems with RTE images';
    }

    /**
     * Analyse situation with RTE magic images. (still to define what the most useful output is).
     * Fix methods: API in \TYPO3\CMS\Core\Database\ReferenceIndex that allows to
     * change the value of a reference (we could copy the files) or remove reference
     *
     * @return array
     */
    public function main()
    {
        // Initialize result array:
        $resultArray = [
            'message' => $this->cli_help['name'] . LF . LF . $this->cli_help['description'],
            'headers' => [
                'completeFileList' => ['Complete list of used RTEmagic files', 'Both parent and copy are listed here including usage count (which should in theory all be "1"). This list does not exclude files that might be missing.', 1],
                'RTEmagicFilePairs' => ['Statistical info about RTEmagic files', '(copy used as index)', 0],
                'doubleFiles' => ['Duplicate RTEmagic image files', 'These files are RTEmagic images found used in multiple records! RTEmagic images should be used by only one record at a time. A large amount of such images probably stems from previous versions of TYPO3 (before 4.2) which did not support making copies automatically of RTEmagic images in case of new copies / versions.', 3],
                'missingFiles' => ['Missing RTEmagic image files', 'These files are not found in the file system! Should be corrected!', 3],
                'lostFiles' => ['Lost RTEmagic files from uploads/', 'These files you might be able to delete but only if _all_ RTEmagic images are found by the soft reference parser. If you are using the RTE in third-party extensions it is likely that the soft reference parser is not applied correctly to their RTE and thus these "lost" files actually represent valid RTEmagic images, just not registered. Lost files can be auto-fixed but only if you specifically set "lostFiles" as parameter to the --AUTOFIX option.', 2]
            ],
            'RTEmagicFilePairs' => [],
            'doubleFiles' => [],
            'completeFileList' => [],
            'missingFiles' => [],
            'lostFiles' => []
        ];
        // Select all RTEmagic files in the reference table (only from soft references of course)
        $recs = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_refindex', 'ref_table=' . $GLOBALS['TYPO3_DB']->fullQuoteStr('_FILE', 'sys_refindex') . ' AND ref_string LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('%/RTEmagic%', 'sys_refindex') . ' AND softref_key=' . $GLOBALS['TYPO3_DB']->fullQuoteStr('images', 'sys_refindex'), '', 'sorting DESC');
        // Traverse the files and put into a large table:
        if (is_array($recs)) {
            foreach ($recs as $rec) {
                $filename = basename($rec['ref_string']);
                if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($filename, 'RTEmagicC_')) {
                    $original = 'RTEmagicP_' . preg_replace('/\\.[[:alnum:]]+$/', '', substr($filename, 10));
                    $infoString = $this->infoStr($rec);
                    // Build index:
                    $resultArray['RTEmagicFilePairs'][$rec['ref_string']]['exists'] = @is_file((PATH_site . $rec['ref_string']));
                    $resultArray['RTEmagicFilePairs'][$rec['ref_string']]['original'] = substr($rec['ref_string'], 0, -strlen($filename)) . $original;
                    $resultArray['RTEmagicFilePairs'][$rec['ref_string']]['original_exists'] = @is_file((PATH_site . $resultArray['RTEmagicFilePairs'][$rec['ref_string']]['original']));
                    $resultArray['RTEmagicFilePairs'][$rec['ref_string']]['count']++;
                    $resultArray['RTEmagicFilePairs'][$rec['ref_string']]['usedIn'][$rec['hash']] = $infoString;
                    $resultArray['completeFileList'][$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['original']]++;
                    $resultArray['completeFileList'][$rec['ref_string']]++;
                    // Missing files:
                    if (!$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['exists']) {
                        $resultArray['missingFiles'][$rec['ref_string']] = $resultArray['RTEmagicFilePairs'][$rec['ref_string']]['usedIn'];
                    }
                    if (!$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['original_exists']) {
                        $resultArray['missingFiles'][$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['original']] = $resultArray['RTEmagicFilePairs'][$rec['ref_string']]['usedIn'];
                    }
                }
            }
            // Searching for duplicates:
            foreach ($resultArray['RTEmagicFilePairs'] as $fileName => $fileInfo) {
                if ($fileInfo['count'] > 1 && $fileInfo['exists'] && $fileInfo['original_exists']) {
                    $resultArray['doubleFiles'][$fileName] = $fileInfo['usedIn'];
                }
            }
        }
        // Now, ask for RTEmagic files inside uploads/ folder:
        $cleanerModules = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lowlevel']['cleanerModules'];
        $cleanerMode = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($cleanerModules['lost_files'][0]);
        $resLostFiles = $cleanerMode->main([], false, true);
        if (is_array($resLostFiles['RTEmagicFiles'])) {
            foreach ($resLostFiles['RTEmagicFiles'] as $fileName) {
                if (!isset($resultArray['completeFileList'][$fileName])) {
                    $resultArray['lostFiles'][$fileName] = $fileName;
                }
            }
        }
        ksort($resultArray['RTEmagicFilePairs']);
        ksort($resultArray['completeFileList']);
        ksort($resultArray['missingFiles']);
        ksort($resultArray['doubleFiles']);
        ksort($resultArray['lostFiles']);
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
        $limitTo = $this->cli_args['--AUTOFIX'][0];
        if (is_array($resultArray['doubleFiles'])) {
            if (!$limitTo || $limitTo === 'doubleFiles') {
                echo 'FIXING double-usages of RTE files in uploads/: ' . LF;
                foreach ($resultArray['RTEmagicFilePairs'] as $fileName => $fileInfo) {
                    // Only fix something if there is a usage count of more than 1 plus if both original and copy exists:
                    if ($fileInfo['count'] > 1 && $fileInfo['exists'] && $fileInfo['original_exists']) {
                        // Traverse all records using the file:
                        $c = 0;
                        foreach ($fileInfo['usedIn'] as $hash => $recordID) {
                            if ($c == 0) {
                                echo '	Keeping file ' . $fileName . ' for record ' . $recordID . LF;
                            } else {
                                // CODE below is adapted from \TYPO3\CMS\Impexp\ImportExport where there is support for duplication of RTE images:
                                echo '	Copying file ' . basename($fileName) . ' for record ' . $recordID . ' ';
                                // Initialize; Get directory prefix for file and set the original name:
                                $dirPrefix = dirname($fileName) . '/';
                                $rteOrigName = basename($fileInfo['original']);
                                // If filename looks like an RTE file, and the directory is in "uploads/", then process as a RTE file!
                                if ($rteOrigName && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($dirPrefix, 'uploads/') && @is_dir((PATH_site . $dirPrefix))) {
                                    // RTE:
                                    // From the "original" RTE filename, produce a new "original" destination filename which is unused.
                                    $fileProcObj = $this->getFileProcObj();
                                    $origDestName = $fileProcObj->getUniqueName($rteOrigName, PATH_site . $dirPrefix);
                                    // Create copy file name:
                                    $pI = pathinfo($fileName);
                                    $copyDestName = dirname($origDestName) . '/RTEmagicC_' . substr(basename($origDestName), 10) . '.' . $pI['extension'];
                                    if (!@is_file($copyDestName) && !@is_file($origDestName) && $origDestName === \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($origDestName) && $copyDestName === \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($copyDestName)) {
                                        echo ' to ' . basename($copyDestName);
                                        if ($bypass = $this->cli_noExecutionCheck($fileName)) {
                                            echo $bypass;
                                        } else {
                                            // Making copies:
                                            \TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move(PATH_site . $fileInfo['original'], $origDestName);
                                            \TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move(PATH_site . $fileName, $copyDestName);
                                            clearstatcache();
                                            if (@is_file($copyDestName)) {
                                                $sysRefObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ReferenceIndex::class);
                                                $error = $sysRefObj->setReferenceValue($hash, \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($copyDestName));
                                                if ($error) {
                                                    echo '	- ERROR:	TYPO3\\CMS\\Core\\Database\\ReferenceIndex::setReferenceValue(): ' . $error . LF;
                                                    die;
                                                } else {
                                                    echo ' - DONE';
                                                }
                                            } else {
                                                echo '	- ERROR: File "' . $copyDestName . '" was not created!';
                                            }
                                        }
                                    } else {
                                        echo '	- ERROR: Could not construct new unique names for file!';
                                    }
                                } else {
                                    echo '	- ERROR: Maybe directory of file was not within "uploads/"?';
                                }
                                echo LF;
                            }
                            $c++;
                        }
                    }
                }
            } else {
                echo 'Bypassing fixing of double-usages since --AUTOFIX was not "doubleFiles"' . LF;
            }
        }
        if (is_array($resultArray['lostFiles'])) {
            if ($limitTo === 'lostFiles') {
                echo 'Removing lost RTEmagic files from folders inside uploads/: ' . LF;
                foreach ($resultArray['lostFiles'] as $key => $value) {
                    $absFileName = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($value);
                    echo 'Deleting file: "' . $absFileName . '": ';
                    if ($bypass = $this->cli_noExecutionCheck($absFileName)) {
                        echo $bypass;
                    } else {
                        if ($absFileName && @is_file($absFileName)) {
                            unlink($absFileName);
                            echo 'DONE';
                        } else {
                            echo '	ERROR: File "' . $absFileName . '" was not found!';
                        }
                    }
                    echo LF;
                }
            }
        } else {
            echo 'Bypassing fixing of double-usages since --AUTOFIX was not "lostFiles"' . LF;
        }
    }

    /**
     * Returns file processing object, initialized only once.
     *
     * @return ExtendedFileUtility File processor object
     */
    public function getFileProcObj()
    {
        if (!is_object($this->fileProcObj)) {
            $this->fileProcObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtendedFileUtility::class);
            $this->fileProcObj->init([], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
            $this->fileProcObj->setActionPermissions();
        }
        return $this->fileProcObj;
    }
}
