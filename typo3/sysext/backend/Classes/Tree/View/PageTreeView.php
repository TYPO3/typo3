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

namespace TYPO3\CMS\Backend\Tree\View;

/**
 * Generate a page-tree, non-browsable.
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class PageTreeView extends AbstractTreeView
{
    protected ?int $currentPageId = null;

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
     * Returns TRUE/FALSE if the next level for $id should be expanded - and all levels should, so we always return true.
     * Here the branch is expanded if the current id matches the global id for the listing/new
     *
     * @param int $id ID (uid) to test for
     * @return bool
     */
    public function expandNext($id)
    {
        if ($this->currentPageId !== null) {
            return (int)$id === $this->currentPageId;
        }
        return true;
    }

    /**
     * Generate the plus/minus icon for the browsable tree.
     * In this case, there is no plus-minus icon displayed.
     *
     * @param array $row Record for the entry
     * @param int $a The current entry number
     * @param int $c The total number of entries. If equal to $a, a 'bottom' element is returned.
     * @param int $nextCount The number of sub-elements to the current element.
     * @param bool $isOpen The element was expanded to render subelements if this flag is set.
     * @return string Image tag with the plus/minus icon.
     * @see AbstractTreeView::PMicon()
     */
    protected function PMicon($row, $a, $c, $nextCount, $isOpen)
    {
        return '<span class="treeline-icon treeline-icon-join' . ($a == $c ? 'bottom' : '') . '"></span>';
    }

    public function setCurrentPageId(int $currentPageId): void
    {
        $this->currentPageId = $currentPageId;
    }
}
