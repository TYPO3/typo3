<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Christopher Hlubek <hlubek@networkteam.com>
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

class Tx_Extbase_Persistence_Repository_testcase extends Tx_Extbase_Base_testcase {
	
	/**
	 * @test
	 */
	public function abstractRepositoryImplementsRepositoryInterface() {
		eval('class Tx_Aggregate_Root_Class implements Tx_Extbase_DomainObject_DomainObjectInterface {
			public function _reconstituteProperty($propertyName, $value) {}
			public function _memorizeCleanState() {}
			public function _isDirty() {}
			public function _getProperties() {}
			public function _getDirtyProperties() {}
		}');
		$repository = new Tx_Extbase_Persistence_Repository('Tx_Aggregate_Root_Class');
		$this->assertTrue($repository instanceof Tx_Extbase_Persistence_RepositoryInterface);
	}

}
?>