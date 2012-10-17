<?php
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

require_once(__DIR__ . '/../Fixture/CLI/Command/MockCommandController.php');

/**
 * Testcase for the CLI CommandManager class
 */
class Tx_Extbase_Tests_Unit_MVC_CLI_CommandManagerTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var array
	 */
	protected $commandControllerBackup = array();

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var Tx_Extbase_MVC_CLI_CommandManager
	 */
	protected $commandManager;

	public function setUp() {
		$this->commandControllerBackup = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'];
		$this->commandManager = $this->getMock('Tx_Extbase_MVC_CLI_CommandManager', array('getAvailableCommands'));
		$this->mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
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
		$commandManager = new Tx_Extbase_MVC_CLI_CommandManager();
		$commandManager->injectObjectManager($this->mockObjectManager);
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'] = array(
			'Tx_Extbase_MVC_Fixture_CLI_Command_MockACommandController',
			'Tx_Extbase_MVC_Fixture_CLI_Command_MockBCommandController'
		);
		$mockCommand1 = $this->getMock('Tx_Extbase_MVC_CLI_Command', array(), array(), '', FALSE);
		$mockCommand2 = $this->getMock('Tx_Extbase_MVC_CLI_Command', array(), array(), '', FALSE);
		$mockCommand3 = $this->getMock('Tx_Extbase_MVC_CLI_Command', array(), array(), '', FALSE);
		$this->mockObjectManager->expects($this->at(0))->method('get')->with('Tx_Extbase_MVC_CLI_Command', 'Tx_Extbase_MVC_Fixture_CLI_Command_MockACommandController', 'foo')->will($this->returnValue($mockCommand1));
		$this->mockObjectManager->expects($this->at(1))->method('get')->with('Tx_Extbase_MVC_CLI_Command', 'Tx_Extbase_MVC_Fixture_CLI_Command_MockACommandController', 'bar')->will($this->returnValue($mockCommand2));
		$this->mockObjectManager->expects($this->at(2))->method('get')->with('Tx_Extbase_MVC_CLI_Command', 'Tx_Extbase_MVC_Fixture_CLI_Command_MockBCommandController', 'baz')->will($this->returnValue($mockCommand3));

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
		$mockCommand = $this->getMock('Tx_Extbase_MVC_CLI_Command', array(), array(), '', FALSE);
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
		$mockCommand = $this->getMock('Tx_Extbase_MVC_CLI_Command', array(), array(), '', FALSE);
		$mockCommand->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('extensionkey:controller:command'));
		$mockCommands = array($mockCommand);
		$this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

		$this->assertSame($mockCommand, $this->commandManager->getCommandByIdentifier('   ExtensionKey:conTroLler:Command  '));
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception_NoSuchCommand
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getCommandByIdentifierThrowsExceptionIfNoMatchingCommandWasFound() {
		$mockCommand = $this->getMock('Tx_Extbase_MVC_CLI_Command', array(), array(), '', FALSE);
		$mockCommand->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('extensionkey:controller:command'));
		$mockCommands = array($mockCommand);
		$this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

		$this->commandManager->getCommandByIdentifier('extensionkey:controller:someothercommand');
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception_AmbiguousCommandIdentifier
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getCommandByIdentifierThrowsExceptionIfMoreThanOneMatchingCommandWasFound() {
		$mockCommand1 = $this->getMock('Tx_Extbase_MVC_CLI_Command', array(), array(), '', FALSE);
		$mockCommand1->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('extensionkey:controller:command'));
		$mockCommand2 = $this->getMock('Tx_Extbase_MVC_CLI_Command', array(), array(), '', FALSE);
		$mockCommand2->expects($this->once())->method('getCommandIdentifier')->will($this->returnValue('otherextensionkey:controller:command'));
		$mockCommands = array($mockCommand1, $mockCommand2);
		$this->commandManager->expects($this->once())->method('getAvailableCommands')->will($this->returnValue($mockCommands));

		$this->commandManager->getCommandByIdentifier('controller:command');
	}

}
?>