<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
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
 * Testcase for the Command Controller
 */
class CommandControllerTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\CommandController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $commandController;

	public function setUp() {
		$this->commandController = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\CommandController', array('dummy'));
	}

	/**
	 * @test
	 */
	public function outputAppendsGivenStringToTheResponseContent() {
		$mockResponse = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Response');
		$mockResponse->expects($this->once())->method('appendContent')->with('some text');
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('output', 'some text');
	}

	/**
	 * @test
	 */
	public function outputReplacesArgumentsInGivenString() {
		$mockResponse = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Response');
		$mockResponse->expects($this->once())->method('appendContent')->with('some text');
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('output', '%2$s %1$s', array('text', 'some'));
	}

	/**
	 * @test
	 */
	public function outputLineAppendsGivenStringAndNewlineToTheResponseContent() {
		$mockResponse = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Response');
		$mockResponse->expects($this->once())->method('appendContent')->with('some text' . PHP_EOL);
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('outputLine', 'some text');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
	 */
	public function quitThrowsStopActionException() {
		$mockResponse = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Response');
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('quit');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
	 */
	public function quitSetsResponseExitCode() {
		$mockResponse = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Response');
		$mockResponse->expects($this->once())->method('setExitCode')->with(123);
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('quit', 123);
	}
}

?>