<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Controller;

/**
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

/**
 * Test case
 */
class CommandControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\CommandController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $commandController;

	public function setUp() {
		$this->commandController = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\CommandController', array('dummyCommand'));
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

	/**
	 * @test
	 */
	public function settingRequestAdminPropertySetsAdminRoleInUserAuthentication() {
		$mockedUserAuthentication = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication');
		$mockedUserAuthentication->user['admin'] = 42;
		$this->commandController->expects($this->once())
			->method('dummyCommand')
			->will(
				$this->returnCallback(
					function() use ($mockedUserAuthentication) {
						if ($mockedUserAuthentication->user['admin'] !== 1) {
							throw new \Exception('User role is not admin');
						}
					}
				));
		$this->commandController->_set('userAuthentication', $mockedUserAuthentication);
		$this->commandController->_set('arguments', array());
		$this->commandController->_set('commandMethodName', 'dummyCommand');
		$this->commandController->_set('requestAdminPermissions', TRUE);
		$this->commandController->_call('callCommandMethod');

		$this->assertSame(42, $mockedUserAuthentication->user['admin']);
	}
}
