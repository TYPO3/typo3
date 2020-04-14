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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Seo\XmlSitemap\PagesXmlSitemapDataProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PagesXmlSitemapDataProviderTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    /**
     * @var array
     */
    protected $items;

    public function setUp(): void
    {
        parent::setUp();
        $this->items = [
            [
                'loc' => 'https://yourdomain.com/page-1',
                'lastMod' => 1535655601
            ],
            [
                'loc' => 'https://yourdomain.com/page-2',
                'lastMod' => 1530432000
            ],
            [
                'loc' => 'https://yourdomain.com/page-3',
                'lastMod' => 1535655756
            ],
            [
                'loc' => 'https://yourdomain.com/page-4',
                'lastMod' => 1530432001
            ],
        ];
    }

    /**
     * @test
     */
    public function checkIfCorrectKeyIsGivenAfterConstruct(): void
    {
        $key = 'dummyKey';
        $cObj = $this->prophesize(ContentObjectRenderer::class);

        $subject = $this->getAccessibleMock(
            PagesXmlSitemapDataProvider::class,
            ['generateItems'],
            [$this->prophesize(ServerRequestInterface::class)->reveal(), $key, [], $cObj->reveal()],
            '',
            true
        );
        self::assertEquals($key, $subject->getKey());
    }

    /**
     * @dataProvider numberOfItemsPerPageProvider
     * @test
     */
    public function checkGetItemsReturnsDefinedItems($numberOfItemsPerPage): void
    {
        $key = 'dummyKey';
        $cObj = $this->prophesize(ContentObjectRenderer::class);

        $subject = $this->getAccessibleMock(
            PagesXmlSitemapDataProvider::class,
            ['generateItems', 'defineUrl'],
            [$this->prophesize(ServerRequestInterface::class)->reveal(), $key, [], $cObj->reveal()],
            '',
            false
        );
        $subject->_set('request', $this->prophesize(ServerRequestInterface::class)->reveal());
        $subject->_set('items', $this->items);
        $subject->_set('numberOfItemsPerPage', $numberOfItemsPerPage);

        $subject->expects(self::any())->method('defineUrl')->willReturnCallback(
            function ($input) {
                return $input;
            }
        );

        $returnedItems = $subject->getItems();
        $expectedReturnedItems = array_slice($this->items, 0, $numberOfItemsPerPage);

        self::assertLessThanOrEqual($numberOfItemsPerPage, count($returnedItems));

        self::assertEquals($expectedReturnedItems, $returnedItems);
    }

    /**
     * @test
     */
    public function checkGetLastModReturnsRightDate(): void
    {
        $key = 'dummyKey';
        $cObj = $this->prophesize(ContentObjectRenderer::class);

        $subject = $this->getAccessibleMock(
            PagesXmlSitemapDataProvider::class,
            ['generateItems'],
            [$this->prophesize(ServerRequestInterface::class)->reveal(), $key, [], $cObj->reveal()],
            '',
            false
        );

        $subject->_set('items', $this->items);

        self::assertEquals(1535655756, $subject->getLastModified());
    }

    /**
     * @return array
     */
    public function numberOfItemsPerPageProvider(): array
    {
        return [
            '1 items per page' => [1],
            '3 items per page' => [3],
            '100 items per page' => [100],
        ];
    }
}
