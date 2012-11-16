<?php
namespace TYPO3\CMS\Core\Tests\Unit\TypoScript;

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
 * Testcase for \TYPO3\CMS\Core\TypoScript\TemplateService
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class TemplateServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * @test
	 */
	public function versionOlCallsVersionOlOfPageSelectClassWithGivenRow() {
		$row = array('foo');
		$GLOBALS['TSFE'] = new \stdClass();
		$sysPageMock = $this->getMock('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		$sysPageMock->expects($this->once())->method('versionOL')->with('sys_template', $row);
		$GLOBALS['TSFE']->sys_page = $sysPageMock;
		$instance = new \TYPO3\CMS\Core\TypoScript\TemplateService();
		$instance->versionOL($row);
	}

}

?>