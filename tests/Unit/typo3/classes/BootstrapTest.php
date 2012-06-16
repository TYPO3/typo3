<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Oliver Hader <oliver.hader@typo3.org>
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

require_once PATH_typo3 . 'classes/Bootstrap/Abstract.php';
require_once PATH_typo3 . 'classes/Bootstrap/Backend.php';
require_once PATH_typo3 . 'classes/Bootstrap/Cli.php';
require_once PATH_typo3 . 'classes/Bootstrap/Install.php';

/**
 * @author Oliver Hader <oliver.hader@typo3.org>
 * @package TYPO3
 * @subpackage core
 */
class Typo3_Bootstrap_BackendTest extends Tx_PhpUnit_TestCase {
	/**
	 * @test
	 */
	public function areDifferentBootstrapObjectsCorrect() {
		$backend = Typo3_Bootstrap_Backend::getInstance();
		$cli = Typo3_Bootstrap_Cli::getInstance();
		$install = Typo3_Bootstrap_Install::getInstance();

		$this->assertInstanceOf('Typo3_Bootstrap_Backend', $backend);
		$this->assertInstanceOf('Typo3_Bootstrap_Cli', $cli);
		$this->assertInstanceOf('Typo3_Bootstrap_Install', $install);
	}
}
?>