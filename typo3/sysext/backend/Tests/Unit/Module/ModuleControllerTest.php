<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Module;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog <typo3@susannemoog.de>
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
 * Test case
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class ModuleControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Backend\Module\ModuleController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $moduleController;

	protected function setUp() {
		$this->moduleController = $this->getAccessibleMock('TYPO3\\CMS\\Backend\\Module\\ModuleController', array('getLanguageService'), array(), '', FALSE);
	}

	/**
	 * @test
	 */
	public function createEntryFromRawDataGeneratesMenuEntry() {
		$entry = $this->moduleController->_call('createEntryFromRawData', array());
		$this->assertInstanceOf('TYPO3\\CMS\\Backend\\Domain\\Model\\Module\\BackendModule', $entry);
	}

	/**
	 * @test
	 */
	public function createEntryFromRawDataSetsPropertiesInEntryObject() {
		$rawModule = array(
			'name' => 'nameTest',
			'title' => 'titleTest',
			'onclick' => 'onclickTest',
			'icon' => array(
				'test' => '123'
			),
			'link' => 'linkTest',
			'description' => 'descriptionTest',
			'navigationComponentId' => 'navigationComponentIdTest'
		);

		$languageServiceMock = $this->getMock('TYPO3\\CMS\\Lang\\LanguageService', array(), array(), '', FALSE);
		$languageServiceMock->expects($this->once())->method('sL')->will($this->returnValue('titleTest'));
		$this->moduleController->expects($this->once())->method('getLanguageService')->will($this->returnValue($languageServiceMock));

		/** @var $entry \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule */
		$entry = $this->moduleController->_call('createEntryFromRawData', $rawModule);
		$this->assertEquals('nameTest', $entry->getName());
		$this->assertEquals('titleTest', $entry->getTitle());
		$this->assertEquals('linkTest', $entry->getLink());
		$this->assertEquals('onclickTest', $entry->getOnClick());
		$this->assertEquals('navigationComponentIdTest', $entry->getNavigationComponentId());
		$this->assertEquals('descriptionTest', $entry->getDescription());
		$this->assertEquals(array('test' => '123'), $entry->getIcon());
	}

	/**
	 * @test
	 */
	public function createEntryFromRawDataSetsLinkIfPathIsGivenInEntryObject() {
		$rawModule = array(
			'path' => 'pathTest'
		);
		/** @var $entry \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule */
		$entry = $this->moduleController->_call('createEntryFromRawData', $rawModule);
		$this->assertEquals('pathTest', $entry->getLink());
	}

}
