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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchProviderRegistry;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;

/**
 * Returns the results for any live searches, e.g. in the toolbar
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class LiveSearchController
{
    private const LIMIT = 50;

    public function __construct(
        protected readonly SearchProviderRegistry $searchProviderRegistry,
        protected readonly BackendViewFactory $backendViewFactory,
    ) {
    }

    /**
     * Processes all AJAX calls and sends back a JSON object
     *
     * @return ResponseInterface
     */
    public function searchAction(ServerRequestInterface $request): ResponseInterface
    {
        if (!isset($request->getParsedBody()['q'])) {
            return new Response('', 400, [], 'Missing argument "q"');
        }

        $queryString = trim($request->getParsedBody()['q']);
        $searchProviders = $request->getParsedBody()['options']['searchProviders'] ?? [];
        $searchResults = [];
        $remainingItems = self::LIMIT;

        foreach ($this->searchProviderRegistry->getProviders() as $provider) {
            if ($remainingItems < 1) {
                break;
            }

            if ($searchProviders !== [] && !in_array(get_class($provider), $searchProviders, true)) {
                continue;
            }

            $searchDemand = new SearchDemand($queryString, $remainingItems, 0);
            $providerResult = $provider->find($searchDemand);
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

        $query = $request->getQueryParams()['q'] ?? '';
        $view = $this->backendViewFactory->create($request, ['typo3/cms-backend']);
        $view->assignMultiple([
            'query' => $query,
            'hint' => $hints[$randomHintKey],
            'searchProviders' => $this->searchProviderRegistry->getProviders(),
        ]);

        $response = new Response();
        $response->getBody()->write($view->render('LiveSearch/Form'));

        return $response;
    }
}
