<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection;

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

/**
 * Test case
 */
class ReflectionServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @param array $foo The foo parameter
	 * @return void
	 */
	public function fixtureMethodForMethodTagsValues(array $foo) {
	}

	/**
	 * @test
	 */
	public function hasMethod() {
		$service = new \TYPO3\CMS\Extbase\Reflection\ReflectionService();
		$this->assertTrue($service->hasMethod(get_class($this), 'fixtureMethodForMethodTagsValues'));
		$this->assertFalse($service->hasMethod(get_class($this), 'notExistentMethod'));
	}

	/**
	 * @test
	 */
	public function getMethodTagsValues() {
		$service = new \TYPO3\CMS\Extbase\Reflection\ReflectionService();
		$tagsValues = $service->getMethodTagsValues(get_class($this), 'fixtureMethodForMethodTagsValues');
		$this->assertEquals(array(
			'param' => array('array $foo The foo parameter'),
			'return' => array('void')
		), $tagsValues);
	}

	/**
	 * @test
	 */
	public function getMethodParameters() {
		$service = new \TYPO3\CMS\Extbase\Reflection\ReflectionService();
		$parameters = $service->getMethodParameters(get_class($this), 'fixtureMethodForMethodTagsValues');
		$this->assertEquals(array(
			'foo' => array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => TRUE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'class' => NULL,
				'type' => 'array'
			)
		), $parameters);
	}
}
