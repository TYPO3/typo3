<?php
namespace TYPO3\CMS\Install\Tests\Unit\FolderStructure;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 */
class StructureFacadeTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getStatusReturnsStatusOfStructureAndReturnsItsResult() {
		/** @var $facade \TYPO3\CMS\Install\FolderStructure\StructureFacade|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$facade = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\StructureFacade', array('dummy'), array(), '', FALSE);
		$root = $this->getMock('TYPO3\\CMS\\Install\\FolderStructure\\RootNode', array(), array(), '', FALSE);
		$root->expects($this->once())->method('getStatus')->will($this->returnValue(array()));
		$facade->_set('structure', $root);
		$status = $facade->getStatus();
		$this->assertInternalType('array', $status);
	}

	/**
	 * @test
	 */
	public function fixCallsFixOfStructureAndReturnsItsResult() {
		/** @var $facade \TYPO3\CMS\Install\FolderStructure\StructureFacade|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$facade = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\StructureFacade', array('dummy'), array(), '', FALSE);
		$root = $this->getMock('TYPO3\\CMS\\Install\\FolderStructure\\RootNode', array(), array(), '', FALSE);
		$root->expects($this->once())->method('fix')->will($this->returnValue(array()));
		$facade->_set('structure', $root);
		$status = $facade->fix();
		$this->assertInternalType('array', $status);
	}
}
