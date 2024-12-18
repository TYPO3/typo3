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

namespace TYPO3\CMS\Backend\Search\EventListener;

use TYPO3\CMS\Backend\Search\Event\ModifyConstraintsForLiveSearchEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Routing\SiteUrlResolver;

/**
 * Event listener to add a "search for live frontend URI" query constraint
 *
 * @internal
 */
final readonly class AddLiveSearchFrontendUriResolverListener
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private SiteUrlResolver $siteUrlResolver
    ) {}

    /**
     * For a similar implementation to the Page Tree filter instead of "Live Search":
     * @see \TYPO3\CMS\Backend\Tree\Repository\PageTreeFilter->addUidsFromSearchPhraseWithFrontendUri()
     */
    #[AsEventListener('typo3/cms-backend/add-live-search-frontend-uri-resolver')]
    public function __invoke(ModifyConstraintsForLiveSearchEvent $event): void
    {
        if ($event->getTableName() !== 'pages') {
            return;
        }

        $queryString = $event->getSearchDemand()->getQuery();

        // Only if a search pattern uses "http(s)://...." then a frontend URL will be resolved.
        if (!str_starts_with($queryString, 'http://') && !str_starts_with($queryString, 'https://')) {
            return;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $resolvedPage = $this->siteUrlResolver->resolvePageUidAndLanguageBySiteUrl($queryString);
        if ($resolvedPage !== null) {
            $event->addConstraint(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $resolvedPage['uid'],
                    ),
                    // On top of the common default page, finding a result by its URI also attaches
                    // the specific language version, too.
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq(
                            'sys_language_uid',
                            $resolvedPage['languageUid'],
                        ),
                        $queryBuilder->expr()->eq(
                            'l10n_parent',
                            $resolvedPage['uid'],
                        ),
                    ),
                ),
            );
        }
    }
}
