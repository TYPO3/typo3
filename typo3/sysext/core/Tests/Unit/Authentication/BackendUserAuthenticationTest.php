<?php
namespace TYPO3\CMS\Core\Tests\Unit\Authentication;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Oliver Klee (typo3-coding@oliverklee.de)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase for \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class BackendUserAuthenticationTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	private $fixture = NULL;

	public function setUp() {
		// reset hooks
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'] = array();
		$this->fixture = new \TYPO3\CMS\Core\Authentication\BackendUserAuthentication();
	}

	public function tearDown() {
		unset($this->fixture);
		\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::purgeInstances();
	}

	/////////////////////////////////////////
	// Tests concerning the form protection
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function logoffCleansFormProtection() {
		$formProtection = $this->getMock('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection', array('clean'));
		$formProtection->expects($this->atLeastOnce())->method('clean');
		\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::set('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection', $formProtection);
		$this->fixture->logoff();
	}

	/**
	 * @return array
	 */
	public function getTSConfigDataProvider() {

		$completeConfiguration = array(
			'value' => 'oneValue',
			'value.' => array('oneProperty' => 'oneValue'),
			'permissions.' => array(
				'file.' => array(
					'default.' => array('readAction' => '1'),
					'1.' => array('writeAction' => '1'),
					'0.' => array('readAction' => '0'),
				),
			)
		);

		return array(
			'single level string' => array(
				$completeConfiguration,
				'permissions',
				array(
					'value' => NULL,
					'properties' =>
					array(
						'file.' => array(
							'default.' => array('readAction' => '1'),
							'1.' => array('writeAction' => '1'),
							'0.' => array('readAction' => '0'),
						),
					),
				),
			),
			'two levels string' => array(
				$completeConfiguration,
				'permissions.file',
				array(
					'value' => NULL,
					'properties' =>
					array(
						'default.' => array('readAction' => '1'),
						'1.' => array('writeAction' => '1'),
						'0.' => array('readAction' => '0'),
					),
				),
			),
			'three levels string' => array(
				$completeConfiguration,
				'permissions.file.default',
				array(
					'value' => NULL,
					'properties' =>
					array('readAction' => '1'),
				),
			),
			'three levels string with integer property' => array(
				$completeConfiguration,
				'permissions.file.1',
				array(
					'value' => NULL,
					'properties' => array('writeAction' => '1'),
				),
			),
			'three levels string with integer zero property' => array(
				$completeConfiguration,
				'permissions.file.0',
				array(
					'value' => NULL,
					'properties' => array('readAction' => '0'),
				),
			),
			'four levels string with integer zero property, value, no properties' => array(
				$completeConfiguration,
				'permissions.file.0.readAction',
				array(
					'value' => '0',
					'properties' => NULL,
				),
			),
			'four levels string with integer property, value, no properties' => array(
				$completeConfiguration,
				'permissions.file.1.writeAction',
				array(
					'value' => '1',
					'properties' => NULL,
				),
			),
			'one level, not existant string' => array(
				$completeConfiguration,
				'foo',
				array(
					'value' => NULL,
					'properties' => NULL,
				),
			),
			'two level, not existant string' => array(
				$completeConfiguration,
				'foo.bar',
				array(
					'value' => NULL,
					'properties' => NULL,
				),
			),
			'two level, where second level does not exist' => array(
				$completeConfiguration,
				'permissions.bar',
				array(
					'value' => NULL,
					'properties' => NULL,
				),
			),
			'three level, where third level does not exist' => array(
				$completeConfiguration,
				'permissions.file.foo',
				array(
					'value' => NULL,
					'properties' => NULL,
				),
			),
			'three level, where second and third level does not exist' => array(
				$completeConfiguration,
				'permissions.foo.bar',
				array(
					'value' => NULL,
					'properties' => NULL,
				),
			),
			'value and properties' => array(
				$completeConfiguration,
				'value',
				array(
					'value' => 'oneValue',
					'properties' => array('oneProperty' => 'oneValue'),
				),
			),
		);
	}

	/**
	 * @param array $completeConfiguration
	 * @param string $objectString
	 * @param array $expectedConfiguration
	 * @dataProvider getTSConfigDataProvider
	 * @test
	 */
	public function getTSConfigReturnsCorrectArrayForGivenObjectString(array $completeConfiguration, $objectString, array $expectedConfiguration) {
		$this->fixture->userTS = $completeConfiguration;

		$actualConfiguration = $this->fixture->getTSConfig($objectString);
		$this->assertSame($expectedConfiguration, $actualConfiguration);
	}

}

?>
