<?php
/***************************************************************
*  Copyright notice
*  (c) 2010 Daniel Pötzinger
*  (c) 2010 Bastian Waidelich <bastian@typo3.org>
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

require_once(t3lib_extMgm::extPath('extbase') . 'Tests/Unit/Object/Container/Fixtures/Testclasses.php');

/**
 * Testcase for class t3lib_object_ClassInfoFactory.
 *
 * @author Daniel Pötzinger
 * @author Bastian Waidelich <bastian@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class Tx_Extbase_Object_Container_ClassInfoFactoryTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var t3lib_object_ClassInfoFactory
	 */
	private $classInfoFactory;

	/**
	 *
	 */
	public function setUp() {
		$this->classInfoFactory = new Tx_Extbase_Object_Container_ClassInfoFactory();
	}
}
?>