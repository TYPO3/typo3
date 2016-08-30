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
 * cleanflexform
 */
class CleanFlexformCommand extends CleanerCommand
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        // Setting up help:
        $this->cli_options[] = ['--echotree level', 'When "level" is set to 1 or higher you will see the page of the page tree outputted as it is traversed. A value of 2 for "level" will show even more information.'];
        $this->cli_options[] = ['--pid id', 'Setting start page in page tree. Default is the page tree root, 0 (zero)'];
        $this->cli_options[] = ['--depth int', 'Setting traversal depth. 0 (zero) will only analyse start page (see --pid), 1 will traverse one level of subpages etc.'];
        $this->cli_help['name'] = 'cleanflexform -- Find flexform fields with unclean XML';
        $this->cli_help['description'] = trim('
Traversing page tree and finding records with FlexForm fields with XML that could be cleaned up. This will just remove obsolete data garbage.

Automatic Repair:
Cleaning XML for FlexForm fields.
');
        $this->cli_help['examples'] = '';
    }

    /**
     * Find orphan records
     * VERY CPU and memory intensive since it will look up the whole page tree!
     *
     * @return array
     */
    public function main()
    {
        // Initialize result array:
        $resultArray = [
            'message' => $this->cli_help['name'] . LF . LF . $this->cli_help['description'],
            'headers' => [
                'dirty' => ['', '', 2]
            ],
            'dirty' => []
        ];
        $startingPoint = $this->cli_isArg('--pid') ? \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->cli_argValue('--pid'), 0) : 0;
        $depth = $this->cli_isArg('--depth') ? \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->cli_argValue('--depth'), 0) : 1000;
        $this->cleanFlexForm_dirtyFields = &$resultArray['dirty'];
        // Do not repair flexform data in deleted records.
        $this->genTree_traverseDeleted = false;
        $this->genTree($startingPoint, $depth, (int)$this->cli_argValue('--echotree'), 'main_parseTreeCallBack');
        asort($resultArray);
        return $resultArray;
    }

    /**
     * Call back function for page tree traversal!
     *
     * @param string $tableName Table name
     * @param int $uid UID of record in processing
     * @param int $echoLevel Echo level  (see calling function
     * @param string $versionSwapmode Version swap mode on that level (see calling function
     * @param int $rootIsVersion Is root version (see calling function
     * @return void
     */
    public function main_parseTreeCallBack($tableName, $uid, $echoLevel, $versionSwapmode, $rootIsVersion)
    {
        foreach ($GLOBALS['TCA'][$tableName]['columns'] as $colName => $config) {
            if ($config['config']['type'] == 'flex') {
                if ($echoLevel > 2) {
                    echo LF . '			[cleanflexform:] Field "' . $colName . '" in ' . $tableName . ':' . $uid . ' was a flexform and...';
                }
                $recRow = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordRaw($tableName, 'uid=' . (int)$uid);
                $flexObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class);
                if ($recRow[$colName]) {
                    // Clean XML:
                    $newXML = $flexObj->cleanFlexFormXML($tableName, $colName, $recRow);
                    if (md5($recRow[$colName]) != md5($newXML)) {
                        if ($echoLevel > 2) {
                            echo ' was DIRTY, needs cleanup!';
                        }
                        $this->cleanFlexForm_dirtyFields[\TYPO3\CMS\Core\Utility\GeneralUtility::shortMd5($tableName . ':' . $uid . ':' . $colName)] = $tableName . ':' . $uid . ':' . $colName;
                    } else {
                        if ($echoLevel > 2) {
                            echo ' was CLEAN';
                        }
                    }
                } elseif ($echoLevel > 2) {
                    echo ' was EMPTY';
                }
            }
        }
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
        foreach ($resultArray['dirty'] as $fieldID) {
            list($table, $uid, $field) = explode(':', $fieldID);
            echo 'Cleaning XML in "' . $fieldID . '": ';
            if ($bypass = $this->cli_noExecutionCheck($fieldID)) {
                echo $bypass;
            } else {
                // Clean XML:
                $data = [];
                $recRow = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordRaw($table, 'uid=' . (int)$uid);
                $flexObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class);
                if ($recRow[$field]) {
                    $data[$table][$uid][$field] = $flexObj->cleanFlexFormXML($table, $field, $recRow);
                }
                // Execute Data array:
                $tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                $tce->stripslashes_values = false;
                $tce->dontProcessTransformations = true;
                $tce->bypassWorkspaceRestrictions = true;
                $tce->bypassFileHandling = true;
                // Check has been done previously that there is a backend user which is Admin and also in live workspace
                $tce->start($data, []);
                $tce->process_datamap();
                // Return errors if any:
                if (count($tce->errorLog)) {
                    echo '	ERROR from "TCEmain":' . LF . 'TCEmain:' . implode((LF . 'TCEmain:'), $tce->errorLog);
                } else {
                    echo 'DONE';
                }
            }
            echo LF;
        }
    }
}
