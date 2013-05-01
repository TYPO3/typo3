<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Configuration;

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
class ConfigurationManagerTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $configurationManager;

	/**
	 * Sets up this testcase
	 */
	public function setUp() {
		$this->configurationManager = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager', array('dummy'));
	}

	/**
	 * Shutdown this testcase
	 */
	public function tearDown() {
		unset($this->configurationManager);
	}

	/**
	 * Error handler for test getOldConcreteConfigurationManagerPropertyViaPublicApi.
	 *
	 * @param $errno
	 * @param $errst
	 */
	public function errorHandlerForTestGetOldConcreteConfigurationmanagerPropertyViaPublicApi($errno , $errst) {
		$this->assertSame(E_USER_ERROR, $errno);
		$this->assertStringStartsWith('Cannot access protected property', $errst);
	}

	/**
	 * @test
	 */
	public function getOldConcreteConfigurationManagerPropertyViaPublicApiWillTriggerFatalError() {
		set_error_handler(array($this, 'errorHandlerForTestGetOldConcreteConfigurationmanagerPropertyViaPublicApi'));
		$this->configurationManager->concreteConfigurationManager;
		restore_error_handler();
	}

	/**
	 * @test
	 */
	public function getConcreteConfigurationManagerViaPropertyAccessOfExtendedClassWillReturnSpecificConfigurationManager() {
		/** @var \TYPO3\CMS\Extbase\Tests\Fixture\ClassExtendingConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $configurationManagerFixture */
		$configurationManagerFixture = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Tests\\Fixture\\ClassExtendingConfigurationManager', array('dummy'));
		$configurationManagerFixture->_set('specificConfigurationManager', new \stdClass());

		$this->assertInstanceOf('stdClass', $configurationManagerFixture->getConcreteConfigurationManager());
	}

}

?>
