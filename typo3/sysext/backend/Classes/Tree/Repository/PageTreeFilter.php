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

namespace TYPO3\CMS\Backend\Tree\Repository;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Adds page tree filter functionality through listener to the BeforePageTreeIsFilteredEvent
 *
 * @internal
 */
final class PageTreeFilter
{
    #[AsEventListener('page-tree-uid-provider')]
    public function addUidsFromSearchPhrase(BeforePageTreeIsFilteredEvent $event): void
    {
        // Extract true integers from search string
        $searchPhrases = GeneralUtility::trimExplode(',', $event->searchPhrase, true);
        foreach ($searchPhrases as $searchPhrase) {
            if (MathUtility::canBeInterpretedAsInteger($searchPhrase) && $searchPhrase > 0) {
                $event->searchUids[] = (int)$searchPhrase;
            }
        }
        $event->searchUids = array_unique($event->searchUids);
    }

    #[AsEventListener('page-tree-wildcard-alias-filter')]
    public function addWildCardAliasFilter(BeforePageTreeIsFilteredEvent $event): void
    {
        $searchFilterWildcard = '%' . $event->queryBuilder->escapeLikeWildcards($event->searchPhrase) . '%';
        $searchWhereAlias = $event->queryBuilder->expr()->or(
            $event->queryBuilder->expr()->like(
                'nav_title',
                $event->queryBuilder->createNamedParameter($searchFilterWildcard)
            ),
            $event->queryBuilder->expr()->like(
                'title',
                $event->queryBuilder->createNamedParameter($searchFilterWildcard)
            )
        );
        $event->searchParts = $event->searchParts->with($searchWhereAlias);
    }
}
