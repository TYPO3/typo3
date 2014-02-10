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
			$GLOBALS['TCA'][$tableName] = array(
				'ctrl' => array(),
				'columns' => array(),
				'types' => array(
					'1' => array()
				),
			);
		}
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
		$this->assertFalse($this->fixture->add('test_extension_a', 'undefined_table', 'categories'));
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionCode 1369122038
	 */
	public function doesAddThrowExceptionOnEmptyTablename() {
		$this->fixture->add('test_extension_a', '', 'categories');
	}

	/**
	 * @test
	 */
	public function areMultipleElementsOfSameExtensionRegistered() {
		$this->fixture->add('test_extension_a', $this->tables['first'], 'categories');
		$this->fixture->add('test_extension_a', $this->tables['second'], 'categories');
		$this->fixture->applyTca();

		$this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['first']]['columns']);
		$this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['second']]['columns']);
	}

	/**
	 * @test
	 */
	public function areElementsOfDifferentExtensionsRegistered() {
		$this->fixture->add('test_extension_a', $this->tables['first'], 'categories');
		$this->fixture->add('test_extension_b', $this->tables['second'], 'categories');
		$this->fixture->applyTca();

		$this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['first']]['columns']);
		$this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['second']]['columns']);
	}

	/**
	 * @test
	 */
	public function areElementsOfDifferentExtensionsOnSameTableRegistered() {
		$this->fixture->add('test_extension_a', $this->tables['first'], 'categories1');
		$this->fixture->add('test_extension_b', $this->tables['first'], 'categories2');
		$this->fixture->applyTca();

		$this->assertArrayHasKey('categories1', $GLOBALS['TCA'][$this->tables['first']]['columns']);
		$this->assertArrayHasKey('categories2', $GLOBALS['TCA'][$this->tables['first']]['columns']);
	}

	/**
	 * @test
	 */
	public function areElementsOfSameExtensionOnSameTableRegistered() {
		$this->fixture->add('test_extension_a', $this->tables['first'], 'categories1');
		$this->fixture->add('test_extension_a', $this->tables['first'], 'categories2');
		$this->fixture->applyTca();

		$this->assertArrayHasKey('categories1', $GLOBALS['TCA'][$this->tables['first']]['columns']);
		$this->assertArrayHasKey('categories2', $GLOBALS['TCA'][$this->tables['first']]['columns']);
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
	public function areDefaultCategorizedTablesLoaded() {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultCategorizedTables'] = $this->tables['first'] . ',' . $this->tables['second'];
		$this->fixture->applyTca();

		$this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['first']]['columns']);
		$this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['second']]['columns']);
	}

	/**
	 * @test
	 */
	public function canApplyTca() {
		$this->fixture->add('test_extension_a', $this->tables['first'], 'categories');
		$this->fixture->add('test_extension_b', $this->tables['second'], 'categories');
		$this->fixture->applyTca();

		$this->assertNotEmpty($GLOBALS['TCA'][$this->tables['first']]['columns']['categories']);
		$this->assertNotEmpty($GLOBALS['TCA'][$this->tables['second']]['columns']['categories']);
	}

	/**
	 * @test
	 */
	public function isRegisteredReturnsTrueIfElementIsAlreadyRegistered() {
		$this->fixture->add('test_extension_a', $this->tables['first'], 'categories');
		$this->assertTrue($this->fixture->isRegistered($this->tables['first'], 'categories'));
	}

	/**
	 * @test
	 */
	public function isRegisteredReturnsFalseIfElementIsNotRegistered() {
		$this->fixture->add('test_extension_a', $this->tables['first'], 'categories');
		$this->assertFalse($this->fixture->isRegistered($this->tables['first'], '_not_registered'));
		$this->assertFalse($this->fixture->isRegistered($this->tables['second'], 'categories'));
	}

	/**
	 * @test
	 */
	public function tabIsAddedForElement() {
		$this->fixture->add('text_extension_a', $this->tables['first']);
		$this->fixture->applyTca();

		foreach($GLOBALS['TCA'][$this->tables['first']]['types'] as $typeConfig) {
			$this->assertContains('--div--;LLL:EXT:lang/locallang_tca.xlf:sys_category.tabs.category', $typeConfig['showitem']);
		}
	}

	/**
	 * @test
	 */
	public function tabIsNotAddedForElementIfFieldListIsSpecified() {
		$this->fixture->add('text_extension_a', $this->tables['first'], 'categories', array('fieldList' => 'categories'));
		$this->fixture->applyTca();

		foreach($GLOBALS['TCA'][$this->tables['first']]['types'] as $typeConfig) {
			$this->assertNotContains('--div--;LLL:EXT:lang/locallang_tca.xlf:sys_category.tabs.category', $typeConfig['showitem']);
		}
	}

	/**
	 * @test
	 */
	public function tabIsAddedOnlyOncePerTable() {
		$this->fixture->add('text_extension_a', $this->tables['first'], 'categories1');
		$this->fixture->add('text_extension_a', $this->tables['first'], 'categories2');
		$this->fixture->applyTca();

		foreach($GLOBALS['TCA'][$this->tables['first']]['types'] as $typeConfig) {
			$this->assertSame(
				1, substr_count($typeConfig['showitem'], '--div--;LLL:EXT:lang/locallang_tca.xlf:sys_category.tabs.category')
			);
		}

	}

}
