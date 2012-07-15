<?php
namespace TYPO3\CMS\Core\Tests\Unit\Category;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Oliver Hader <oliver.hader@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase for CategoryRegistry
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 * @author Fabien Udriot <fabien.udriot@typo3.org>
 */
class CategoryRegistryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Category\CategoryRegistry
	 */
	protected $fixture;

	/**
	 * @var array
	 */
	protected $tables;

	/**
	 * Sets up this test suite.
	 */
	protected function setUp() {
		$this->fixture = new \TYPO3\CMS\Core\Category\CategoryRegistry();
		$this->tables = array(
			'first' => uniqid('first'),
			'second' => uniqid('second')
		);
		foreach ($this->tables as $tableName) {
			$GLOBALS['TCA'][$tableName] = array('ctrl' => array(), 'columns' => array());
		}
	}

	/**
	 * Tears down this test suite.
	 */
	protected function tearDown() {
		unset($this->tables);
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function isRegistryEmptyByDefault() {
		$this->assertEquals(array(), $this->fixture->get());
	}

	/**
	 * @test
	 */
	public function doesAddReturnTrueOnDefinedTable() {
		$this->assertTrue($this->fixture->add('test_extension_a', $this->tables['first'], 'categories'));
	}

	/**
	 * @test
	 */
	public function doesAddReturnFalseOnUndefinedTable() {
		$this->fixture->add('test_extension_a', $this->tables['first'], 'categories');

		$this->assertFalse(
			$this->fixture->add('test_extension_a', $this->tables['first'], 'categories')
		);
	}

	/**
	 * @test
	 */
	public function areMultipleElementsOfSameExtensionRegistered() {
		$this->fixture->add('test_extension_a', $this->tables['first'], 'categories');
		$this->fixture->add('test_extension_b', $this->tables['second'], 'categories');
		$registry = $this->fixture->get();
		ob_flush();

		$this->assertEquals(
			'categories',
			$registry['test_extension_a'][$this->tables['first']]['fieldName']
		);

		$this->assertEquals(
			'categories',
			$registry['test_extension_b'][$this->tables['second']]['fieldName']
		);
	}

	/**
	 * @test
	 */
	public function areElementsOfDifferentExtensionsRegistered() {
		$this->fixture->add('test_extension_a', $this->tables['first'], 'categories');
		$this->fixture->add('test_extension_b', $this->tables['second'], 'categories');
		$registry = $this->fixture->get();

		$this->assertEquals(
			'categories',
			$registry['test_extension_a'][$this->tables['first']]['fieldName']
		);

		$this->assertEquals(
			'categories',
			$registry['test_extension_b'][$this->tables['second']]['fieldName']
		);
	}

	/**
	 * @test
	 */
	public function areElementsOnSameTableOverridden() {
		$this->fixture->add('test_extension_a', $this->tables['first'], $this->tables['first']);
		$this->fixture->add('test_extension_b', $this->tables['second'], $this->tables['second']);
		$registry = $this->fixture->get();

		$this->assertEquals(
			$this->tables['first'],
			$registry['test_extension_a'][$this->tables['first']]['fieldName']
		);
	}

	/**
	 * @test
	 */
	public function areDatabaseDefinitionsOfAllElementsAvailable() {
		$this->fixture->add('test_extension_a', $this->tables['first'], 'categories');
		$this->fixture->add('test_extension_b', $this->tables['second'], 'categories');
		$this->fixture->add('test_extension_c', $this->tables['first'], 'categories');
		$definitions = $this->fixture->getDatabaseTableDefinitions();
		$matches = array();
		preg_match_all('#CREATE TABLE\\s*([^ (]+)\\s*\\(\\s*([^ )]+)\\s+int\\(11\\)[^)]+\\);#mis', $definitions, $matches);
		$this->assertEquals(2, count($matches[0]));
		$this->assertEquals($matches[1][0], $this->tables['first']);
		$this->assertEquals($matches[2][0], 'categories');
		$this->assertEquals($matches[1][1], $this->tables['second']);
		$this->assertEquals($matches[2][1], 'categories');
	}

	/**
	 * @test
	 */
	public function areDatabaseDefinitionsOfParticularExtensionAvailable() {
		$this->fixture->add('test_extension_a', $this->tables['first'], 'categories');
		$this->fixture->add('test_extension_b', $this->tables['second'], 'categories');
		$definitions = $this->fixture->getDatabaseTableDefinition('test_extension_a');
		$matches = array();
		preg_match_all('#CREATE TABLE\\s*([^ (]+)\\s*\\(\\s*([^ )]+)\\s+int\\(11\\)[^)]+\\);#mis', $definitions, $matches);
		$this->assertEquals(1, count($matches[0]));
		$this->assertEquals($matches[1][0], $this->tables['first']);
		$this->assertEquals($matches[2][0], 'categories');
	}

	/**
	 * @test
	 */
	public function areDefaultCategorizedTabledLoaded() {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultCategorizedTables'] = $this->tables['first'] . ',' . $this->tables['second'];
		$this->fixture->applyTca();

		$registry = $this->fixture->get();
		$this->assertCount(2, $registry['core']);
		$this->assertSame(key($registry['core']), $this->tables['first']);
	}

	/**
	 * @test
	 */
	public function canApplyTca() {
		$this->fixture->add('test_extension_a', $this->tables['first'], 'categories');
		$this->fixture->add('test_extension_b', $this->tables['second'], 'categories');
		$this->fixture->applyTca();
		foreach ($this->tables as $table) {
			$this->assertNotEmpty($GLOBALS['TCA'][$table]['columns']['categories']);
		}
	}
}

?>