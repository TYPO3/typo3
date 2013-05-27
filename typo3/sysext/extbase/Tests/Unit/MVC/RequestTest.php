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
 * Testcase for the MVC Generic Request
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Extbase_Tests_Unit_MVC_RequestTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aSingleArgumentCanBeSetWithSetArgumentAndRetrievedWithGetArgument() {
		$request = new Tx_Extbase_MVC_Request();
		$request->setArgument('someArgumentName', 'theValue');
		$this->assertEquals('theValue', $request->getArgument('someArgumentName'));
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception_InvalidArgumentName
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setArgumentThrowsExceptionIfTheGivenArgumentNameIsNoString() {
		$request = new Tx_Extbase_MVC_Request();
		$request->setArgument(123, 'theValue');
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception_InvalidArgumentName
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setArgumentThrowsExceptionIfTheGivenArgumentNameIsAnEmptyString() {
		$request = new Tx_Extbase_MVC_Request();
		$request->setArgument('', 'theValue');
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception_InvalidArgumentTypeException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setArgumentThrowsExceptionIfTheGivenArgumentValueIsAnObject() {
		$this->markTestSkipped('Differing behavior from FLOW3 because of backwards compatibility reasons.');
		$request = new Tx_Extbase_MVC_Request();
		$request->setArgument('theKey', new stdClass());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setArgumentsOverridesAllExistingArguments() {
		$arguments = array('key1' => 'value1', 'key2' => 'value2');
		$request = new Tx_Extbase_MVC_Request();
		$request->setArgument('someKey', 'shouldBeOverridden');
		$request->setArguments($arguments);

		$actualResult = $request->getArguments();
		$this->assertEquals($arguments, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setArgumentsCallsSetArgumentForEveryArrayEntry() {
		$request = $this->getMock('Tx_Extbase_MVC_Request', array('setArgument'));
		$request->expects($this->at(0))->method('setArgument')->with('key1', 'value1');
		$request->expects($this->at(1))->method('setArgument')->with('key2', 'value2');
		$request->setArguments(array('key1' => 'value1', 'key2' => 'value2'));
	}

	/**
	 * @test
	 */
	public function setArgumentShouldSetControllerExtensionNameIfPackageKeyIsGiven() {
		$request = $this->getMock('Tx_Extbase_MVC_Request', array('setControllerExtensionName'));
		$request->expects($this->any())->method('setControllerExtensionName')->with('MyExtension');
		$request->setArgument('@extension', 'MyExtension');
		$this->assertFalse($request->hasArgument('@extension'));
	}

	/**
	 * @test
	 */
	public function setArgumentShouldSetControllerSubpackageKeyIfSubpackageKeyIsGiven() {
		$request = $this->getMock('Tx_Extbase_MVC_Request', array('setControllerSubpackageKey'));
		$request->expects($this->any())->method('setControllerSubpackageKey')->with('MySubPackage');
		$request->setArgument('@subpackage', 'MySubPackage');
		$this->assertFalse($request->hasArgument('@subpackage'));
	}

	/**
	 * @test
	 */
	public function setArgumentShouldSetControllerNameIfControllerIsGiven() {
		$request = $this->getMock('Tx_Extbase_MVC_Request', array('setControllerName'));
		$request->expects($this->any())->method('setControllerName')->with('MyController');
		$request->setArgument('@controller', 'MyController');
		$this->assertFalse($request->hasArgument('@controller'));
	}

	/**
	 * @test
	 */
	public function setArgumentShouldSetControllerActionNameIfActionIsGiven() {
		$request = $this->getMock('Tx_Extbase_MVC_Request', array('setControllerActionName'));
		$request->expects($this->any())->method('setControllerActionName')->with('foo');
		$request->setArgument('@action', 'foo');
		$this->assertFalse($request->hasArgument('@action'));
	}

	/**
	 * @test
	 */
	public function setArgumentShouldSetFormatIfFormatIsGiven() {
		$request = $this->getMock('Tx_Extbase_MVC_Request', array('setFormat'));
		$request->expects($this->any())->method('setFormat')->with('txt');
		$request->setArgument('@format', 'txt');
		$this->assertFalse($request->hasArgument('@format'));
	}

	/**
	 * @test
	 */
	public function internalArgumentsShouldNotBeReturnedAsNormalArgument() {
		$request = new Tx_Extbase_MVC_Request();
		$request->setArgument('__referrer', 'foo');
		$this->assertFalse($request->hasArgument('__referrer'));
	}

	/**
	 * @test
	 */
	public function internalArgumentsShouldBeStoredAsInternalArguments() {
		$request = new Tx_Extbase_MVC_Request();
		$request->setArgument('__referrer', 'foo');
		$this->assertSame('foo', $request->getInternalArgument('__referrer'));
	}

	/**
	 * @test
	 */
	public function hasInternalArgumentShouldReturnNullIfArgumentNotFound() {
		$request = new Tx_Extbase_MVC_Request();
		$this->assertNull($request->getInternalArgument('__nonExistingInternalArgument'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setArgumentAcceptsObjectIfArgumentIsInternal() {
		$request = new Tx_Extbase_MVC_Request();
		$object = new stdClass();
		$request->setArgument('__theKey', $object);
		$this->assertSame($object, $request->getInternalArgument('__theKey'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function multipleArgumentsCanBeSetWithSetArgumentsAndRetrievedWithGetArguments() {
		$arguments = array(
			'firstArgument' => 'firstValue',
			'dænishÅrgument' => 'görman välju',
			'3a' => '3v'
		);
		$request = new Tx_Extbase_MVC_Request();
		$request->setArguments($arguments);
		$this->assertEquals($arguments, $request->getArguments());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasArgumentTellsIfAnArgumentExists() {
		$request = new Tx_Extbase_MVC_Request();
		$request->setArgument('existingArgument', 'theValue');

		$this->assertTrue($request->hasArgument('existingArgument'));
		$this->assertFalse($request->hasArgument('notExistingArgument'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theActionNameCanBeSetAndRetrieved() {
		$request = $this->getMock('Tx_Extbase_MVC_Request', array('getControllerObjectName'), array(), '', FALSE);
		$request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue(''));

		$request->setControllerActionName('theAction');
		$this->assertEquals('theAction', $request->getControllerActionName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theRepresentationFormatCanBeSetAndRetrieved() {
		$request = new Tx_Extbase_MVC_Request();
		$request->setFormat('html');
		$this->assertEquals('html', $request->getFormat());
	}

	/**
	 * @test
	 */
	public function theRepresentationFormatIsAutomaticallyLowercased() {
		$this->markTestSkipped('Different behavior from FLOW3 because of backwards compatibility.');
		$request = new Tx_Extbase_MVC_Request();
		$request->setFormat('hTmL');
		$this->assertEquals('html', $request->getFormat());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aFlagCanBeSetIfTheRequestNeedsToBeDispatchedAgain() {
		$request = new Tx_Extbase_MVC_Request();
		$this->assertFalse($request->isDispatched());

		$request->setDispatched(TRUE);
		$this->assertTrue($request->isDispatched());
	}
}

?>