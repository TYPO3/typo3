<?php
namespace TYPO3\CMS\Install\Tests\Unit;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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
 * Testcase for class "tx_install"
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class InstallToolTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var boolean True, if a test set a different error handler
	 */
	protected $customErrorHandlerUsed = FALSE;

	/**
	 * Restore error handler if a different one was set during tests
	 */
	public function tearDown() {
		if ($this->customErrorHandlerUsed === TRUE) {
			restore_error_handler();
		}
	}

	/**
	 * @test
	 */
	public function generateConfigFormThrowsNoWarningHandlingContentOfTypo3ConfVarsExtensionAdded() {
		$GLOBALS['TYPO3_CONF_VARS_extensionAdded'] = array();
		// The '/r' triggers a warning if the content is not properly quoted in the regex
		$GLOBALS['TYPO3_CONF_VARS_extensionAdded']['key1']['key2'] = 'FILE:EXT:rtehtmlarea/res';
		$GLOBALS['TYPO3_CONF_VARS'] = array();
		$GLOBALS['TYPO3_CONF_VARS']['key1']['key2'] = 'foo';
		set_error_handler(array($this, 'errorHandlerCallback'), E_ALL & ~(E_STRICT | E_NOTICE));
		$this->customErrorHandlerUsed = TRUE;
		require_once PATH_site . 'typo3/sysext/install/mod/class.tx_install.php';
		/** @var $instance \TYPO3\CMS\Install\Installer */
		$instance = $this->getMock('TYPO3\\CMS\\Install\\Installer', array('otherMethod'), array('otherMethod'), '', FALSE);
		$instance->generateConfigForm('get_form');
	}

	/**
	 * Set as error handler in test
	 * generateConfigFormThrowsNoWarningHandlingContentOfTypo3ConfVarsExtensionAdded
	 *
	 * @param $errorNumber
	 * @throws \Exception
	 */
	public function errorHandlerCallback($errorNumber) {
		throw new \Exception('Error thrown');
	}

}


?>