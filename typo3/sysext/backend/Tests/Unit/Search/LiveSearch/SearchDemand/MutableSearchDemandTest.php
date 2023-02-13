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
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\MutableSearchDemand;
use TYPO3\CMS\Core\Http\ServerRequest;

class MutableSearchDemandTest extends TestCase
{
    /**
     * @test
     */
    public function setPropertyWorksAsExpected(): void
    {
        $query = 'foo';

        $mutableSearchDemand = new MutableSearchDemand();
        $mutableSearchDemand->setProperty(DemandPropertyName::query, $query);

        self::assertSame($query, $mutableSearchDemand->getProperty(DemandPropertyName::query)->getValue());
    }

    /**
     * @test
     */
    public function consecutiveSetPropertyCallWithSameNameWorksAsExpected(): void
    {
        $limit = 42;

        $mutableSearchDemand = new MutableSearchDemand();
        $mutableSearchDemand->setProperty(DemandPropertyName::limit, 0);
        $mutableSearchDemand->setProperty(DemandPropertyName::limit, 1);
        $mutableSearchDemand->setProperty(DemandPropertyName::limit, $limit);

        self::assertSame($limit, $mutableSearchDemand->getProperty(DemandPropertyName::limit)->getValue());
    }

    /**
     * @test
     */
    public function freezeSetsSameDemandProperties(): void
    {
        $mutableSearchDemand = new MutableSearchDemand([
            new DemandProperty(DemandPropertyName::query, 'foo'),
        ]);
        $searchDemand = $mutableSearchDemand->freeze();

        self::assertSame($mutableSearchDemand->getProperties(), $searchDemand->getProperties());
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
        $mutableSearchDemand = MutableSearchDemand::fromRequest($request);

        self::assertSame($query, $mutableSearchDemand->getQuery());
        self::assertSame($limit, $mutableSearchDemand->getLimit());
        self::assertSame($offset, $mutableSearchDemand->getOffset());
        self::assertSame($searchProviders, $mutableSearchDemand->getSearchProviders());
    }
}
