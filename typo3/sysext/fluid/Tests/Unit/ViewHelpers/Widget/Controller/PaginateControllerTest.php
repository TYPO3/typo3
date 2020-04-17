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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Widget\Controller;

use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\PaginateController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class PaginateControllerTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Query
     */
    protected $query;

    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\PaginateController|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $controller;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface
     */
    protected $querySettings;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface
     */
    protected $backend;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
     */
    protected $dataMapper;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->query = $this->getAccessibleMock(Query::class, ['dummy'], ['someType']);
        $this->querySettings = $this->createMock(QuerySettingsInterface::class);
        $this->query->_set('querySettings', $this->querySettings);
        $this->persistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $this->backend = $this->createMock(BackendInterface::class);
        $this->query->_set('persistenceManager', $this->persistenceManager);
        $this->dataMapper = $this->createMock(DataMapper::class);
        $this->query->_set('dataMapper', $this->dataMapper);
        $this->controller = $this->getAccessibleMock(
            PaginateController::class,
            ['dummy'],
            [],
            '',
            false
        );
        $this->controller->_set('view', $this->createMock(ViewInterface::class));
    }

    /**
     * @test
     */
    public function calculateDisplayRangeDeterminesCorrectDisplayRangeStartAndEndForEvenMaximumNumberOfLinks()
    {
        $this->controller->_set('maximumNumberOfLinks', 8);
        $this->controller->_set('numberOfPages', 100);
        $this->controller->_set('currentPage', 50);
        $this->controller->_call('calculateDisplayRange');
        self::assertSame(46, $this->controller->_get('displayRangeStart'));
        self::assertSame(53, $this->controller->_get('displayRangeEnd'));
    }

    /**
     * @test
     */
    public function calculateDisplayRangeDeterminesCorrectDisplayRangeStartAndEndForOddMaximumNumberOfLinks()
    {
        $this->controller->_set('maximumNumberOfLinks', 7);
        $this->controller->_set('numberOfPages', 100);
        $this->controller->_set('currentPage', 50);
        $this->controller->_call('calculateDisplayRange');
        self::assertSame(47, $this->controller->_get('displayRangeStart'));
        self::assertSame(53, $this->controller->_get('displayRangeEnd'));
    }

    /**
     * @test
     */
    public function calculateDisplayRangeDeterminesCorrectDisplayRangeStartAndEndForEvenMaximumNumberOfLinksWhenOnFirstPage()
    {
        $this->controller->_set('maximumNumberOfLinks', 8);
        $this->controller->_set('numberOfPages', 100);
        $this->controller->_set('currentPage', 1);
        $this->controller->_call('calculateDisplayRange');
        self::assertSame(1, $this->controller->_get('displayRangeStart'));
        self::assertSame(8, $this->controller->_get('displayRangeEnd'));
    }

    /**
     * @test
     */
    public function calculateDisplayRangeDeterminesCorrectDisplayRangeStartAndEndForOddMaximumNumberOfLinksWhenOnFirstPage()
    {
        $this->controller->_set('maximumNumberOfLinks', 7);
        $this->controller->_set('numberOfPages', 100);
        $this->controller->_set('currentPage', 1);
        $this->controller->_call('calculateDisplayRange');
        self::assertSame(1, $this->controller->_get('displayRangeStart'));
        self::assertSame(7, $this->controller->_get('displayRangeEnd'));
    }

    /**
     * @test
     */
    public function calculateDisplayRangeDeterminesCorrectDisplayRangeStartAndEndForEvenMaximumNumberOfLinksWhenOnLastPage()
    {
        $this->controller->_set('maximumNumberOfLinks', 8);
        $this->controller->_set('numberOfPages', 100);
        $this->controller->_set('currentPage', 100);
        $this->controller->_call('calculateDisplayRange');
        self::assertSame(93, $this->controller->_get('displayRangeStart'));
        self::assertSame(100, $this->controller->_get('displayRangeEnd'));
    }

    /**
     * @test
     */
    public function calculateDisplayRangeDeterminesCorrectDisplayRangeStartAndEndForOddMaximumNumberOfLinksWhenOnLastPage()
    {
        $this->controller->_set('maximumNumberOfLinks', 7);
        $this->controller->_set('numberOfPages', 100);
        $this->controller->_set('currentPage', 100);
        $this->controller->_call('calculateDisplayRange');
        self::assertSame(94, $this->controller->_get('displayRangeStart'));
        self::assertSame(100, $this->controller->_get('displayRangeEnd'));
    }

    /**
     * @test
     */
    public function acceptQueryResultInterfaceAsObjects()
    {
        $mockQueryResult = $this->createMock(QueryResultInterface::class);
        $mockQuery = $this->createMock(QueryInterface::class);
        $mockQueryResult->expects(self::any())->method('getQuery')->willReturn($mockQuery);
        $this->controller->_set('objects', $mockQueryResult);
        $this->controller->_set('widgetConfiguration', ['as' => 'paginatedObjects']);
        $this->controller->indexAction();
        self::assertSame($mockQueryResult, $this->controller->_get('objects'));
    }

    /**
     * @test
     */
    public function acceptArrayAsObjects()
    {
        $objects = [];
        $this->controller->_set('objects', $objects);
        $this->controller->_set('widgetConfiguration', ['as' => 'paginatedObjects']);
        $this->controller->indexAction();
        self::assertSame($objects, $this->controller->_get('objects'));
    }

    /**
     * @test
     */
    public function acceptObjectStorageAsObjects()
    {
        $objects = new ObjectStorage();
        $this->controller->_set('objects', $objects);
        $this->controller->_set('widgetConfiguration', ['as' => 'paginatedObjects']);
        $this->controller->indexAction();
        self::assertSame($objects, $this->controller->_get('objects'));
    }

    /**
     * @test
     */
    public function prepareObjectsSliceReturnsCorrectPortionForObjectStorageAndFirstPage()
    {
        $objects = new ObjectStorage();
        for ($i = 0; $i <= 25; $i++) {
            $item = new \stdClass();
            $objects->attach($item);
        }
        $this->controller->_set('objects', $objects);
        $expectedPortion = [];
        for ($j = 0; $j <= 9; $j++) {
            $expectedPortion[] = $objects->toArray()[$j];
        }
        self::assertSame($expectedPortion, $this->controller->_call('prepareObjectsSlice', 10, 0));
    }

    /**
     * @test
     */
    public function prepareObjectsSliceReturnsCorrectPortionForObjectStorageAndSecondPage()
    {
        $this->controller->_set('currentPage', 2);
        $objects = new ObjectStorage();
        for ($i = 0; $i <= 55; $i++) {
            $item = new \stdClass();
            $objects->attach($item);
        }
        $this->controller->_set('objects', $objects);
        $expectedPortion = [];
        for ($j = 10; $j <= 19; $j++) {
            $expectedPortion[] = $objects->toArray()[$j];
        }
        self::assertSame($expectedPortion, $this->controller->_call('prepareObjectsSlice', 10, 10));
    }

    /**
     * @test
     */
    public function prepareObjectsSliceReturnsCorrectPortionForObjectStorageAndLastPage()
    {
        $this->controller->_set('currentPage', 3);
        $objects = new ObjectStorage();
        for ($i = 0; $i <= 25; $i++) {
            $item = new \stdClass();
            $objects->attach($item);
        }
        $this->controller->_set('objects', $objects);
        $expectedPortion = [];
        for ($j = 20; $j <= 25; $j++) {
            $expectedPortion[] = $objects->toArray()[$j];
        }
        self::assertSame($expectedPortion, $this->controller->_call('prepareObjectsSlice', 10, 20));
    }

    /**
     * @test
     */
    public function prepareObjectsSliceReturnsCorrectPortionForArrayAndFirstPage()
    {
        $objects = [];
        for ($i = 0; $i <= 25; $i++) {
            $item = new \stdClass();
            $objects[] = $item;
        }
        $this->controller->_set('objects', $objects);
        $expectedPortion = [];
        for ($j = 0; $j <= 9; $j++) {
            $expectedPortion = array_slice($objects, 0, 10);
        }
        self::assertSame($expectedPortion, $this->controller->_call('prepareObjectsSlice', 10, 0));
    }

    /**
     * @test
     */
    public function prepareObjectsSliceReturnsCorrectPortionForArrayAndSecondPage()
    {
        $this->controller->_set('currentPage', 2);
        $objects = [];
        for ($i = 0; $i <= 55; $i++) {
            $item = new \stdClass();
            $objects[] = $item;
        }
        $this->controller->_set('objects', $objects);
        $expectedPortion = [];
        for ($j = 10; $j <= 19; $j++) {
            $expectedPortion = array_slice($objects, 10, 10);
        }
        self::assertSame($expectedPortion, $this->controller->_call('prepareObjectsSlice', 10, 10));
    }
}
