<?php

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
