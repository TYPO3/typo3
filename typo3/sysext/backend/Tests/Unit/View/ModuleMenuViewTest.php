<?php
namespace TYPO3\CMS\Backend\Tests\Unit\View;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Fabien Udriot <fabien.udriot@typo3.org>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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