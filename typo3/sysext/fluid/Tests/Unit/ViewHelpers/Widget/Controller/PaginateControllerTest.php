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

/**
 * Test case
 */
class PaginateControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\PaginateController
	 */
	protected $controller;

	/**
	 * Sets up this test case
	 *
	 * @return void
	 */
	public function setUp() {
		$this->controller = $this->getAccessibleMock('TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\PaginateController', array('dummy'), array(), '', FALSE);
	}

	/**
	 * @test
	 */
	public function calculateDisplayRangeDeterminesCorrectDisplayRangeStartAndEndForEvenMaximumNumberOfLinks() {
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
	public function calculateDisplayRangeDeterminesCorrectDisplayRangeStartAndEndForOddMaximumNumberOfLinks() {
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
	public function calculateDisplayRangeDeterminesCorrectDisplayRangeStartAndEndForEvenMaximumNumberOfLinksWhenOnFirstPage() {
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
	public function calculateDisplayRangeDeterminesCorrectDisplayRangeStartAndEndForOddMaximumNumberOfLinksWhenOnFirstPage() {
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
	public function calculateDisplayRangeDeterminesCorrectDisplayRangeStartAndEndForEvenMaximumNumberOfLinksWhenOnLastPage() {
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
	public function calculateDisplayRangeDeterminesCorrectDisplayRangeStartAndEndForOddMaximumNumberOfLinksWhenOnLastPage() {
		$this->controller->_set('maximumNumberOfLinks', 7);
		$this->controller->_set('numberOfPages', 100);
		$this->controller->_set('currentPage', 100);
		$this->controller->_call('calculateDisplayRange');
		$this->assertSame(94, $this->controller->_get('displayRangeStart'));
		$this->assertSame(100, $this->controller->_get('displayRangeEnd'));
	}
}