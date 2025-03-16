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
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
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
        private TcaSchemaFactory $tcaSchemaFactory
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
     * Resolves the subtree of a page and returns its slugs for language $languageId.
     */
    private function getSlugsOfSubPages(int $pageId, Site $site): array
    {
        $pageUrls = [[]];

        $schema = $this->tcaSchemaFactory->get('pages');
        $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
        $queryBuilder = $this->connectionPool
            ->getQueryBuilderForTable('pages');

        $queryBuilder
            ->select('uid', 'slug', $languageCapability->getLanguageField()->getName())
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)),
            );
        $result = $queryBuilder->executeQuery();

        while ($row = $result->fetchAssociative()) {
            // @todo Considering only page slug is not complete, as it does not matches redirects with file extension,
            //       for ex. if PageTypeSuffix routeEnhancer are used and redirects are created based on that.
            $slug = ltrim($row['slug'] ?? '', '/');
            $languageId = (int)$row[$languageCapability->getLanguageField()->getName()];
            try {
                $siteLanguage = $site->getLanguageById($languageId);
            } catch (\InvalidArgumentException) {
                // skip invalid languages which might occur due to previous changes in site configuration
                continue;
            }

            // empty slugs should to occur here, but to be sure we skip them here, as they were already handled.
            if ($slug === '') {
                continue;
            }

            $pageUrls[] = [rtrim((string)$siteLanguage->getBase(), '/') . '/' . $slug];

            // only traverse for pages of default language (as even translated pages contain pid of parent in default language)
            if ($languageId === 0) {
                $pageUrls[] = $this->getSlugsOfSubPages((int)$row['uid'], $site);
            }
        }
        return array_merge(...$pageUrls);
    }
}
