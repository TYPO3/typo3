<?php
/***************************************************************
*  Copyright notice
*
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * Fixture class with getters and setters
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Extbase_Tests_Unit_Reflection_Fixture_DummyClassWithGettersAndSetters {

	protected $property;
	protected $anotherProperty;
	protected $property2;
	protected $booleanProperty = TRUE;

	protected $protectedProperty;

	protected $unexposedProperty = 'unexposed';

	public $publicProperty;
	public $publicProperty2 = 42;

	public function setProperty($property) {
		$this->property = $property;
	}

	public function getProperty() {
		return $this->property;
	}

	public function setAnotherProperty($anotherProperty) {
		$this->anotherProperty = $anotherProperty;
	}

	public function getAnotherProperty() {
		return $this->anotherProperty;
	}

	public function getProperty2() {
		return $this->property2;
	}
	public function setProperty2($property2) {
		$this->property2 = $property2;
	}

	protected function getProtectedProperty() {
		return '42';
	}

	protected function setProtectedProperty($value) {
		$this->protectedProperty = $value;
	}

	public function isBooleanProperty() {
		return 'method called ' . $this->booleanProperty;
	}

	protected function getPrivateProperty() {
		return '21';
	}

	public function setWriteOnlyMagicProperty($value) {
	}
}


?>