<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Fetch information of user specific inline record expanded / collapsed state
 * from user->uc and put it into $result['inlineExpandCollapseStateArray']
 */
class TcaInlineExpandCollapseState implements FormDataProviderInterface
{
    /**
     * Add inline expand / collapse state
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        if (empty($result['inlineExpandCollapseStateArray'])) {
            if (!empty($result['inlineTopMostParentUid']) && !empty($result['inlineTopMostParentTableName'])) {
                // Happens in inline ajax context, top parent uid and top parent table are set
                if (isset($this->getBackendUser()->uc['inlineView'])) {
                    $fullInlineState = unserialize($this->getBackendUser()->uc['inlineView'], ['allowed_classes' => false]);
                    if (!is_array($fullInlineState)) {
                        $fullInlineState = [];
                    }
                } else {
                    $fullInlineState = [];
                }
                $inlineStateForTable = [];
                if ($result['command'] !== 'new') {
                    $table = $result['inlineTopMostParentTableName'];
                    $uid = $result['inlineTopMostParentUid'];
                    if (!empty($fullInlineState[$table][$uid])) {
                        $inlineStateForTable = $fullInlineState[$table][$uid];
                    }
                }
                $result['inlineExpandCollapseStateArray'] = $inlineStateForTable;
            } else {
                // Default case for a single record
                $fullInlineState = !empty($this->getBackendUser()->uc['inlineView']) ? unserialize($this->getBackendUser()->uc['inlineView'], ['allowed_classes' => false]) : [];
                if (!is_array($fullInlineState)) {
                    $fullInlineState = [];
                }
                $inlineStateForTable = [];
                if ($result['command'] !== 'new') {
                    $table = $result['tableName'];
                    $uid = $result['databaseRow']['uid'];
                    if (!empty($fullInlineState[$table][$uid])) {
                        $inlineStateForTable = $fullInlineState[$table][$uid];
                    }
                }
                $result['inlineExpandCollapseStateArray'] = $inlineStateForTable;
            }
        }

        if (!$result['isInlineChildExpanded']) {
            // If the record is an inline child that is not expanded, it is not necessary to calculate all fields
            $isExistingRecord = $result['command'] === 'edit';
            $inlineConfig = $result['inlineParentConfig'];
            $collapseAll = isset($inlineConfig['appearance']['collapseAll']) && $inlineConfig['appearance']['collapseAll'];
            $expandAll = isset($inlineConfig['appearance']['collapseAll']) && !$inlineConfig['appearance']['collapseAll'];
            $expandCollapseStateArray = $result['inlineExpandCollapseStateArray'];
            $foreignTable = $result['inlineParentConfig']['foreign_table'];
            $isExpandedByUcState = isset($expandCollapseStateArray[$foreignTable])
                && is_array($expandCollapseStateArray[$foreignTable])
                && in_array($result['databaseRow']['uid'], $expandCollapseStateArray[$foreignTable]) !== false;

            if (!$isExistingRecord || ($isExpandedByUcState && !$collapseAll) || $expandAll || $result['isInlineAjaxOpeningContext']) {
                $result['isInlineChildExpanded'] = true;
            }
        }

        return $result;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
