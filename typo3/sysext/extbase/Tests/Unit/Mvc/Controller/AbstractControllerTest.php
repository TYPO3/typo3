<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Stefan Neufeind <info@speedpartner.de>
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
 * Abstract Controller Test Class
 */
class AbstractControllerTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 */
	public function constructResolvesExtensionnameFromOldStyle() {
		$className = uniqid('Tx_Extbase_Tests_Fixtures_Controller');
		eval('class ' . $className . ' extends \\TYPO3\\CMS\\Extbase\\Mvc\\Controller\\AbstractController { function getExtensionName() { return $this->extensionName; } }');
		$mockController = new $className();
		$expectedResult = 'Extbase';
		$actualResult = $mockController->getExtensionName();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function constructResolvesExtensionnameFromNamespaced() {
		$className = uniqid('DummyController');
		eval('namespace ' . __NAMESPACE__ . '; class ' . $className . ' extends \\TYPO3\\CMS\\Extbase\\Mvc\\Controller\\AbstractController { function getExtensionName() { return $this->extensionName; } }');
		$classNameNamespaced = __NAMESPACE__ . '\\' . $className;
		$mockController = new $classNameNamespaced();
		$expectedResult = 'Extbase';
		$actualResult = $mockController->getExtensionName();
		$this->assertEquals($expectedResult, $actualResult);
	}
}

?>