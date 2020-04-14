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

namespace TYPO3\CMS\Backend\Tree\View;

/**
 * Extension for the tree class that generates the tree of pages in the page-wizard mode
 *
 * @see \TYPO3\CMS\Backend\Controller\NewRecordController
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class NewRecordPageTreeView extends PageTreeView
{
    /**
     * @var int
     */
    protected $currentPageId;

    public function __construct(int $currentPageId)
    {
        $this->currentPageId = $currentPageId;
        parent::__construct();
    }

    /**
     * Determines whether to expand a branch or not.
     * Here the branch is expanded if the current id matches the global id for the listing/new
     *
     * @param int $id The ID (page id) of the element
     * @return bool Returns TRUE if the IDs matches
     */
    public function expandNext($id)
    {
        return (int)$id === $this->currentPageId;
    }
}
