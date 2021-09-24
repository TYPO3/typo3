<?php

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

namespace TYPO3\CMS\Frontend\ContentObject;

use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Category\Collection\CategoryCollection;

/**
 * Contains RECORDS class object.
 */
class RecordsContentObject extends AbstractContentObject
{
    /**
     * List of all items with table and uid information
     *
     * @var array
     */
    protected $itemArray = [];

    /**
     * List of all selected records with full data, arranged per table
     *
     * @var array
     */
    protected $data = [];

    /**
     * Rendering the cObject, RECORDS
     *
     * @param array $conf Array of TypoScript properties
     * @return string Output
     */
    public function render($conf = [])
    {
        // Reset items and data
        $this->itemArray = [];
        $this->data = [];

        $theValue = '';
        $originalRec = $GLOBALS['TSFE']->currentRecord;
        // If the currentRecord is set, we register, that this record has invoked this function.
        // It's should not be allowed to do this again then!!
        if ($originalRec) {
            if (!($GLOBALS['TSFE']->recordRegister[$originalRec] ?? false)) {
                $GLOBALS['TSFE']->recordRegister[$originalRec] = 0;
            }

            ++$GLOBALS['TSFE']->recordRegister[$originalRec];
        }

        $tables = (string)$this->cObj->stdWrapValue('tables', $conf ?? []);
        if ($tables !== '') {
            $tablesArray = array_unique(GeneralUtility::trimExplode(',', $tables, true));
            // Add tables which have a configuration (note that this may create duplicate entries)
            if (is_array($conf['conf.'] ?? false)) {
                foreach ($conf['conf.'] as $key => $value) {
                    if (substr($key, -1) !== '.' && !in_array($key, $tablesArray)) {
                        $tablesArray[] = $key;
                    }
                }
            }

            // Get the data, depending on collection method.
            // Property "source" is considered more precise and thus takes precedence over "categories"
            $source = (string)$this->cObj->stdWrapValue('source', $conf ?? []);
            $categories = (string)$this->cObj->stdWrapValue('categories', $conf ?? []);
            if ($source) {
                $this->collectRecordsFromSource($source, $tablesArray);
            } elseif ($categories) {
                $relationField = (string)$this->cObj->stdWrapValue('relation', $conf['categories.'] ?? []);
                $this->collectRecordsFromCategories($categories, $tablesArray, $relationField);
            }
            $itemArrayCount = count($this->itemArray);
            if ($itemArrayCount > 0) {
                /** @var ContentObjectRenderer $cObj */
                $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
                $cObj->setParent($this->cObj->data, $this->cObj->currentRecord);
                $this->cObj->currentRecordNumber = 0;
                // @deprecated since v11, will be removed in v12. Drop together with ContentObjectRenderer->currentRecordTotal
                $this->cObj->currentRecordTotal = $itemArrayCount;
                foreach ($this->itemArray as $val) {
                    $row = $this->data[$val['table']][$val['id']];
                    // Perform overlays if necessary (records coming from category collections are already overlaid)
                    if ($source) {
                        // Versioning preview
                        $this->getPageRepository()->versionOL($val['table'], $row);
                        // Language overlay
                        if (is_array($row)) {
                            $row = $this->getPageRepository()->getLanguageOverlay($val['table'], $row);
                        }
                    }
                    // Might be unset during the overlay process
                    if (is_array($row)) {
                        $dontCheckPid = $this->cObj->stdWrapValue('dontCheckPid', $conf ?? []);
                        if (!$dontCheckPid) {
                            $validPageId = $this->getPageRepository()->filterAccessiblePageIds([$row['pid']]);
                            $row = !empty($validPageId) ? $row : '';
                        }
                        if ($row && !empty($val['table']) && !($GLOBALS['TSFE']->recordRegister[$val['table'] . ':' . $val['id']] ?? false)) {
                            $renderObjName = ($conf['conf.'][$val['table']] ?? false) ? $conf['conf.'][$val['table']] : '<' . $val['table'];
                            $renderObjKey = ($conf['conf.'][$val['table']] ?? false) ? 'conf.' . $val['table'] : '';
                            $renderObjConf = ($conf['conf.'][$val['table'] . '.'] ?? false) ? $conf['conf.'][$val['table'] . '.'] : [];
                            $this->cObj->currentRecordNumber++;
                            $cObj->parentRecordNumber = $this->cObj->currentRecordNumber;
                            $GLOBALS['TSFE']->currentRecord = $val['table'] . ':' . $val['id'];
                            $this->cObj->lastChanged($row['tstamp']);
                            $cObj->start($row, $val['table'], $this->request);
                            $tmpValue = $cObj->cObjGetSingle($renderObjName, $renderObjConf, $renderObjKey);
                            $theValue .= $tmpValue;
                        }
                    }
                }
            }
        }
        $wrap = $this->cObj->stdWrapValue('wrap', $conf ?? []);
        if ($wrap) {
            $theValue = $this->cObj->wrap($theValue, $wrap);
        }
        if (isset($conf['stdWrap.'])) {
            $theValue = $this->cObj->stdWrap($theValue, $conf['stdWrap.']);
        }
        // Restore
        $GLOBALS['TSFE']->currentRecord = $originalRec;
        if ($originalRec) {
            --$GLOBALS['TSFE']->recordRegister[$originalRec];
        }
        return $theValue;
    }

