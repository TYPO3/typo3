<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Testcase for the Response object
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ResponseTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Response|\PHPUnit_Framework_MockObject_MockObject|\Tx_Phpunit_Interface_AccessibleObject
	 */
	protected $mockResponse;

	public function setUp() {
		$this->mockResponse = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Mvc\\Response', array('dummy'));
	}

	/**
	 * @test
	 */
	public function propertyContentInitiallyIsNull() {
		$this->assertNull($this->mockResponse->_get('content'));
	}

	/**
	 * @test
	 */
	public function setContentSetsContentCorrectly() {
		$this->mockResponse->setContent('foo');
		$this->assertSame('foo', $this->mockResponse->_get('content'));
	}

	/**
	 * @test
	 */
	public function appendContentAppendsContentCorrectly() {
		$this->mockResponse->_set('content', 'foo');
		$this->mockResponse->appendContent('bar');
		$this->assertSame('foobar', $this->mockResponse->_get('content'));
	}

	/**
	 * @test
	 */
	public function getContentReturnsContentCorrectly() {
		$this->mockResponse->_set('content', 'foo');
		$this->assertSame('foo', $this->mockResponse->getContent());
	}

	/**
	 * @test
	 */
	public function __toStringReturnsActualContent() {
		$this->mockResponse->_set('content', 'foo');
		$this->assertSame('foo', (string) $this->mockResponse);
	}
}

?>