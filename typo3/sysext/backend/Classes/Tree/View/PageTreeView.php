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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generate a page-tree, non-browsable.
 */
class PageTreeView extends AbstractTreeView
{
    /**
     * @var array
     */
    public $fieldArray = [
        'uid',
        'pid',
        'title',
        'doktype',
        'nav_title',
        'mount_pid',
        'php_tree_stop',
        't3ver_id',
        't3ver_state',
        'hidden',
        'starttime',
        'endtime',
        'fe_group',
        'module',
        'extendToSubpages',
        'nav_hide'
    ];

    /**
     * override to use this treeName
     * @var string
     */
    public $treeName = 'pages';

    /**
     * override to use this table
     * @var string
     */
    public $table = 'pages';

    /**
     * @var bool
     */
    public $ext_showNavTitle = false;

    /**
     * Init function
     * REMEMBER to feed a $clause which will filter out non-readable pages!
     *
     * @param string $clause Part of where query which will filter out non-readable pages.
     * @param string $orderByFields Record ORDER BY field
     * @return void
     */
    public function init($clause = '', $orderByFields = '')
    {
        parent::init(' AND deleted=0 ' . $clause, 'sorting');
    }

    /**
     * Returns TRUE/FALSE if the next level for $id should be expanded - and all levels should, so we always return 1.
     *
     * @param int $id ID (uid) to test for (see extending classes where this is checked against session data)
     * @return bool
     */
    public function expandNext($id)
    {
        return 1;
    }

    /**
     * Generate the plus/minus icon for the browsable tree.
     * In this case, there is no plus-minus icon displayed.
     *
     * @param array $row Record for the entry
     * @param int $a The current entry number
     * @param int $c The total number of entries. If equal to $a, a 'bottom' element is returned.
     * @param int $nextCount The number of sub-elements to the current element.
     * @param bool $isExpand The element was expanded to render subelements if this flag is set.
     * @return string Image tag with the plus/minus icon.
     * @access private
     * @see AbstractTreeView::PMicon()
     */
    public function PMicon($row, $a, $c, $nextCount, $isExpand)
    {
        return '<span class="treeline-icon treeline-icon-join' . ($a == $c ? 'bottom' : '') . '"></span>';
    }

    /**
     * Get stored tree structure AND updating it if needed according to incoming PM GET var.
     * - Here we just set it to nothing since we want to just render the tree, nothing more.
     *
     * @return void
     * @access private
     */
    public function initializePositionSaving()
    {
        $this->stored = [];
    }

    /**
     * Returns the title for the input record. If blank, a "no title" label (localized) will be returned.
     * Do NOT htmlspecialchar the string from this function - has already been done.
     *
     * @param array $row The input row array (where the key "title" is used for the title)
     * @param int $titleLen Title length (30)
     * @return string The title.
     */
    public function getTitleStr($row, $titleLen = 30)
    {
        $lang = $this->getLanguageService();
        if ($this->ext_showNavTitle && isset($row['nav_title']) && trim($row['nav_title']) !== '') {
            $title = '<span title="' . $lang->sL('LLL:EXT:lang/locallang_tca.xlf:title', true) . ' '
                     . htmlspecialchars(trim($row['title'])) . '">'
                     . htmlspecialchars(GeneralUtility::fixed_lgd_cs($row['nav_title'], $titleLen))
                     . '</span>';
        } else {
            $title = htmlspecialchars(GeneralUtility::fixed_lgd_cs($row['title'], $titleLen));
            if (isset($row['nav_title']) && trim($row['nav_title']) !== '') {
                $title = '<span title="'
                         . $lang->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.nav_title', true)
                         . ' ' . htmlspecialchars(trim($row['nav_title'])) . '">' . $title
                         . '</span>';
            }
            $title = trim($row['title']) === ''
                ? '<em>[' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.no_title', true) . ']</em>'
                : $title;
        }
        return $title;
    }
}
