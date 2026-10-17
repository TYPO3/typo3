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

namespace TYPO3\CMS\Backend\Tests\Unit\Search\LiveSearch;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Search\LiveSearch\ResultItem;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\DemandProperty;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\DemandPropertyName;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\SearchDemand;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchProviderInterface;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchProviderRegistry;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchRepository;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SearchRepositoryTest extends UnitTestCase
{
    #[Test]
    public function findDoesNotFailIfProviderCountsFewerItemsThanItReturns(): void
    {
        $provider = new class implements SearchProviderInterface {
            public function count(SearchDemand $searchDemand): int
            {
                return 1;
            }

            public function find(SearchDemand $searchDemand): array
            {
                return [new ResultItem(self::class), new ResultItem(self::class), new ResultItem(self::class)];
            }

            public function getFilterLabel(): string
            {
                return '';
            }
        };
        $eventDispatcher = new class implements EventDispatcherInterface {
            public function dispatch(object $event): object
            {
                return $event;
            }
        };
        $subject = new SearchRepository($eventDispatcher, new SearchProviderRegistry([$provider]));
        $searchDemand = new SearchDemand([new DemandProperty(DemandPropertyName::query, 'needle')]);

        $paginator = $subject->find($searchDemand);

        self::assertCount(3, array_filter($paginator->getPaginatedItems()));
    }
}
