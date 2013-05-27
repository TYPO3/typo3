<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Domain\Repository;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012 Markus Günther <mail@markus-guenther.de>
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
 * Testcase for \TYPO3\CMS\Extbase\Domain\Repository\BackendUserGroupRepository
 *
 * @author Markus Günther <mail@markus-guenther.de>
 * @api
 */
class BackendUserGroupRepositoryTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 */
	public function initializeObjectSetsRespectStoragePidToFalse() {
		$objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$fixture = new \TYPO3\CMS\Extbase\Domain\Repository\BackendUserGroupRepository($objectManager);
		$querySettings = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
		$querySettings->expects($this->once())->method('setRespectStoragePage')->with(FALSE);
		$objectManager->expects($this->once())->method('get')->with('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings')->will($this->returnValue($querySettings));
		$fixture->initializeObject();
	}

	/**
	 * @test
	 */
	public function initializeObjectSetsDefaultQuerySettings() {
		$objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		/** @var $fixture \TYPO3\CMS\Extbase\Domain\Repository\BackendUserGroupRepository */
		$fixture = $this->getMock('TYPO3\\CMS\\Extbase\\Domain\\Repository\\BackendUserGroupRepository', array('setDefaultQuerySettings'), array($objectManager));
		$querySettings = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
		$objectManager->expects($this->once())->method('get')->with('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings')->will($this->returnValue($querySettings));
		$fixture->expects($this->once())->method('setDefaultQuerySettings')->with($querySettings);
		$fixture->initializeObject();
	}
}

?>