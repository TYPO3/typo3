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
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Frontend\ContentObject\UserContentObject;

/**
 * Testcase
 *
 * @author Philipp Gampe <philipp.gampe@typo3.org>
 */
class UserContentObjectTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * Set up
	 */
	public function setUp() {
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
		$GLOBALS['TT'] = new TimeTracker();
	}

	/**
	 * Tear down
	 */
	public function tearDown() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
	}


	/**
	 * @test
	 */
	public function renderCallsUserFunction() {

		$configuration = array('userFunc' => 'myUserFunction');

		/**
		 * @var $contentObjectRenderer \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer|\PHPUnit_Framework_MockObject_MockObject
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

		/**
		 * @var $fixture \TYPO3\CMS\Frontend\ContentObject\UserContentObject
		 */
		$fixture = new UserContentObject($contentObjectRenderer);
		$fixture->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderCatchesException() {

		$configuration = array('userFunc' => 'myUserFunction');

		/**
		 * @var $contentObjectRenderer \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer|\PHPUnit_Framework_MockObject_MockObject
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


		/**
		 * @var $fixture \TYPO3\CMS\Frontend\ContentObject\UserContentObject
		 */
		$fixture = new UserContentObject($contentObjectRenderer);
		$content = $fixture->render($configuration);
		$this->assertContains('My exception message.', $content);
	}

}

?>