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

namespace TYPO3\CMS\Seo\Tests\Unit\XmlSitemap;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Seo\XmlSitemap\XmlSitemap;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class XmlSitemapTest extends UnitTestCase
{
    private const ITEMS = [
        ['loc' => 'https://yourdomain.com/page-1', 'lastMod' => 1535655601],
        ['loc' => 'https://yourdomain.com/page-2', 'lastMod' => 1530432000],
        ['loc' => 'https://yourdomain.com/page-3', 'lastMod' => 1535655756],
        ['loc' => 'https://yourdomain.com/page-4', 'lastMod' => 1530432001],
    ];

    public static function forPageReturnsItemsOfRequestedPageDataProvider(): \Generator
    {
        yield 'first page with one item per page' => [0, 1, [self::ITEMS[0]]];
        yield 'last page with one item per page' => [3, 1, [self::ITEMS[3]]];
        yield 'page beyond the last one' => [4, 1, []];
        yield 'first page with three items per page' => [0, 3, [self::ITEMS[0], self::ITEMS[1], self::ITEMS[2]]];
        yield 'second page with three items per page' => [1, 3, [self::ITEMS[3]]];
        yield 'all items on one page' => [0, 100, self::ITEMS];
    }

    #[DataProvider('forPageReturnsItemsOfRequestedPageDataProvider')]
    #[Test]
    public function forPageReturnsItemsOfRequestedPage(int $page, int $itemsPerPage, array $expectedItems): void
    {
        $subject = XmlSitemap::forPage(self::ITEMS, $page, null, $itemsPerPage);

        self::assertSame($expectedItems, $subject->getItems());
    }

    #[Test]
    public function forPageCalculatesNumberOfPages(): void
    {
        self::assertSame(4, XmlSitemap::forPage(self::ITEMS, 0, null, 1)->getNumberOfPages());
        self::assertSame(2, XmlSitemap::forPage(self::ITEMS, 0, null, 3)->getNumberOfPages());
        self::assertSame(1, XmlSitemap::forPage(self::ITEMS, 0, null, 100)->getNumberOfPages());
        self::assertSame(0, XmlSitemap::forPage([], 0, null, 100)->getNumberOfPages());
    }

    #[Test]
    public function forPageDeterminesLastModifiedOfAllItems(): void
    {
        $subject = XmlSitemap::forPage(self::ITEMS, 1, null, 3);

        self::assertSame(1535655756, $subject->getLastModified());
    }

    #[Test]
    public function forPageDeterminesLastModifiedWithoutItems(): void
    {
        self::assertSame(0, XmlSitemap::forPage([])->getLastModified());
        self::assertSame(0, XmlSitemap::forPage([['loc' => 'https://yourdomain.com/page-1']])->getLastModified());
    }

    #[Test]
    public function forPageAppliesItemMapperToItemsOfRequestedPageOnly(): void
    {
        $mappedItems = new \ArrayObject();
        $subject = XmlSitemap::forPage(
            self::ITEMS,
            1,
            static function (array $item) use ($mappedItems): array {
                $mappedItems[] = $item['loc'];
                $item['loc'] .= '?mapped=1';
                return $item;
            },
            2
        );

        // Items are resolved when they are requested, the sitemap index needs the meta data only
        self::assertSame([], $mappedItems->getArrayCopy());

        $items = $subject->getItems();

        self::assertSame(['https://yourdomain.com/page-3', 'https://yourdomain.com/page-4'], $mappedItems->getArrayCopy());
        self::assertSame('https://yourdomain.com/page-3?mapped=1', $items[0]['loc']);
    }
}
