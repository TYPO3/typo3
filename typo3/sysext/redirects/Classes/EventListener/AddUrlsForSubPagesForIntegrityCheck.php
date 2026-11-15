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

namespace TYPO3\CMS\Redirects\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageDescendantsRepository;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\LanguageMarker;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Event\AfterPageUrlsForSiteForRedirectIntegrityHaveBeenCollectedEvent;

/**
 * Event listener to gather the slugs of all pages of a site
 *
 * @internal This class is not part of TYPO3 Core API.
 */
final readonly class AddUrlsForSubPagesForIntegrityCheck
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private TcaSchemaFactory $tcaSchemaFactory,
        private PageDescendantsRepository $pageDescendantsRepository,
    ) {}

    #[AsEventListener('redirects-add-slugs-of-subpages')]
    public function __invoke(AfterPageUrlsForSiteForRedirectIntegrityHaveBeenCollectedEvent $event): void
    {
        $pageUrls = $event->getPageUrls();

        $pageUrls = array_merge(
            $pageUrls,
            $this->getSlugsOfSubPages(
                $event->getSite()->getRootPageId(),
                $event->getSite()
            )
        );

        $event->setPageUrls($pageUrls);
    }

    /**
     * Resolves the subtree of a page and returns the slugs of its pages in all languages.
     * Hidden pages and pages outside their start and end time are left out together
     * with their subpages.
     */
    private function getSlugsOfSubPages(int $pageId, Site $site): array
    {
        $languageFieldName = $this->tcaSchemaFactory->get('pages')
            ->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();
        $pages = [];
        $defaultLanguagePages = $this->pageDescendantsRepository->findDescendants(
            [$pageId],
            PageDescendantsRepository::MAX_TRAVERSAL_LEVELS,
            0,
            null,
            [
                GeneralUtility::makeInstance(HiddenRestriction::class),
                GeneralUtility::makeInstance(StartTimeRestriction::class),
                GeneralUtility::makeInstance(EndTimeRestriction::class),
            ],
            ['slug'],
        );
        foreach ($defaultLanguagePages as $page) {
            $pages[] = ['slug' => $page['slug'], 'languageId' => LanguageMarker::DEFAULT_LANGUAGE];
        }
        // Translated pages have the same parent page as their page in the default language
        foreach (array_chunk([$pageId, ...array_column($defaultLanguagePages, 'uid')], 1000) as $parentPageIds) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
            $result = $queryBuilder
                ->select('slug', $languageFieldName)
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->in('pid', $queryBuilder->createNamedParameter($parentPageIds, Connection::PARAM_INT_ARRAY)),
                    $queryBuilder->expr()->gt($languageFieldName, $queryBuilder->createNamedParameter(LanguageMarker::DEFAULT_LANGUAGE, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                )
                ->executeQuery();
            while ($row = $result->fetchAssociative()) {
                $pages[] = ['slug' => $row['slug'], 'languageId' => (int)$row[$languageFieldName]];
            }
        }

        $pageUrls = [];
        foreach ($pages as $page) {
            // @todo Considering only page slug is not complete, as it does not matches redirects with file extension,
            //       for ex. if PageTypeSuffix routeEnhancer are used and redirects are created based on that.
            $slug = ltrim($page['slug'] ?? '', '/');
            // empty slugs should not occur here, but to be sure we skip them here, as they were already handled.
            if ($slug === '') {
                continue;
            }
            try {
                $siteLanguage = $site->getLanguageById($page['languageId']);
            } catch (\InvalidArgumentException) {
                // skip invalid languages which might occur due to previous changes in site configuration
                continue;
            }
            $pageUrls[] = rtrim((string)$siteLanguage->getBase(), '/') . '/' . $slug;
        }
        return $pageUrls;
    }
}
