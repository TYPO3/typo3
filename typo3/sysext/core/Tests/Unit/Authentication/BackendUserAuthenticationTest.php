<?php
namespace TYPO3\CMS\Core\Tests\Unit\Authentication;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Oliver Klee (typo3-coding@oliverklee.de)
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
 * Testcase for \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class BackendUserAuthenticationTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	private $fixture = NULL;

	public function setUp() {
		// reset hooks
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'] = array();
		$this->fixture = new \TYPO3\CMS\Core\Authentication\BackendUserAuthentication();
	}

	public function tearDown() {
		unset($this->fixture);
		\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::purgeInstances();
	}

	/////////////////////////////////////////
	// Tests concerning the form protection
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function logoffCleansFormProtection() {
		$formProtection = $this->getMock('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection', array('clean'));
		$formProtection->expects($this->atLeastOnce())->method('clean');
		\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::set('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection', $formProtection);
		$this->fixture->logoff();
	}

}

?>