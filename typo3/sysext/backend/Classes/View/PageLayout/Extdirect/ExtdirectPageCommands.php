<?php
namespace TYPO3\CMS\Backend\View\PageLayout\ExtDirect;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Commands for the Page module
 * @deprecated since TYPO3 CMS 7, this file will be removed in TYPO3 CMS 8, as Drag&Drop does this
 * logic now directly and calls DataHandler via AJAX directly.
 */
class ExtdirectPageCommands
{
    /**
     * Move content element to a position and/or column.
     *
     * Function is called from the Page module javascript.
     *
     * @param int $sourceElement  Id attribute of content element which must be moved
     * @param string $destinationColumn Column to move the content element to
     * @param int $destinationElement Id attribute of the element it was dropped on
     * @return array
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function moveContentElement($sourceElement, $destinationColumn, $destinationElement)
    {
        GeneralUtility::logDeprecatedFunction();
        $moveElementUid = 0;
        $afterElementUid = -1;
        $targetColumn = 0;
        $targetPage = 0;
        list($_, $table, $uid) = GeneralUtility::trimExplode('-', $sourceElement);
        if ($table === 'tt_content' && MathUtility::canBeInterpretedAsInteger($uid)) {
            $moveElementUid = (int)$uid;
        }
        list($_, $table, $uid) = GeneralUtility::trimExplode('-', $destinationElement);
        if ($table === 'tt_content' && MathUtility::canBeInterpretedAsInteger($uid)) {
            $afterElementUid = (int)$uid;
        } else {
            // it's dropped in an empty column
            $afterElementUid = -1;
        }
        list($prefix, $column, $prefix2, $page, $_) = GeneralUtility::trimExplode('-', $destinationColumn);
        if ($prefix === 'colpos' && MathUtility::canBeInterpretedAsInteger($column) &&
                $prefix2 === 'page' && MathUtility::canBeInterpretedAsInteger($page)
        ) {
            $targetColumn = (int)$column;
            $targetPage = (int)$page;
        }
        // move to empty column
        if ($afterElementUid === -1) {
            $action['cmd']['tt_content'][$moveElementUid]['move'] = $targetPage;
        } else {
            $action['cmd']['tt_content'][$moveElementUid]['move'] = -$afterElementUid;
        }

        $action['data']['tt_content'][$moveElementUid]['colPos'] = $targetColumn;

        GeneralUtility::devLog(
            'Dragdrop',
            'core',
            -1,
            [
                'action' => $action,
                'sourceElement' => $sourceElement,
                'destinationColumn' => $destinationColumn,
                'destinationElement' => $destinationElement,
            ]
        );
        /** @var $tce \TYPO3\CMS\Core\DataHandling\DataHandler */
        $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
        $tce->stripslashes_values = 0;
        $tce->start($action['data'], $action['cmd']);
        $tce->process_datamap();
        $tce->process_cmdmap();

        return ['success' => true];
    }
}
