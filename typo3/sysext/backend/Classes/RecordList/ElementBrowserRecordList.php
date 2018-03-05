<?php
namespace TYPO3\CMS\Backend\RecordList;

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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;

/**
 * Displays the page tree for browsing database records.
 */
class ElementBrowserRecordList extends DatabaseRecordList
{
    /**
     * Table name of the field pointing to this element browser
     *
     * @var string
     */
    protected $relatingTable;

    /**
     * Field name of the field pointing to this element browser
     *
     * @var string
     */
    protected $relatingField;

    /**
     * Returns the title (based on $code) of a record (from table $table) with the proper link around (that is for "pages"-records a link to the level of that record...)
     *
     * @param string $table Table name
     * @param int $uid UID (not used here)
     * @param string $code Title string
     * @param array $row Records array (from table name)
     * @return string
     */
    public function linkWrapItems($table, $uid, $code, $row)
    {
        if (!$code) {
            $code = '<i>[' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title')) . ']</i>';
        } else {
            $code = BackendUtility::getRecordTitlePrep($code, $this->fixedL);
        }
        $title = BackendUtility::getRecordTitle($table, $row, false, true);
        $ficon = $this->iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render();

        $ATag = '<a href="#" data-close="0" title="' . htmlspecialchars($this->getLanguageService()->getLL('addToList')) . '">';
        $ATag_alt = '<a href="#" data-close="1" title="' . htmlspecialchars($this->getLanguageService()->getLL('addToList')) . '">';
        $ATag_e = '</a>';
        $out = '<span data-uid="' . htmlspecialchars($row['uid']) . '" data-table="' . htmlspecialchars($table) . '" data-title="' . htmlspecialchars($title) . '" data-icon="' . htmlspecialchars($ficon) . '">';
        $out .= $ATag . $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL)->render() . $ATag_e . $ATag_alt . $code . $ATag_e;
        $out .= '</span>';

        return $out;
    }

    /**
     * Check if all row listing conditions are fulfilled.
     *
     * @param string $table String Table name
     * @param array $row Array Record
     * @return bool True, if all conditions are fulfilled.
     */
    protected function isRowListingConditionFulfilled($table, $row)
    {
        $returnValue = true;
        if ($this->relatingField && $this->relatingTable) {
            $tcaFieldConfig = $GLOBALS['TCA'][$this->relatingTable]['columns'][$this->relatingField]['config'];
            if (is_array($tcaFieldConfig['filter'])) {
                foreach ($tcaFieldConfig['filter'] as $filter) {
                    if (!$filter['userFunc']) {
                        continue;
                    }
                    $parameters = $filter['parameters'] ?: [];
                    $parameters['values'] = [$table . '_' . $row['uid']];
                    $parameters['tcaFieldConfig'] = $tcaFieldConfig;
                    $valueArray = GeneralUtility::callUserFunction($filter['userFunc'], $parameters, $this);
                    if (empty($valueArray)) {
                        $returnValue = false;
                    }
                }
            }
        }
        return $returnValue;
    }

    /**
     * Set which pointing field (in the TCEForm) we are currently rendering the element browser for
     *
     * @param string $tableName Table name
     * @param string $fieldName Field name
     */
    public function setRelatingTableAndField($tableName, $fieldName)
    {
        // Check validity of the input data and load TCA
        if (isset($GLOBALS['TCA'][$tableName])) {
            $this->relatingTable = $tableName;
            if ($fieldName && isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName])) {
                $this->relatingField = $fieldName;
            }
        }
    }

    /**
     * Local version that sets allFields to TRUE to support userFieldSelect
     *
     * @see fieldSelectBox
     */
    public function generateList()
    {
        $this->allFields = true;
        parent::generateList();
    }
}
