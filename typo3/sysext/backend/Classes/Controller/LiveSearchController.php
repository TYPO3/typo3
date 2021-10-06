<?php

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
use TYPO3\CMS\Backend\Search\LiveSearch\LiveSearch;
use TYPO3\CMS\Backend\Search\LiveSearch\QueryParser;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Returns the results for any live searches, e.g. in the toolbar
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class LiveSearchController
{
    /**
     * Processes all AJAX calls and sends back a JSON object
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function liveSearchAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryString = trim($request->getQueryParams()['q']);
        $liveSearch = GeneralUtility::makeInstance(LiveSearch::class);
        $queryParser = GeneralUtility::makeInstance(QueryParser::class);

        $searchResults = [];
        $liveSearch->setQueryString($queryString);
        // Jump & edit - find page and retrieve an edit link (this is only for pages
        if ($queryParser->isValidPageJump($queryString)) {
            $commandQuery = $queryParser->getCommandForPageJump($queryString);
            if ($commandQuery) {
                $queryString = $commandQuery;
            }
        }
        // Search through the database and find records who match to the given search string
        $resultArray = $liveSearch->find($queryString);
        foreach ($resultArray as $resultFromTable) {
            foreach ($resultFromTable as $item) {
                $searchResults[] = $item;
            }
        }
        return new JsonResponse($searchResults);
    }
}
