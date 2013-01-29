<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Domain\Repository;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Philipp Gampe <philipp.gampe@typo3.org>
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
 * Test case
 *
 * @author Philipp Gampe <philipp.gampe@typo3.org>
 */
class UpdateScriptRepositoryTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 */
	public function findByExtensionReturnsNullIfNoUpdateScriptFileExists() {
		/** @var $fixture \TYPO3\CMS\Extensionmanager\Domain\Repository\UpdateScriptRepository|\PHPUnit_Framework_MockObject_MockObject */
		$fixture = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\UpdateScriptRepository', array('updateFileExists'));
		$fixture
			->expects($this->once())
			->method('updateFileExists')
			->with(array('siteRelPath' => 'typo3'))
			->will($this->returnValue(FALSE));
		// Invalid path with no /class.ext_update.php
		$this->assertNull($fixture->findByExtension(array('siteRelPath' => 'typo3')));
	}

	/**
	 * @test
	 */
	public function needsUpdateScriptReturnsTrueIfNoAccessMethodExists() {
		$fixture = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\UpdateScriptRepository', array('dummy'));
		eval('
			class ext_update1 {}
		');
		$updateObject = new \ext_update1;
		$this->assertTrue($fixture->_callRef('needsUpdateScript', $updateObject));
	}

	/**
	 * @test
	 */
	public function needsUpdateScriptReturnsTrueIfAccessMethodExistsAndReturnsTrue() {
		$fixture = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\UpdateScriptRepository', array('dummy'));
		eval('
			class ext_update2 { public function access() {return TRUE;}}
		');
		$updateObject = new \ext_update2;
		$this->assertTrue($fixture->_callRef('needsUpdateScript', $updateObject));
	}

	/**
	 * @test
	 */
	public function needsUpdateScriptReturnsFalseIfAccessMethodExistsAndReturnsFalse() {
		$fixture = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\UpdateScriptRepository', array('dummy'));
		eval('
			class ext_update3 { public function access() {return FALSE;}}
		');
		$updateObject = new \ext_update3;
		$this->assertFalse($fixture->_callRef('needsUpdateScript', $updateObject));
	}

}