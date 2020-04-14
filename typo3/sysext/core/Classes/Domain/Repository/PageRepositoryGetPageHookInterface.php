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

namespace TYPO3\CMS\Core\Domain\Repository;

/**
 * Interface for classes which hook into pageSelect and do additional getPage processing
 */
interface PageRepositoryGetPageHookInterface
{
    /**
     * Modifies the DB params
     *
     * @param int $uid The page ID
     * @param bool $disableGroupAccessCheck If set, the check for group access is disabled. VERY rarely used
     * @param PageRepository $parentObject Parent object
     */
    public function getPage_preProcess(&$uid, &$disableGroupAccessCheck, PageRepository $parentObject);
}
