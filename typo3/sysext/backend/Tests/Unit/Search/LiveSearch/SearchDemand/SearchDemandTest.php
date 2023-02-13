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

namespace TYPO3\CMS\Backend\Tests\Unit\Search\LiveSearch\SearchDemand;

use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Backend\Search\LiveSearch\DatabaseRecordProvider;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\DemandProperty;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\DemandPropertyName;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\SearchDemand;
use TYPO3\CMS\Core\Http\ServerRequest;

class SearchDemandTest extends TestCase
{
    /**
     * @test
     */
    public function getPropertyReturnsValueAsExpected(): void
    {
        $query = 'foo';
        $limit = 42;

        $searchDemand = new SearchDemand([
            new DemandProperty(DemandPropertyName::query, $query),
            new DemandProperty(DemandPropertyName::limit, $limit),
        ]);

        self::assertSame($query, $searchDemand->getProperty(DemandPropertyName::query)->getValue());
        self::assertSame($limit, $searchDemand->getProperty(DemandPropertyName::limit)->getValue());
    }

    /**
     * @test
     */
    public function fromRequestCreatesExpectedDemand(): void
    {
        $query = 'Karl Ranseier';
        $limit = 10;
        $offset = 1;
        $searchProviders = [
            DatabaseRecordProvider::class,
        ];

        $request = (new ServerRequest())->withParsedBody([
            'query' => $query,
            'limit' => $limit,
            'offset' => $offset,
            'searchProviders' => $searchProviders,
        ]);
        $searchDemand = SearchDemand::fromRequest($request);

        self::assertSame($query, $searchDemand->getQuery());
        self::assertSame($limit, $searchDemand->getLimit());
        self::assertSame($offset, $searchDemand->getOffset());
        self::assertSame($searchProviders, $searchDemand->getSearchProviders());
    }
}
