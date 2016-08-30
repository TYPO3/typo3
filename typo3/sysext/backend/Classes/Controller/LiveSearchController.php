<?php
namespace TYPO3\CMS\Backend\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Search\LiveSearch\LiveSearch;
use TYPO3\CMS\Backend\Search\LiveSearch\QueryParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Returns the results for any live searches, e.g. in the toolbar
 */
class LiveSearchController
{
    /**
     * @var array
     */
    protected $searchResults = [];

    /**
     * Processes all AJAX calls and sends back a JSON object
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function liveSearchAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $queryString = $request->getQueryParams()['q'];
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
        $response->getBody()->write(json_encode($searchResults));
        return $response;
    }
}
