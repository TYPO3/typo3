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

    public function find(MutableSearchDemand $mutableSearchDemand): array
    {
        $searchResults = [];
        $remainingItems = $mutableSearchDemand->getLimit();

        foreach ($this->getViableSearchProviders($mutableSearchDemand) as $provider) {
            if ($remainingItems < 1) {
                break;
            }

            $mutableSearchDemand
                ->setProperty(DemandPropertyName::limit, $remainingItems);
            $providerResult = $provider->find($mutableSearchDemand->freeze());
            foreach ($providerResult as $key => $resultItem) {
                $modifyRecordEvent = $this->eventDispatcher->dispatch(new ModifyResultItemInLiveSearchEvent($resultItem));
                $providerResult[$key] = $modifyRecordEvent->getResultItem();
            }
            $count = count($providerResult);
            $remainingItems -= $count;

            $searchResults[] = $providerResult;
        }

        $flattenedSearchResults = array_merge([], ...$searchResults);

        // @todo: introduce pagination and return an ArrayPaginator.
        //        Its implementation requires the full result set which is bad here for performance reasons.
        //        Fill the "gaps" with stub data.
        return $flattenedSearchResults;
    }
}
