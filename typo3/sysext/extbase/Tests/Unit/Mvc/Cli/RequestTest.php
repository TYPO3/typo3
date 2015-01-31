<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Cli;

/*
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
class RequestTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Cli\Request|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $request;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * Sets up this test case
	 */
	public function setUp() {
		$this->request = $this->getAccessibleMock('TYPO3\CMS\Extbase\Mvc\Cli\Request', array('dummy'));
		$this->mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$this->request->_set('objectManager', $this->mockObjectManager);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCommandReturnsTheCommandObjectReflectingTheRequestInformation() {
		$this->request->setControllerObjectName('Tx_Extbase_Command_CacheCommandController');
		$this->request->setControllerCommandName('flush');
		$this->mockObjectManager->expects($this->once())->method('get')->with('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Command', 'Tx_Extbase_Command_CacheCommandController', 'flush');
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
		$this->mockObjectManager->expects($this->once())->method('get')->with('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Command', 'Tx_SomeExtension_Command_BeerCommandController', 'drink');
		$this->request->getCommand();
	}

	/**
	 * @test
	 */
	public function setControllerObjectNameProperlyResolvesExtensionNameWithNamespaces() {
		$mockCliRequest = new \TYPO3\CMS\Extbase\Mvc\Cli\Request;
		$mockCliRequest->setControllerObjectName('TYPO3\CMS\Extbase\Command\NamespacedMockCommandController');

		$this->assertSame('Extbase', $mockCliRequest->getControllerExtensionName());
	}

	/**
	 * @test
	 */
	public function setControllerObjectNameProperlyResolvesExtensionNameWithoutNamespaces() {
		$mockCliRequest = new \TYPO3\CMS\Extbase\Mvc\Cli\Request;
		$mockCliRequest->setControllerObjectName('Tx_Extbase_Command_OldschoolMockCommandController');

		$this->assertSame('Extbase', $mockCliRequest->getControllerExtensionName());
	}
}
