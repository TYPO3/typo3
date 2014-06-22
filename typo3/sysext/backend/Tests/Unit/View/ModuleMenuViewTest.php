<?php
namespace TYPO3\CMS\Backend\Tests\Unit\View;

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
 * Test for TYPO3\CMS\Backend\ModuleMenuView
 */
class ModuleMenuViewTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function unsetHiddenModulesUnsetsHiddenModules() {
		/** @var \TYPO3\CMS\Backend\View\ModuleMenuView|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $moduleMenuViewMock */
		$moduleMenuViewMock = $this->getAccessibleMock(
			'TYPO3\\CMS\\Backend\\View\\ModuleMenuView',
			array('dummy'),
			array(),
			'',
			FALSE
		);

		$loadedModulesFixture = array(
			'file' => array(),
			'tools' => array(),
			'web' => array(
				'sub' => array(
					'list' => array(),
					'func' => array(),
					'info' => array(),
				),
			),
			'user' => array(
				'sub' => array(
					'task' => array(),
					'settings' => array(),
				),
			),
		);
		$moduleMenuViewMock->_set('loadedModules', $loadedModulesFixture);

		$userTsFixture = array(
			'value' => 'file,help',
			'properties' => array(
				'web' => 'list,func',
				'user' => 'task',
			),
		);

		$GLOBALS['BE_USER'] = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication', array(), array(), '', FALSE);
		$GLOBALS['BE_USER']->expects($this->any())->method('getTSConfig')->will($this->returnValue($userTsFixture));

		$expectedResult = array(
			'tools' => array(),
			'web' => array(
				'sub' => array(
					'info' => array(),
				),
			),
			'user' => array(
				'sub' => array(
					'settings' => array(),
				),
			),
		);

		$moduleMenuViewMock->_call('unsetHiddenModules');
		$actualResult = $moduleMenuViewMock->_get('loadedModules');
		$this->assertSame($expectedResult, $actualResult);
	}
}