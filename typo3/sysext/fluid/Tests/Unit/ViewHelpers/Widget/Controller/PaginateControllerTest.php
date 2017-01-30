<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Widget\Controller;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Test case
 */
class PaginateControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Query
     */
    protected $query;

    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\PaginateController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
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
     *
     * @return void
     */
    protected function setUp()
    {
        $this->query = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Query::class, ['dummy'], ['someType']);
        $this->querySettings = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::class);
        $this->query->_set('querySettings', $this->querySettings);
        $this->persistenceManager = $this->getMock(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);
        $this->backend = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface::class);
        $this->backend->expects($this->any())->method('getQomFactory')->will($this->returnValue(null));
        $this->persistenceManager->expects($this->any())->method('getBackend')->will($this->returnValue($this->backend));
        $this->query->_set('persistenceManager', $this->persistenceManager);
        $this->dataMapper = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class);
        $this->query->_set('dataMapper', $this->dataMapper);
        $this->controller = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\PaginateController::class,
            ['dummy'], [], '', false);
        $this->controller->_set('view', $this->getMock(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface::class));
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
        $this->assertSame(46, $this->controller->_get('displayRangeStart'));
        $this->assertSame(53, $this->controller->_get('displayRangeEnd'));
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
        $this->assertSame(47, $this->controller->_get('displayRangeStart'));
        $this->assertSame(53, $this->controller->_get('displayRangeEnd'));
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
        $this->assertSame(1, $this->controller->_get('displayRangeStart'));
        $this->assertSame(8, $this->controller->_get('displayRangeEnd'));
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
        $this->assertSame(1, $this->controller->_get('displayRangeStart'));
        $this->assertSame(7, $this->controller->_get('displayRangeEnd'));
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
        $this->assertSame(93, $this->controller->_get('displayRangeStart'));
        $this->assertSame(100, $this->controller->_get('displayRangeEnd'));
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
        $this->assertSame(94, $this->controller->_get('displayRangeStart'));
        $this->assertSame(100, $this->controller->_get('displayRangeEnd'));
    }

    /**
     * @test
     */
    public function acceptQueryResultInterfaceAsObjects()
    {
        $mockQueryResult = $this->getMock(\TYPO3\CMS\Extbase\Persistence\QueryResultInterface::class);
        $mockQuery = $this->getMock(\TYPO3\CMS\Extbase\Persistence\QueryInterface::class);
        $mockQueryResult->expects($this->any())->method('getQuery')->will($this->returnValue($mockQuery));
        $this->controller->_set('objects', $mockQueryResult);
        $this->controller->indexAction();
        $this->assertSame($mockQueryResult, $this->controller->_get('objects'));
    }

    /**
     * @test
     */
    public function acceptArrayAsObjects()
    {
        $objects = [];
        $this->controller->_set('objects', $objects);
        $this->controller->indexAction();
        $this->assertSame($objects, $this->controller->_get('objects'));
    }

    /**
     * @test
     */
    public function acceptObjectStorageAsObjects()
    {
        $objects = new ObjectStorage();
        $this->controller->_set('objects', $objects);
        $this->controller->indexAction();
        $this->assertSame($objects, $this->controller->_get('objects'));
    }

    /**
     * @test
     */
    public function prepareObjectsSliceReturnsCorrectPortionForObjectStorageAndFirstPage()
    {
        $objects = new ObjectStorage();
        for ($i = 0; $i <= 25; $i++) {
            $item = new \StdClass;
            $objects->attach($item);
        }
        $this->controller->_set('objects', $objects);
        $expectedPortion = [];
        for ($j = 0; $j <= 9; $j++) {
            $expectedPortion[] = $objects->toArray()[$j];
        }
        $this->assertSame($expectedPortion, $this->controller->_call('prepareObjectsSlice', 10, 0));
    }

    /**
     * @test
     */
    public function prepareObjectsSliceReturnsCorrectPortionForObjectStorageAndSecondPage()
    {
        $this->controller->_set('currentPage', 2);
        $objects = new ObjectStorage();
        for ($i = 0; $i <= 55; $i++) {
            $item = new \StdClass;
            $objects->attach($item);
        }
        $this->controller->_set('objects', $objects);
        $expectedPortion = [];
        for ($j = 10; $j <= 19; $j++) {
            $expectedPortion[] = $objects->toArray()[$j];
        }
        $this->assertSame($expectedPortion, $this->controller->_call('prepareObjectsSlice', 10, 10));
    }

    /**
     * @test
     */
    public function prepareObjectsSliceReturnsCorrectPortionForObjectStorageAndLastPage()
    {
        $this->controller->_set('currentPage', 3);
        $objects = new ObjectStorage();
        for ($i = 0; $i <= 25; $i++) {
            $item = new \stdClass;
            $objects->attach($item);
        }
        $this->controller->_set('objects', $objects);
        $expectedPortion = [];
        for ($j = 20; $j <= 25; $j++) {
            $expectedPortion[] = $objects->toArray()[$j];
        }
        $this->assertSame($expectedPortion, $this->controller->_call('prepareObjectsSlice', 10, 20));
    }

    /**
     * @test
     */
    public function prepareObjectsSliceReturnsCorrectPortionForArrayAndFirstPage()
    {
        $objects = [];
        for ($i = 0; $i <= 25; $i++) {
            $item = new \StdClass;
            $objects[] = $item;
        }
        $this->controller->_set('objects', $objects);
        $expectedPortion = [];
        for ($j = 0; $j <= 9; $j++) {
            $expectedPortion = array_slice($objects, 0, 10);
        }
        $this->assertSame($expectedPortion, $this->controller->_call('prepareObjectsSlice', 10, 0));
    }

    /**
     * @test
     */
    public function prepareObjectsSliceReturnsCorrectPortionForArrayAndSecondPage()
    {
        $this->controller->_set('currentPage', 2);
        $objects = [];
        for ($i = 0; $i <= 55; $i++) {
            $item = new \StdClass;
            $objects[] = $item;
        }
        $this->controller->_set('objects', $objects);
        $expectedPortion = [];
        for ($j = 10; $j <= 19; $j++) {
            $expectedPortion = array_slice($objects, 10, 10);
        }
        $this->assertSame($expectedPortion, $this->controller->_call('prepareObjectsSlice', 10, 10));
    }
}
