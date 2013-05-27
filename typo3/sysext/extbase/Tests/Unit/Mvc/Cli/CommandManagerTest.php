<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Cli;

/*                                                                        *
 * This script belongs to the Extbase framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
/**
 * Testcase for the CLI CommandManager class
 */
class CommandManagerTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var array
	 */
	protected $commandControllerBackup = array();

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager
	 */
	protected $commandManager;

	public function setUp() {
		$this->commandControllerBackup = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'];
		$this->commandManager = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\CommandManager', array('getAvailableCommands'));
		$this->mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$this->commandManager->injectObjectManager($this->mockObjectManager);
	}

	public function tearDown() {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'] = $this->commandControllerBackup;
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getAvailableCommandsReturnsAllAvailableCommands() {
		$commandManager = new \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager();
		$commandManager->injectObjectManager($this->mockObjectManager);
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'] = array(
			'TYPO3\\CMS\\Extbase\\Tests\\Unit\\Mvc\\Cli\\Fixture\\Command\\MockACommandController',
			'TYPO3\\CMS\\Extbase\\Tests\\Unit\\Mvc\\Cli\\Fixture\\Command\\MockBCommandController'
		);
		$mockCommand1 = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Command', array(), array(), '', FALSE);
		$mockCommand2 = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Command', array(), array(), '', FALSE);
		$mockCommand3 = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Command', array(), array(), '', FALSE);
		$this->mockObjectManager->expects($this->at(0))->method('get')->with('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Command', 'TYPO3\\CMS\\Extbase\\Tests\\Unit\\Mvc\\Cli\\Fixture\\Command\\MockACommandController', 'foo')->will($this->returnValue($mockCommand1));
		$this->mockObjectManager->expects($this->at(1))->method('get')->with('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Command', 'TYPO3\\CMS\\Extbase\\Tests\\Unit\\Mvc\\Cli\\Fixture\\Command\\MockACommandController', 'bar')->will($this->returnValue($mockCommand2));
		$this->mockObjectManager->expects($this->at(2))->method('get')->with('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Command', 'TYPO3\\CMS\\Extbase\\Tests\\Unit\\Mvc\\Cli\\Fixture\\Command\\MockBCommandController', 'baz')->will($this->returnValue($mockCommand3));
		$commands = $commandManager->getAvailableCommands();
		$this->assertEquals(3, count($commands));
		$this->assertSame($mockCommand1, $commands[0]);
		$this->assertSame($mockCommand2, $commands[1]);
		$this->assertSame($mockCommand3, $commands[2]);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getCommandByIdentifierReturnsCommandIfIdentifierIsEqual() {
		$mockCommand = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Command', array(), array(), '', FALSE);
		$mockCommand->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('extensionkey:controller:command'));
		$mockCommands = array($mockCommand);
		$this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));
		$this->assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('extensionkey:controller:command'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getCommandByIdentifierWorksCaseInsensitive() {
		$mockCommand = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Command', array(), array(), '', FALSE);
		$mockCommand->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('extensionkey:controller:command'));
		$mockCommands = array($mockCommand);
		$this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));
		$this->assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('   ExtensionKey:conTroLler:Command  '));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchCommandException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getCommandByIdentifierThrowsExceptionIfNoMatchingCommandWasFound() {
		$mockCommand = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Command', array(), array(), '', FALSE);
		$mockCommand->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('extensionkey:controller:command'));
		$mockCommands = array($mockCommand);
		$this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));
		$this->commandManager->getCommandByIdentifier('extensionkey:controller:someothercommand');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception\AmbiguousCommandIdentifierException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getCommandByIdentifierThrowsExceptionIfMoreThanOneMatchingCommandWasFound() {
		$mockCommand1 = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Command', array(), array(), '', FALSE);
		$mockCommand1->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('extensionkey:controller:command'));
		$mockCommand2 = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Command', array(), array(), '', FALSE);
		$mockCommand2->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('otherextensionkey:controller:command'));
		$mockCommands = array($mockCommand1, $mockCommand2);
		$this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));
		$this->commandManager->getCommandByIdentifier('controller:command');
	}
}

?>