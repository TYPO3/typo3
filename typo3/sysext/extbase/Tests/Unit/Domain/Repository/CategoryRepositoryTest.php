<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2012 Oliver Klee <typo3-coding@oliverklee.de>
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
 * Testcase for the Tx_Extbase_Domain_Repository_CategoryRepository class.
 *
 * @package Extbase
 * @subpackage Domain\Repository
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class Tx_Extbase_Tests_Unit_Domain_Repository_CategoryRepositoryTest extends Tx_Extbase_Tests_Unit_BaseTestCase {
	/**
	 * @var Tx_Extbase_Domain_Repository_CategoryRepository
	 */
	private $fixture = NULL;

	protected function setUp() {
		$this->fixture = new Tx_Extbase_Domain_Repository_CategoryRepository(
			$this->getMock('Tx_Extbase_Object_ObjectManagerInterface')
		);
	}

	protected function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function initializeObjectSetsRespectStoragePidToFalse() {
		/** @var $objectManager Tx_Extbase_Object_ObjectManagerInterface */
		$objectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$fixture = new Tx_Extbase_Domain_Repository_CategoryRepository($objectManager);

		$querySettings = $this->getMock('Tx_Extbase_Persistence_Typo3QuerySettings');
		$querySettings->expects($this->once())->method('setRespectStoragePage')->with(FALSE);
		$objectManager->expects($this->once())->method('create')
			->with('Tx_Extbase_Persistence_Typo3QuerySettings')->will($this->returnValue($querySettings));

		$fixture->initializeObject();
	}
}
?>