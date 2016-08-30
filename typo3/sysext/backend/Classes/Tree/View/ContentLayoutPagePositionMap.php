<?php
namespace TYPO3\CMS\Backend\Tree\View;

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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Position map class for content elements within the page module
 */
class ContentLayoutPagePositionMap extends PagePositionMap
{
    /**
     * @var bool
     */
    public $dontPrintPageInsertIcons = 1;

    /**
     * @var string
     */
    public $l_insertNewRecordHere = 'newContentElement';

    /**
     * Wrapping the title of the record.
     *
     * @param string $str The title value.
     * @param array $row The record row.
     * @return string Wrapped title string.
     */
    public function wrapRecordTitle($str, $row)
    {
        $aOnClick = 'jumpToUrl(' . GeneralUtility::quoteJSvalue($GLOBALS['SOBE']->local_linkThisScript(['edit_record' => ('tt_content:' . $row['uid'])])) . ');return false;';
        return '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $str . '</a>';
    }

    /**
     * Wrapping the column header
     *
     * @param string $str Header value
     * @param string $vv Column info.
     * @return string
     * @see printRecordMap()
     */
    public function wrapColumnHeader($str, $vv)
    {
        $aOnClick = 'jumpToUrl(' . GeneralUtility::quoteJSvalue($GLOBALS['SOBE']->local_linkThisScript(['edit_record' => ('_EDIT_COL:' . $vv)])) . ');return false;';
        return '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $str . '</a>';
    }

    /**
     * Create on-click event value.
     *
     * @param array $row The record.
     * @param string $vv Column position value.
     * @param int $moveUid Move uid
     * @param int $pid PID value.
     * @param int $sys_lang System language
     * @return string
     */
    public function onClickInsertRecord($row, $vv, $moveUid, $pid, $sys_lang = 0)
    {
        if (is_array($row)) {
            $location = $GLOBALS['SOBE']->local_linkThisScript(['edit_record' => 'tt_content:new/-' . $row['uid'] . '/' . $row['colPos']]);
        } else {
            $location = $GLOBALS['SOBE']->local_linkThisScript(['edit_record' => 'tt_content:new/' . $pid . '/' . $vv]);
        }
        return 'jumpToUrl(' . GeneralUtility::quoteJSvalue($location) . ');return false;';
    }

    /**
     * Wrapping the record header  (from getRecordHeader())
     *
     * @param string $str HTML content
     * @param array $row Record array.
     * @return string HTML content
     */
    public function wrapRecordHeader($str, $row)
    {
        if ($row['uid'] == $this->moveUid) {
            /** @var IconFactory $iconFactory */
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            return $iconFactory->getIcon('status-status-current', Icon::SIZE_SMALL)->render() . $str;
        } else {
            return $str;
        }
    }
}
