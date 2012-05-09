<?php
/***************************************************************
* Copyright notice
*
* (c) 2012 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Testcase for the t3lib_tstemplate class in the TYPO3 core.
 *
 * @package TYPO3
 * @subpackage t3lib
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class t3lib_tstemplateTest extends tx_phpunit_testcase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Exclude TYPO3_DB from backup/ restore of $GLOBALS
	 * because resource types cannot be handled during serializing
	 *
	 * @var array
	 */
	protected $backupGlobalsBlacklist = array('TYPO3_DB');

	/**
	 * @test
	 */
	public function versionOlCallsVersionOlOfPageSelectClassWithGivenRow() {
		$row = array('foo');
		$GLOBALS['TSFE'] = new stdClass();
		$sysPageMock = $this->getMock('t3lib_pageSelect');
		$sysPageMock->expects($this->once())->method('versionOL')->with('sys_template', $row);
		$GLOBALS['TSFE']->sys_page = $sysPageMock;

		$instance = new t3lib_TStemplate();
		$instance->versionOL($row);
	}
}
?>