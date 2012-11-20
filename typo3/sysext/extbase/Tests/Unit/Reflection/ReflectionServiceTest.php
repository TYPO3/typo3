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
 * Some functional tests for the backport of the reflection service
 */
class ReflectionServiceTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @param array $foo The foo parameter
	 * @return void
	 */
	public function fixtureMethodForMethodTagsValues(array $foo) {
	}

	/**
	 * @test
	 */
	public function getMethodTagsValues() {
		$service = new \TYPO3\CMS\Extbase\Reflection\ReflectionService();
		$tagsValues = $service->getMethodTagsValues('TYPO3\\CMS\\Extbase\\Tests\\Unit\\Reflection\\ReflectionServiceTest', 'fixtureMethodForMethodTagsValues');
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
		$parameters = $service->getMethodParameters('TYPO3\\CMS\\Extbase\\Tests\\Unit\\Reflection\\ReflectionServiceTest', 'fixtureMethodForMethodTagsValues');
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

	/**
	 * @test
	 */
	public function classSchemaForModelIsSetAggregateRootIfRepositoryClassIsFoundForNamespacedClasses() {
		$className = uniqid('BazFixture');
		eval ('
			namespace Foo\\Bar\\Domain\\Model;
			class ' . $className . ' extends \\TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity {}
		');
		eval ('
			namespace Foo\\Bar\\Domain\\Repository;
			class ' . $className . 'Repository {}
		');

		$service = new \TYPO3\CMS\Extbase\Reflection\ReflectionService();
		$service->injectObjectManager($this->objectManager);
		$classSchema = $service->getClassSchema('Foo\\Bar\\Domain\\Model\\' . $className);
		$this->assertTrue($classSchema->isAggregateRoot());
	}

	/**
	 * @test
	 */
	public function classSchemaForModelIsSetAggregateRootIfRepositoryClassIsFoundForNotNamespacedClasses() {
		$className = uniqid('BazFixture');
		eval ('
			class Foo_Bar_Domain_Model_' . $className . ' extends \\TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity {}
		');
		eval ('
			class Foo_Bar_Domain_Repository_' . $className . 'Repository {}
		');

		$service = new \TYPO3\CMS\Extbase\Reflection\ReflectionService();
		$service->injectObjectManager($this->objectManager);
		$classSchema = $service->getClassSchema('Foo_Bar_Domain_Model_' . $className);
		$this->assertTrue($classSchema->isAggregateRoot());
	}
}

?>