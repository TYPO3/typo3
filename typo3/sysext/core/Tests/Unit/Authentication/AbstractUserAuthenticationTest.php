<?php
namespace TYPO3\CMS\Core\Tests\Unit\Authentication;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Helmut Hummel <helmut.hummel@typo3.org>
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
 * Testcase for class \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication
 *
 */
class AbstractUserAuthenticationTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getAuthInfoArrayReturnsEmptyPidListIfNoCheckPidValueIsGiven() {
		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('cleanIntList'));
		$GLOBALS['TYPO3_DB']->expects($this->never())->method('cleanIntList');

		/** @var $mock \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication */
		$mock = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\AbstractUserAuthentication', array('dummy'));
		$mock->checkPid = TRUE;
		$mock->checkPid_value = NULL;
		$result = $mock->getAuthInfoArray();
		$this->assertEquals('', $result['db_user']['checkPidList']);
	}

}
