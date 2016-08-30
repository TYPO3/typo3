<?php
namespace TYPO3\CMS\Impexp\View;

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

use TYPO3\CMS\Backend\Tree\View\BrowseTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extension of the page tree class. Used to get the tree of pages to export.
 */
class ExportPageTreeView extends BrowseTreeView
{
    /**
     * Initialization
     */
    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    /**
     * Wrapping title from page tree.
     *
     * @param string $title Title to wrap
     * @param string $row Item record
     * @param int $bank Bank pointer (which mount point number)
     * @return string Wrapped title
     * @access private
     */
    public function wrapTitle($title, $row, $bank = 0)
    {
        return trim($title) === '' ? '<em>[' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.no_title', true) . ']</em>' : htmlspecialchars($title);
    }

    /**
     * Wrapping Plus/Minus icon
     *
     * @param string $icon Icon HTML
     * @param mixed $cmd (See parent class)
     * @param mixed $bMark (See parent class)
     * @param bool $isOpen
     * @return string Icon HTML
     */
    public function PM_ATagWrap($icon, $cmd, $bMark = '', $isOpen = false)
    {
        return $icon;
    }

    /**
     * Wrapping Icon
     *
     * @param string $icon Icon HTML
     * @param array $row Record row (page)
     * @return string Icon HTML
     */
    public function wrapIcon($icon, $row)
    {
        return $icon;
    }

    /**
     * Tree rendering
     *
     * @param int $pid PID value
     * @param string $clause Additional where clause
     * @return array Array of tree elements
     */
    public function ext_tree($pid, $clause = '')
    {
        // Initialize:
        $this->init(' AND ' . $this->BE_USER->getPagePermsClause(1) . $clause);
        // Get stored tree structure:
        $this->stored = unserialize($this->BE_USER->uc['browseTrees']['browsePages']);
        $treeArr = [];
        $idx = 0;
        // Set first:
        $this->bank = $idx;
        $isOpen = $this->stored[$idx][$pid] || $this->expandFirst;
        // save ids
        $curIds = $this->ids;
        $this->reset();
        $this->ids = $curIds;
        if ($pid > 0) {
            $rootRec = BackendUtility::getRecordWSOL('pages', $pid);
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $firstHtml = $iconFactory->getIconForRecord('pages', $rootRec, Icon::SIZE_SMALL)->render();
        } else {
            $rootRec = [
                'title' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
                'uid' => 0
            ];
            $firstHtml = $this->getRootIcon($rootRec);
        }
        $this->tree[] = ['HTML' => $firstHtml, 'row' => $rootRec, 'hasSub' => $isOpen];
        if ($isOpen) {
            // Set depth:
            if ($this->addSelfId) {
                $this->ids[] = $pid;
            }
            $this->getTree($pid, 999, '');
            $idH = [];
            $idH[$pid]['uid'] = $pid;
            if (!empty($this->buffer_idH)) {
                $idH[$pid]['subrow'] = $this->buffer_idH;
            }
            $this->buffer_idH = $idH;
        }
        // Add tree:
        return array_merge($treeArr, $this->tree);
    }
}
