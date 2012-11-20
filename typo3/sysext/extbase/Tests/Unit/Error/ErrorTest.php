<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Error;

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
 * Testcase for the Error object
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ErrorTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theConstructorSetsTheErrorMessageCorrectly() {
		$errorMessage = 'The message';
		$error = new \TYPO3\CMS\Extbase\Error\Error($errorMessage, 0);
		$this->assertEquals($errorMessage, $error->getMessage());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theConstructorSetsTheErrorCodeCorrectly() {
		$errorCode = 123456789;
		$error = new \TYPO3\CMS\Extbase\Error\Error('', $errorCode);
		$this->assertEquals($errorCode, $error->getCode());
	}
}

?>