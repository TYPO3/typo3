<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class StorageRepositoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getWhereClauseForEnabledFieldsIncludesDeletedCheckInBackend() {
		unset($GLOBALS['TSFE']);
		$storageRepositoryMock = $this->getAccessibleMock(
			'TYPO3\\CMS\\Core\\Resource\\StorageRepository',
			array('dummy'),
			array(),
			'',
			FALSE
		);
		$result = $storageRepositoryMock->_call('getWhereClauseForEnabledFields');
		$this->assertContains('sys_file_storage.deleted=0', $result);
	}

	/**
	 * @test
	 */
	public function getWhereClauseForEnabledFieldsCallsSysPageForDeletedFlagInFrontend() {
		$GLOBALS['TSFE'] = new \stdClass();
		$sysPageMock = $this->getMock('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		$GLOBALS['TSFE']->sys_page = $sysPageMock;
		$sysPageMock
			->expects($this->once())
			->method('deleteClause')
			->with('sys_file_storage');
		$storageRepositoryMock = $this->getAccessibleMock(
			'TYPO3\\CMS\\Core\\Resource\\StorageRepository',
			array('dummy'),
			array(),
			'',
			FALSE
		);
		$storageRepositoryMock->_call('getWhereClauseForEnabledFields');
	}
}

?>