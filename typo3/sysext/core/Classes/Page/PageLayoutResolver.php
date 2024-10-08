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

namespace TYPO3\CMS\Core\Page;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderCollection;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext;
use TYPO3\CMS\Backend\View\BackendLayout\DefaultDataProvider;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\TypoScript\PageTsConfigFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Finds the proper layout for a page, using the database fields "backend_layout"
 * and "backend_layout_next_level".
 *
 * The most crucial part is that "backend_layout" is only applied for the CURRENT level,
 * whereas backend_layout_next_level.
 *
 * Used in TypoScript as "getData:pagelayout".
 *
 * Currently, there is a hard dependency on EXT:backend however, all DataProvider logic should be migrated
 * towards EXT:core.
 *
 * @internal This is not part of TYPO3 Core API.
 */
#[Autoconfigure(public: true)]
class PageLayoutResolver
{
    public function __construct(
        protected readonly DataProviderCollection $dataProviderCollection,
        protected readonly SiteFinder $siteFinder,
        protected readonly PageTsConfigFactory $pageTsConfigFactory
    ) {
        $this->dataProviderCollection->add('default', DefaultDataProvider::class);
        foreach ((array)($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider'] ?? []) as $identifier => $className) {
            $this->dataProviderCollection->add($identifier, $className);
        }
    }

    public function getLayoutForPage(array $pageRecord, array $rootLine): ?PageLayout
    {
        $pageId = (int)$pageRecord['uid'];

        try {
            $site = $this->siteFinder->getSiteByPageId($pageId, $rootLine);
        } catch (SiteNotFoundException) {
            $site = new NullSite();
        }

        $pageTsConfig = $this->pageTsConfigFactory->create($rootLine, $site);

        $dataProviderContext = GeneralUtility::makeInstance(DataProviderContext::class);
        $dataProviderContext
            ->setPageId($pageId)
            ->setData($pageRecord)
            ->setTableName('pages')
            ->setFieldName('backend_layout')
            ->setPageTsConfig($pageTsConfig->getPageTsConfigArray());

        $selectedPageLayout = $this->getLayoutIdentifierForPage($pageRecord, $rootLine);
        $layout = $this->dataProviderCollection->getBackendLayout($selectedPageLayout, $pageId);

        if ($layout === null) {
            return null;
        }

        $fullStructure = $layout->getStructure()['__config'];
        $contentAreas = [];
        // find all arrays recursively from , where one of the columns within the array is called "colPos"
        $findColPos = function (array $structure) use (&$findColPos, &$contentAreas) {
            if (isset($structure['colPos'])) {
                unset($structure['colspan'], $structure['rowspan']);
                $contentAreas[] = $structure;
            }
            foreach ($structure as $value) {
                if (is_array($value)) {
                    $findColPos($value);
                }
            }
        };
        $findColPos($fullStructure);

        return new PageLayout($layout->getIdentifier(), $layout->getTitle(), $contentAreas);
    }

    /**
     * Check if the current page has a value in the DB field "backend_layout"
     * if empty, check the root line for "backend_layout_next_level"
     * Same as TypoScript:
     *   field = backend_layout
     *   ifEmpty.data = levelfield:-2, backend_layout_next_level, slide
     *   ifEmpty.ifEmpty = default
     */
    public function getLayoutIdentifierForPage(array $page, array $rootLine): string
    {
        $selectedLayout = $page['backend_layout'] ?? '';

        // If it is set to "none" - don't use any
        if ($selectedLayout === '-1') {
            return 'none';
        }

        if ($selectedLayout === '' || $selectedLayout === '0') {
            // If it not set check the root-line for a layout on next level and use this
            // Remove first element, which is the current page
            // See also \TYPO3\CMS\Backend\View\BackendLayoutView::getSelectedCombinedIdentifier()
            array_shift($rootLine);
            foreach ($rootLine as $rootLinePage) {
                $selectedLayout = (string)($rootLinePage['backend_layout_next_level'] ?? '');
                // If layout for "next level" is set to "none" - don't use any and stop searching
                if ($selectedLayout === '-1') {
                    $selectedLayout = 'none';
                    break;
                }
                if ($selectedLayout !== '' && $selectedLayout !== '0') {
                    // Stop searching if a layout for "next level" is set
                    break;
                }
            }
        }
        if ($selectedLayout === '0' || $selectedLayout === '') {
            $selectedLayout = 'default';
        }
        return $selectedLayout;
    }

    public function getLayoutIdentifierForPageWithoutPrefix(array $page, array $rootLine): string
    {
        $selectedLayout = $this->getLayoutIdentifierForPage($page, $rootLine);
        if (str_contains($selectedLayout, '__')) {
            return explode('__', $selectedLayout, 2)[1] ?? '';
        }
        return $selectedLayout;
    }
}
