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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Pagination;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Pagination\ExtensionListPaginator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ExtensionListPaginatorTest extends UnitTestCase
{
    #[Test]
    public function onlyTheItemsOfTheCurrentPageAreFetched(): void
    {
        $calls = [];
        $subject = new ExtensionListPaginator(
            function (int $offset, int $limit) use (&$calls): array {
                $calls[] = [$offset, $limit];
                return [$this->createExtension('a'), $this->createExtension('b')];
            },
            2870,
            3,
            10
        );

        self::assertSame([[20, 10]], $calls);
        self::assertCount(2, $subject->getPaginatedItems());
    }

    /**
     * The number of pages follows the total, not the amount of items handed back
     * for the current page - that is the whole point of paging at the source.
     */
    #[Test]
    public function theNumberOfPagesIsDerivedFromTheTotalAndNotFromTheFetchedItems(): void
    {
        $subject = new ExtensionListPaginator(
            fn(): array => [$this->createExtension('a')],
            95,
            1,
            10
        );

        self::assertSame(10, $subject->getNumberOfPages());
        self::assertCount(1, $subject->getPaginatedItems());
    }

    #[Test]
    public function aPageBeyondTheLastOneIsResolvedToTheLastPage(): void
    {
        $offsets = [];
        $subject = new ExtensionListPaginator(
            function (int $offset) use (&$offsets): array {
                $offsets[] = $offset;
                return [$this->createExtension('a')];
            },
            25,
            9,
            10
        );

        self::assertSame(3, $subject->getCurrentPageNumber());
        self::assertSame([20], $offsets);
    }

    #[Test]
    public function anEmptyListStillReportsOnePage(): void
    {
        $subject = new ExtensionListPaginator(fn(): array => [], 0);

        self::assertSame(1, $subject->getNumberOfPages());
        self::assertSame([], $subject->getPaginatedItems());
    }

    private function createExtension(string $extensionKey): Extension
    {
        $extension = new Extension();
        $extension->extensionKey = $extensionKey;
        return $extension;
    }
}
