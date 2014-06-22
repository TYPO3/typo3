<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
