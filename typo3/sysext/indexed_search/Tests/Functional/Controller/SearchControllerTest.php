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

namespace TYPO3\CMS\IndexedSearch\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Pagination\PaginationInterface;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\IndexedSearch\Controller\SearchController;
use TYPO3\CMS\IndexedSearch\Domain\Repository\IndexSearchRepository;
use TYPO3\CMS\IndexedSearch\Lexer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SearchControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'indexed_search',
    ];

    public static function paginationRespectsPageLinksSettingDataProvider(): array
    {
        return [
            'window around current page' => [
                ['page_links' => 5],
                10,
                [9, 10, 11, 12, 13],
            ],
            'window at first page' => [
                ['page_links' => 5],
                0,
                [1, 2, 3, 4, 5],
            ],
            'window at last page' => [
                ['page_links' => 5],
                19,
                [16, 17, 18, 19, 20],
            ],
            'default of 10 page links' => [
                [],
                0,
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            ],
            'page links exceeding the number of pages' => [
                ['page_links' => 50],
                0,
                range(1, 20),
            ],
            'invalid page links fall back to 10' => [
                ['page_links' => 0],
                0,
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            ],
        ];
    }

    #[DataProvider('paginationRespectsPageLinksSettingDataProvider')]
    #[Test]
    public function paginationRespectsPageLinksSetting(array $settings, int $pointer, array $expectedPageNumbers): void
    {
        $controller = new class (
            $this->createMock(Context::class),
            $this->createMock(IndexSearchRepository::class),
            $this->createMock(TypoScriptService::class),
            $this->createMock(Lexer::class),
            $this->createMock(LinkFactory::class),
        ) extends SearchController {
            public function callBuildPagination(array $searchData, array $rows, int $count): PaginationInterface
            {
                return $this->buildPagination($searchData, $rows, $count);
            }

            public function setSettingsForTest(array $settings): void
            {
                $this->settings = $settings;
            }
        };

        $controller->setSettingsForTest($settings);
        $pagination = $controller->callBuildPagination(
            ['pointer' => $pointer, 'numberOfResults' => 10],
            array_fill(0, 10, ['item_title' => 'Result']),
            200
        );

        self::assertSame($expectedPageNumbers, $pagination->getAllPageNumbers());
    }
}