    /**
     * Collects records according to the configured source
     *
     * @param string $source Source of records
     * @param array $tables List of tables
     */
    protected function collectRecordsFromSource($source, array $tables)
    {
        /** @var RelationHandler $loadDB*/
        $loadDB = GeneralUtility::makeInstance(RelationHandler::class);
        $loadDB->start($source, implode(',', $tables));
        foreach ($loadDB->tableArray as $table => $v) {
            if (isset($GLOBALS['TCA'][$table])) {
                $loadDB->additionalWhere[$table] = $this->getPageRepository()->enableFields($table);
            }
        }
        $this->data = $loadDB->getFromDB();
        reset($loadDB->itemArray);
        $this->itemArray = $loadDB->itemArray;
    }

    /**
     * Collects records for all selected tables and categories.
     *
     * @param string $selectedCategories Comma-separated list of categories
     * @param array $tables List of tables
     * @param string $relationField Name of the field containing the categories relation
     */
    protected function collectRecordsFromCategories($selectedCategories, array $tables, $relationField)
    {
        $selectedCategories = array_unique(GeneralUtility::intExplode(',', $selectedCategories, true));

        // Loop on all selected tables
        foreach ($tables as $table) {

            // Get the records for each selected category
            $tableRecords = [];
            $categoriesPerRecord = [];
            foreach ($selectedCategories as $aCategory) {
                try {
                    $collection = CategoryCollection::load(
                        $aCategory,
                        true,
                        $table,
                        $relationField
                    );
                    if ($collection->count() > 0) {
                        // Add items to the collection of records for the current table
                        foreach ($collection as $item) {
                            $tableRecords[$item['uid']] = $item;
                            // Keep track of all categories a given item belongs to
                            if (!isset($categoriesPerRecord[$item['uid']])) {
                                $categoriesPerRecord[$item['uid']] = [];
                            }
                            $categoriesPerRecord[$item['uid']][] = $aCategory;
                        }
                    }
                } catch (\Exception $e) {
                    $message = sprintf(
                        'Could not get records for category id %d. Error: %s (%d)',
                        $aCategory,
                        $e->getMessage(),
                        $e->getCode()
                    );
                    $this->getTimeTracker()->setTSlogMessage($message, LogLevel::WARNING);
                }
            }
            // Store the resulting records into the itemArray and data results array
            if (!empty($tableRecords)) {
                $this->data[$table] = [];
                foreach ($tableRecords as $record) {
                    $this->itemArray[] = [
                        'id' => $record['uid'],
                        'table' => $table,
                    ];
                    // Add to the record the categories it belongs to
                    $record['_categories'] = implode(',', $categoriesPerRecord[$record['uid']]);
                    $this->data[$table][$record['uid']] = $record;
                }
            }
        }
    }

    /**
     * @return TimeTracker
     */
    protected function getTimeTracker()
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }

    /**
     * @return PageRepository
     */
    protected function getPageRepository(): PageRepository
    {
        return $GLOBALS['TSFE']->sys_page ?: GeneralUtility::makeInstance(PageRepository::class);
    }
}
