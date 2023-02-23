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

namespace TYPO3\CMS\Backend\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Search\Event\ModifyResultItemInLiveSearchEvent;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\DemandPropertyName;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\MutableSearchDemand;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\SearchDemand;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchProviderRegistry;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;

/**
 * Returns the results for any live searches, e.g. in the toolbar
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Controller]
class LiveSearchController
{
    private const LIMIT = 50;

    public function __construct(
        protected readonly SearchProviderRegistry $searchProviderRegistry,
        protected readonly BackendViewFactory $backendViewFactory,
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Processes all AJAX calls and sends back a JSON object
     */
    public function searchAction(ServerRequestInterface $request): ResponseInterface
    {
        $mutableSearchDemand = MutableSearchDemand::fromRequest($request);
        if ($mutableSearchDemand->getQuery() === '') {
            return new Response('', 400, [], 'Argument "query" is missing or empty.');
        }

        $searchResults = [];
        $remainingItems = self::LIMIT;

        foreach ($this->searchProviderRegistry->getProviders() as $provider) {
            if ($remainingItems < 1) {
                break;
            }

            if ($mutableSearchDemand->getSearchProviders() !== [] && !in_array(get_class($provider), $mutableSearchDemand->getSearchProviders(), true)) {
                continue;
            }

            $mutableSearchDemand->setProperty(DemandPropertyName::limit, $remainingItems);
            $providerResult = $provider->find($mutableSearchDemand->freeze());
            foreach ($providerResult as $key => $resultItem) {
                $modifyRecordEvent = $this->eventDispatcher->dispatch(new ModifyResultItemInLiveSearchEvent($resultItem));
                $providerResult[$key] = $modifyRecordEvent->getResultItem();
            }
            $remainingItems -= count($providerResult);

            $searchResults[] = $providerResult;
        }

        $flattenedSearchResults = array_merge([], ...$searchResults);

        return new JsonResponse($flattenedSearchResults);
    }

    public function formAction(ServerRequestInterface $request): ResponseInterface
    {
        $hints = [
            'LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:liveSearch_helpDescriptionPages',
            'LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:liveSearch_helpDescriptionContent',
            'LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:liveSearch_help.shortcutOpen',
        ];
        $randomHintKey = array_rand($hints);

        $searchDemand = SearchDemand::fromRequest($request);
        $searchProviders = [];
        foreach ($this->searchProviderRegistry->getProviders() as $searchProviderClassName => $searchProvider) {
            $searchProviders[$searchProviderClassName] = [
                'instance' => $searchProvider,
                'isActive' => in_array($searchProviderClassName, $searchDemand->getSearchProviders(), true),
            ];
        }

        $activeOptions = 0;
        $activeOptions += count(array_filter($searchProviders, fn (array $searchProviderOption) => $searchProviderOption['isActive']));

        $view = $this->backendViewFactory->create($request, ['typo3/cms-backend']);
        $view->assignMultiple([
            'searchDemand' => $searchDemand,
            'hint' => $hints[$randomHintKey],
            'searchProviders' => $searchProviders,
            'activeOptions' => $activeOptions,
        ]);

        $response = new Response();
        $response->getBody()->write($view->render('LiveSearch/Form'));

        return $response;
    }
}
