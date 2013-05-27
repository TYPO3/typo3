<?php
namespace TYPO3\CMS\Extbase\Tests\Fixture;

/*                                                                        *
 * This script belongs to the Extbase framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
/**
 * A dummy class with setters for testing data mapping
 *
 */
class ClassWithSetters {

	/**
	 * @var mixed
	 */
	public $property1;

	/**
	 * @var mixed
	 */
	protected $property2;

	/**
	 * @var mixed
	 */
	public $property3;

	/**
	 * @var mixed
	 */
	public $property4;

	public function setProperty3($value) {
		$this->property3 = $value;
	}

	protected function setProperty4($value) {
		$this->property4 = $value;
	}

	public function getProperty2() {
		return $this->property2;
	}
}
?>