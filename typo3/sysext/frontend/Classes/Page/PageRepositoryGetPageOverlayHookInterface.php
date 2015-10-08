<?php
namespace TYPO3\CMS\Frontend\Page;

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
 * Interface for classes which hook into \TYPO3\CMS\Frontend\Page\PageRepository
 */
interface PageRepositoryGetPageOverlayHookInterface
{
    /**
     * enables to preprocess the pageoverlay
     *
     * @param array $pageInput The page record
     * @param int $lUid The overlay language
     * @param \TYPO3\CMS\Frontend\Page\PageRepository $parent The calling parent object
     * @return void
     */
    public function getPageOverlay_preProcess(&$pageInput, &$lUid, PageRepository $parent);
}
