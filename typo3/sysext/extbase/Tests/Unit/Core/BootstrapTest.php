<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Core;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Testcase for the Bootstrap object
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class BootstrapTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * Sets up this testcase
	 */
	public function setUp() {
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
	}

	public function tearDown() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::purgeInstances();
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
	}

	/**
	 * @test
	 */
	public function configureObjectManagerRespectsOverridingOfAlternativeObjectRegistrationViaPluginConfiguration() {
		/** @var $objectContainer \TYPO3\CMS\Extbase\Object\Container\Container|\PHPUnit_Framework_MockObject_MockObject */
		$objectContainer = $this->getMock('TYPO3\CMS\Extbase\Object\Container\Container', array('registerImplementation'));
		$objectContainer->expects($this->once())->method('registerImplementation')->with('TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface', 'TYPO3\CMS\Extbase\Persistence\Reddis\PersistenceManager');
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\CMS\Extbase\Object\Container\Container', $objectContainer);

		$frameworkSettings['objects'] = array(
			'TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface.' => array(
				'className' => 'TYPO3\CMS\Extbase\Persistence\Reddis\PersistenceManager'
			)
		);

		/** @var $configurationManagerMock \TYPO3\CMS\Extbase\Configuration\ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\Tx_Phpunit_Interface_AccessibleObject */
		$configurationManagerMock = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager', array('getConfiguration'));
		$configurationManagerMock->expects($this->any())->method('getConfiguration')->with('Framework')->will($this->returnValue($frameworkSettings));

		/** @var $bootstrapMock \TYPO3\CMS\Extbase\Core\Bootstrap|\PHPUnit_Framework_MockObject_MockObject|\Tx_Phpunit_Interface_AccessibleObject */
		$bootstrapMock = $this->getAccessibleMock('TYPO3\CMS\Extbase\Core\Bootstrap', array('inject'));
		$bootstrapMock->_set('objectManager', $this->objectManager);
		$bootstrapMock->_set('configurationManager', $configurationManagerMock);
		$bootstrapMock->configureObjectManager();
	}

	/**
	 * @test
	 */
	public function cliRequestHandlerIsFetchedByRequestHandlerResolver() {
		/** @var $requestHandlerResolver \TYPO3\CMS\Extbase\Mvc\RequestHandlerResolver|\PHPUnit_Framework_MockObject_MockObject */
		$requestHandlerResolver = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\RequestHandlerResolver', array('resolveRequestHandler'));

		/** @var $cliRequestHandler \TYPO3\CMS\Extbase\Mvc\Cli\RequestHandler|\PHPUnit_Framework_MockObject_MockObject */
		$cliRequestHandler = $this->getMock('TYPO3\CMS\Extbase\Mvc\Cli\RequestHandler', array('canHandleRequest'));
		$cliRequestHandler->expects($this->any())->method('canHandleRequest')->will($this->returnValue(TRUE));
		$cliRequestHandler->injectObjectManager($this->objectManager);
		$cliRequestHandler->injectRequestBuilder($this->objectManager->get('TYPO3\CMS\Extbase\Mvc\Cli\RequestBuilder'));
		$cliRequestHandler->injectDispatcher($this->objectManager->get('TYPO3\CMS\Extbase\Mvc\Dispatcher'));

		/** @var $cliResponse \TYPO3\CMS\Extbase\Mvc\Cli\Response */
		$cliResponse = $this->getMock('TYPO3\CMS\Extbase\Mvc\Cli\Response', array('send'));

		/** @var $reflectionServiceMock \TYPO3\CMS\Extbase\Reflection\ReflectionService */
		$reflectionServiceMock = $this->getMock('TYPO3\\CMS\\Extbase\\Reflection\\ReflectionService', array(), array(), '', FALSE);

		/** @var $bootstrap \TYPO3\CMS\Extbase\Core\Bootstrap |\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$bootstrap = $this->getAccessibleMock('TYPO3\CMS\Extbase\Core\Bootstrap', array('isInCliMode', 'initializeReflection'));
		$bootstrap->_set('reflectionService', $reflectionServiceMock);
		$bootstrap->expects($this->once())->method('isInCliMode')->will($this->returnValue(TRUE));

		$requestHandlerResolver->expects($this->once())->method('resolveRequestHandler')->will($this->returnValue($cliRequestHandler));

		\TYPO3\CMS\Core\Utility\GeneralUtility::addInstance('TYPO3\\CMS\\Extbase\\Mvc\\RequestHandlerResolver', $requestHandlerResolver);
		\TYPO3\CMS\Core\Utility\GeneralUtility::addInstance('TYPO3\CMS\Extbase\Mvc\Cli\RequestHandler', $cliRequestHandler);
		\TYPO3\CMS\Core\Utility\GeneralUtility::addInstance('TYPO3\CMS\Extbase\Mvc\Cli\Response', $cliResponse);

		$bootstrap->run('', array());
	}
}

?>