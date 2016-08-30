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

/**
 * Position map class for moving content elements,
 * previously resided in typo3/move_el.php
 */
class ContentMovingPagePositionMap extends PagePositionMap
{
    /**
     * @var int
     */
    public $dontPrintPageInsertIcons = 1;

    /**
     * Page tree implementation class name
     *
     * @var string
     */
    protected $pageTreeClassName = PageTreeView::class;

    /**
     * Wrapping page title.
     *
     * @param string $str Page title.
     * @param array $rec Page record (?)
     * @return string Wrapped title.
     */
    public function linkPageTitle($str, $rec)
    {
        $url = \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(['uid' => (int)$rec['uid'], 'moveUid' => $GLOBALS['SOBE']->moveUid]);
        return '<a href="' . htmlspecialchars($url) . '">' . $str . '</a>';
    }

    /**
     * Wrapping the title of the record.
     *
     * @param string $str The title value.
     * @param array $row The record row.
     * @return string Wrapped title string.
     */
    public function wrapRecordTitle($str, $row)
    {
        if ($GLOBALS['SOBE']->moveUid == $row['uid']) {
            $str = '<strong>' . $str . '</strong>';
        }
        return parent::wrapRecordTitle($str, $row);
    }
}
