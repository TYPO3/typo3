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
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\SearchDemand;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchRepository;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;

/**
 * Returns the results for any live searches, e.g. in the toolbar
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Controller]
class LiveSearchController
{
    public function __construct(
        protected readonly BackendViewFactory $backendViewFactory,
        protected readonly SearchRepository $searchService,
    ) {
    }

    /**
     * Processes all AJAX calls and sends back a JSON object
     */
    public function searchAction(ServerRequestInterface $request): ResponseInterface
    {
        $mutableSearchDemand = SearchDemand::fromRequest($request);
        if ($mutableSearchDemand->getQuery() === '') {
            return new Response('', 400, [], 'Argument "query" is missing or empty.');
        }

        $results = $this->searchService->find($mutableSearchDemand);
        $pagination = new SlidingWindowPagination($results, 15);
        $response = [
            'pagination' => [
                'itemsPerPage' => SearchDemand::DEFAULT_LIMIT,
                'currentPage' => $pagination->getPaginator()->getCurrentPageNumber(),
                'firstPage' => $pagination->getFirstPageNumber(),
                'lastPage' => $pagination->getLastPageNumber(),
                'allPageNumbers' => $pagination->getAllPageNumbers(),
                'previousPageNumber' => $pagination->getPreviousPageNumber(),
                'nextPageNumber' => $pagination->getNextPageNumber(),
                'hasMorePages' => $pagination->getHasMorePages(),
                'hasLessPages' => $pagination->getHasLessPages(),
            ],
            'results' => $results->getPaginatedItems(),
        ];

        return new JsonResponse($response);
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
        $searchProviders = $this->searchService->getSearchProviderState($searchDemand);

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
