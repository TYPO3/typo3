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

/**
 * Testcase for the CLI Request class
 */
class Tx_Extbase_Tests_Unit_MVC_CLI_RequestTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Extbase_MVC_CLI_Request
	 */
	protected $request;

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * Sets up this test case
	 */
	public function setUp() {
		$this->request = new Tx_Extbase_MVC_CLI_Request();

		$this->mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$this->request->injectObjectManager($this->mockObjectManager);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCommandReturnsTheCommandObjectReflectingTheRequestInformation() {
		$this->request->setControllerObjectName('Tx_Extbase_Command_CacheCommandController');
		$this->request->setControllerCommandName('flush');

		$this->mockObjectManager->expects($this->once())->method('get')->with('Tx_Extbase_MVC_CLI_Command', 'Tx_Extbase_Command_CacheCommandController', 'flush');
		$this->request->getCommand();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerObjectNameAndSetControllerCommandNameUnsetTheBuiltCommandObject() {
		$this->request->setControllerObjectName('Tx_Extbase_Command_CacheCommandController');
		$this->request->setControllerCommandName('flush');
		$this->request->getCommand();

		$this->request->setControllerObjectName('Tx_SomeExtension_Command_BeerCommandController');
		$this->request->setControllerCommandName('drink');

		$this->mockObjectManager->expects($this->once())->method('get')->with('Tx_Extbase_MVC_CLI_Command', 'Tx_SomeExtension_Command_BeerCommandController', 'drink');
		$this->request->getCommand();
	}
}
?>