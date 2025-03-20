<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Impexp\View;

use TYPO3\CMS\Backend\Tree\View\AbstractTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extension of the page tree class. Used to get the tree of pages to export.
 *
 * @internal This class is not considered part of the public TYPO3 API.
 */
class ExportPageTreeView extends AbstractTreeView
{
    /**
     * If set, then ALL items will be expanded, regardless of stored settings.
     */
    protected bool $expandAll = false;

    /**
     * Points to the current mountpoint key
     * @var int
     */
    public $bank = 0;

    /**
     * Init function
     * REMEMBER to feed a $clause which will filter out non-readable pages!
     *
     * @param string $clause Part of where query which will filter out non-readable pages.
     * @param string $orderByFields Record ORDER BY field
     */
    public function init($clause = '', $orderByFields = '')
    {
        parent::init(' AND deleted=0 AND sys_language_uid=0 ' . $clause, $orderByFields);
    }

    /**
     * Creates title attribute content for pages.
     * Uses API function in \TYPO3\CMS\Backend\Utility\BackendUtility which will retrieve lots of useful information for pages.
     *
     * @param array $row The table row.
     * @return string
     */
    protected function getTitleAttrib($row)
    {
        return BackendUtility::titleAttribForPages($row, '1=1 ', false);
    }

    /**
     * Wrapping Plus/Minus icon, unused in Export Page Tree
     */
    protected function PMicon($row, $a, $c, $nextCount, $isOpen)
    {
        return '';
    }

    /**
     * Construction of the tree structure with predefined depth.
     *
     * @param int $pid Page ID
     * @param int $levels Page tree levels
     */
    public function buildTreeByLevels(int $pid, int $levels): void
    {
        $this->expandAll = true;
        $checkSub = $levels > 0;

        $this->buildTree($pid, $levels, $checkSub);
    }

    /**
     * Creation of a tree structure with predefined depth to prepare the export.
     *
     * @param int $pid Page ID
     * @param int $levels Page tree levels
     * @param bool $checkSub Should root page be checked for sub pages?
     */
    protected function buildTree(int $pid, int $levels, bool $checkSub): void
    {
        $this->reset();
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        // Root page
        if ($pid > 0) {
            $rootRecord = BackendUtility::getRecordWSOL('pages', $pid);
            $rootHtml = $iconFactory->getIconForRecord('pages', $rootRecord, IconSize::SMALL)->render();
        } else {
            $rootRecord = [
                'title' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
                'uid' => 0,
            ];
            $rootHtml = $iconFactory->getIcon('apps-pagetree-root', IconSize::SMALL)->render();
        }

        $this->tree[] = [
            'HTML' => $rootHtml,
            'row' => $rootRecord,
            'hasSub' => $checkSub,
            'bank' => $this->bank,
        ];

        // Subtree
        if ($checkSub) {
            $this->getTree($pid, $levels);
        }

        $idH = [];
        $idH[$pid]['uid'] = $pid;
        if (!empty($this->buffer_idH)) {
            $idH[$pid]['subrow'] = $this->buffer_idH;
        }
        $this->buffer_idH = $idH;

        // Check if root page has subtree
        if (empty($this->buffer_idH)) {
            $this->tree[0]['hasSub'] = false;
        }
    }

    /**
     * Compiles the HTML code for displaying the structure found inside the ->tree array
     *
     * @param array|string $treeArr "tree-array" - if blank string, the internal ->tree array is used.
     * @return string The HTML code for the tree
     */
    public function printTree($treeArr = '')
    {
        $titleLen = (int)$this->getBackendUser()->uc['titleLen'];
        if (!is_array($treeArr)) {
            $treeArr = $this->tree;
        }
        $out = '';
        $closeDepth = [];
        foreach ($treeArr as $treeItem) {
            if ($treeItem['isFirst'] ?? false) {
                $out .= '<ul class="treelist">';
            }

            $idAttr = htmlspecialchars('pages' . $treeItem['row']['uid']);
            $out .= '
                <li id="' . $idAttr . '">
                    <span class="treelist-group">
                        <span class="treelist-icon">' . $treeItem['HTML'] . '</span>
                        <span class="treelist-title">' . $this->getTitleStr($treeItem['row'], $titleLen) . '</span>
                    </span>';

            if (!($treeItem['hasSub'] ?? false)) {
                $out .= '</li>';
            }

            // We have to remember if this is the last one
            // on level X so the last child on level X+1 closes the <ul>-tag
            if ($treeItem['isLast'] ?? false) {
                $closeDepth[$treeItem['invertedDepth']] = 1;
            }
            // If this is the last one and does not have subitems, we need to close
            // the tree as long as the upper levels have last items too
            if (($treeItem['isLast'] ?? false) && !($treeItem['hasSub'] ?? false)) {
                for ($i = $treeItem['invertedDepth']; ($closeDepth[$i] ?? 0) == 1; $i++) {
                    $closeDepth[$i] = 0;
                    $out .= '</ul></li>';
                }
            }
        }
        return '<ul class="treelist treelist-root treelist-root-clean">' . $out . '</ul>';
    }

    /**
     * Returns TRUE/FALSE if the next level for $id should be expanded - based on the ->expandAll flag.
     * Extending parent function
     *
     * @param int $id Record id/key
     * @return bool
     * @internal
     * @see \TYPO3\CMS\Backend\Tree\View\PageTreeView::expandNext()
     */
    public function expandNext($id)
    {
        return $this->expandAll;
    }

    /**
     * Returns the title for the input record. If blank, a "no title" label (localized) will be returned.
     * Do NOT htmlspecialchar the string from this function - has already been done.
     *
     * @param array $row The input row array (where the key "title" is used for the title)
     * @param int $titleLen Title length (30)
     * @return string The title.
     */
    protected function getTitleStr($row, $titleLen)
    {
        $title = htmlspecialchars(GeneralUtility::fixed_lgd_cs($row['title'], (int)$titleLen));
        return trim($title) === '' ? '<em>[' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title')) . ']</em>' : $title;
    }
}
