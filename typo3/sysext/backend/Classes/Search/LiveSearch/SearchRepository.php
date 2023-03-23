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

namespace TYPO3\CMS\Backend\Search\LiveSearch;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Search\Event\ModifyResultItemInLiveSearchEvent;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\DemandPropertyName;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\MutableSearchDemand;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\SearchDemand;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;

/**
 * Repository class to ease using the search API.
 *
 * @internal
 */
final class SearchRepository
{
    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly SearchProviderRegistry $searchProviderRegistry,
    ) {
    }

    /**
     * Returns a list of available search providers including a flag whether they are currently active.
     *
     * @param SearchDemand $searchDemand
     * @return array<class-string, array{instance: SearchProviderInterface, isActive: bool}>
     */
    public function getSearchProviderState(SearchDemand $searchDemand): array
    {
        $searchProviders = [];
        foreach ($this->searchProviderRegistry->getProviders() as $searchProviderClassName => $searchProvider) {
            $searchProviders[$searchProviderClassName] = [
                'instance' => $searchProvider,
                'isActive' => in_array($searchProviderClassName, $searchDemand->getSearchProviders(), true),
            ];
        }

        return $searchProviders;
    }

    /**
     * @return SearchProviderInterface[]
     */
    public function getViableSearchProviders(SearchDemand $searchDemand): array
    {
        return array_filter(
            $this->searchProviderRegistry->getProviders(),
            static fn (SearchProviderInterface $provider) => $searchDemand->getSearchProviders() === [] || in_array(get_class($provider), $searchDemand->getSearchProviders(), true)
        );
    }

    public function find(SearchDemand $searchDemand): ArrayPaginator
    {
        $searchResults = [];
        $totalCount = 0;
        $mutableSearchDemand = MutableSearchDemand::fromSearchDemand($searchDemand);
        $offset = $searchDemand->getOffset();
        $remainingItems = $searchDemand->getLimit();

        foreach ($this->getViableSearchProviders($searchDemand) as $provider) {
            $count = $provider->count($mutableSearchDemand->freeze());
            $totalCount += $count;
            if ($count < $offset) {
                // The number of potential results is smaller than the offset, do not query results
                $offset -= $count;
                continue;
            }

            if ($remainingItems < 1) {
                continue;
            }

            $mutableSearchDemand
                ->setProperty(DemandPropertyName::limit, $remainingItems)
                ->setProperty(DemandPropertyName::offset, $offset);
            $providerResult = $provider->find($mutableSearchDemand->freeze());
            if ($providerResult !== []) {
                foreach ($providerResult as $key => $resultItem) {
                    $modifyRecordEvent = $this->eventDispatcher->dispatch(new ModifyResultItemInLiveSearchEvent($resultItem));
                    $providerResult[$key] = $modifyRecordEvent->getResultItem();
                }
                $remainingItems -= count($providerResult);
                // We got a result here, offset became irrelevant for next iteration
                $offset = 0;

                $searchResults[] = $providerResult;
            }
        }
        unset($mutableSearchDemand);

        $flattenedSearchResults = array_merge([], ...$searchResults);
        $resultCount = count($flattenedSearchResults);

        $currentPage = (int)floor(($searchDemand->getOffset() + $searchDemand->getLimit()) / $searchDemand->getLimit());
        if (ceil($totalCount / $searchDemand->getLimit()) < $currentPage) {
            // Requested page does not match with the overall amount of items, reset to first page
            $currentPage = 1;
        }

        if ($resultCount > 0) {
            // The paginator expects a full result set to be able to calculate its pagination. This will have negative
            // performance consequences, therefore we only consider the current result set and create stubs for the "gaps".
            $paginatorItems = array_merge(
                array_fill(0, $searchDemand->getOffset(), null),
                $flattenedSearchResults,
                array_fill(0, $totalCount - $searchDemand->getOffset() - $resultCount, null)
            );
        } else {
            $paginatorItems = [];
        }

        return new ArrayPaginator($paginatorItems, $currentPage, SearchDemand::DEFAULT_LIMIT);
    }
}
