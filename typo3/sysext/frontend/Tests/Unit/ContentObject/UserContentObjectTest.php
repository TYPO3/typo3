<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Philipp Gampe <philipp.gampe@typo3.org>
 *  All rights reserved
 *
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Frontend\ContentObject\UserContentObject;

/**
 * Testcase for \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
 *
 * @author Philipp Gampe <philipp.gampe@typo3.org>
 */
class UserContentObjectTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Set up
	 */
	public function setUp() {
		$GLOBALS['TT'] = new \TYPO3\CMS\Core\TimeTracker\TimeTracker();
	}

	/**
	 * Tear down
	 */
	public function tearDown() {
		unset($GLOBALS['TT']);
	}

	/**
	 * @test
	 */
	public function renderCallsUserFunction() {
		$configuration = array('userFunc' => 'myUserFunction');

		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObjectRenderer
		 */
		$contentObjectRenderer = $this->getMock(
			'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer',
			array('callUserFunction', 'includeLibs')
		);
		$contentObjectRenderer
			->expects($this->once())
			->method('includeLibs')
			->with($configuration);
		$contentObjectRenderer
			->expects($this->once())
			->method('callUserFunction')
			->with($configuration['userFunc']);

		$fixture = new UserContentObject($contentObjectRenderer);
		$fixture->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderCatchesExceptionAndConvertsItToEmptyString() {
		$configuration = array('userFunc' => 'myUserFunction');

		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObjectRenderer
		 */
		$contentObjectRenderer = $this->getMock(
			'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer',
			array('callUserFunction', 'includeLibs')
		);

		$exception = new \Exception('My exception message.');
		$contentObjectRenderer
			->expects($this->once())
			->method('includeLibs')
			->with($configuration);
		$contentObjectRenderer
			->expects($this->once())
			->method('callUserFunction')
			->will($this->throwException($exception));

		$fixture = new UserContentObject($contentObjectRenderer);
		$content = $fixture->render($configuration);
		$this->assertEquals('', $content);
	}

}

?>