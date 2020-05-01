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

namespace {
    die('Access denied');
}

namespace TYPO3\CMS\Frontend\Page {
    class PageRepository extends \TYPO3\CMS\Core\Domain\Repository\PageRepository
    {
    }
    interface PageRepositoryGetPageHookInterface extends \TYPO3\CMS\Core\Domain\Repository\PageRepositoryGetPageHookInterface
    {
    }
    interface PageRepositoryGetPageOverlayHookInterface extends \TYPO3\CMS\Core\Domain\Repository\PageRepositoryGetPageOverlayHookInterface
    {
    }
    interface PageRepositoryGetRecordOverlayHookInterface extends \TYPO3\CMS\Core\Domain\Repository\PageRepositoryGetRecordOverlayHookInterface
    {
    }
    interface PageRepositoryInitHookInterface extends \TYPO3\CMS\Core\Domain\Repository\PageRepositoryInitHookInterface
    {
    }
}
