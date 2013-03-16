<?php
namespace TYPO3\CMS\Core\Tests\Unit\Error;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Oliver Klee <typo3-coding@oliverklee.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * testcase for the \TYPO3\CMS\Core\Error\ProductionExceptionHandler class.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class ProductionExceptionHandlerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Error\ProductionExceptionHandler|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $fixture = NULL;

	/**
	 * Sets up this test case.
	 */
	protected function setUp() {
		$this->fixture = $this->getMock('TYPO3\\CMS\\Core\\Error\\ProductionExceptionHandler', array('discloseExceptionInformation', 'sendStatusHeaders', 'writeLogEntries'), array(), '', FALSE);
		$this->fixture->expects($this->any())->method('discloseExceptionInformation')->will($this->returnValue(TRUE));
	}

	/**
	 * Tears down this test case.
	 */
	protected function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function echoExceptionWebEscapesExceptionMessage() {
		$message = '<b>b</b><script>alert(1);</script>';
		$exception = new \Exception($message);
		ob_start();
		$this->fixture->echoExceptionWeb($exception);
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertContains(htmlspecialchars($message), $output);
		$this->assertNotContains($message, $output);
	}

	/**
	 * @test
	 */
	public function echoExceptionWebEscapesExceptionTitle() {
		$title = '<b>b</b><script>alert(1);</script>';
		/** @var $exception \Exception|\PHPUnit_Framework_MockObject_MockObject */
		$exception = $this->getMock('Exception', array('getTitle'), array('some message'));
		$exception->expects($this->any())->method('getTitle')->will($this->returnValue($title));
		ob_start();
		$this->fixture->echoExceptionWeb($exception);
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertContains(htmlspecialchars($title), $output);
		$this->assertNotContains($title, $output);
	}

}

?>