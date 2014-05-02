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
	protected $subject;

	/**
	 * @var array
	 */
	protected $tables;

	/**
	 * Sets up this test suite.
	 */
	protected function setUp() {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultCategorizedTables'] = 'pages';
		$GLOBALS['TCA']['pages']['columns'] = array();
		$this->subject = new \TYPO3\CMS\Core\Category\CategoryRegistry();
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
		$this->assertTrue($this->subject->add('test_extension_a', $this->tables['first'], 'categories'));
	}

	/**
	 * @test
	 */
	public function doesAddReturnTrueOnDefinedTableTheFirstTimeAndFalseTheSecondTime() {
		$this->assertTrue($this->subject->add('test_extension_a', $this->tables['first'], 'categories'));
		$this->assertFalse($this->subject->add('test_extension_a', $this->tables['first'], 'categories'));
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionCode 1369122038
	 */
	public function doesAddThrowExceptionOnEmptyTablename() {
		$this->subject->add('test_extension_a', '', 'categories');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionCode 1397836158
	 */
	public function doesAddThrowExceptionOnEmptyExtensionKey() {
		$this->subject->add('', 'foo', 'categories');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionCode 1369122038
	 */
	public function doesAddThrowExceptionOnInvalidTablename() {
		$this->subject->add('test_extension_a', array(), 'categories');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionCode 1397836158
	 */
	public function doesAddThrowExceptionOnInvalidExtensionKey() {
		$this->subject->add(array(), 'foo', 'categories');
	}

	/**
	 * @test
	 */
	public function areMultipleElementsOfSameExtensionRegistered() {
		$this->subject->add('test_extension_a', $this->tables['first'], 'categories');
		$this->subject->add('test_extension_a', $this->tables['second'], 'categories');
		$this->subject->applyTcaForPreRegisteredTables();

		$this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['first']]['columns']);
		$this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['second']]['columns']);
	}

	/**
	 * @test
	 */
	public function areElementsOfDifferentExtensionsRegistered() {
		$this->subject->add('test_extension_a', $this->tables['first'], 'categories');
		$this->subject->add('test_extension_b', $this->tables['second'], 'categories');
		$this->subject->applyTcaForPreRegisteredTables();

		$this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['first']]['columns']);
		$this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['second']]['columns']);
	}

	/**
	 * @test
	 */
	public function areElementsOfDifferentExtensionsOnSameTableRegistered() {
		$this->subject->add('test_extension_a', $this->tables['first'], 'categories1');
		$this->subject->add('test_extension_b', $this->tables['first'], 'categories2');
		$this->subject->applyTcaForPreRegisteredTables();

		$this->assertArrayHasKey('categories1', $GLOBALS['TCA'][$this->tables['first']]['columns']);
		$this->assertArrayHasKey('categories2', $GLOBALS['TCA'][$this->tables['first']]['columns']);
	}

	/**
	 * @test
	 */
	public function areElementsOfSameExtensionOnSameTableRegistered() {
		$this->subject->add('test_extension_a', $this->tables['first'], 'categories1');
		$this->subject->add('test_extension_a', $this->tables['first'], 'categories2');
		$this->subject->applyTcaForPreRegisteredTables();

		$this->assertArrayHasKey('categories1', $GLOBALS['TCA'][$this->tables['first']]['columns']);
		$this->assertArrayHasKey('categories2', $GLOBALS['TCA'][$this->tables['first']]['columns']);
	}

	/**
	 * @test
	 */
	public function areDatabaseDefinitionsOfAllElementsAvailable() {
		$this->subject->add('test_extension_a', $this->tables['first'], 'categories');
		$this->subject->add('test_extension_b', $this->tables['second'], 'categories');
		$this->subject->add('test_extension_c', $this->tables['first'], 'categories');
		$definitions = $this->subject->getDatabaseTableDefinitions();
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
		$this->subject->add('test_extension_a', $this->tables['first'], 'categories');
		$this->subject->add('test_extension_b', $this->tables['second'], 'categories');
		$definitions = $this->subject->getDatabaseTableDefinition('test_extension_a');
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
		$this->subject->applyTcaForPreRegisteredTables();

		$this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['first']]['columns']);
		$this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['second']]['columns']);
	}

	/**
	 * @test
	 */
	public function canApplyTca() {
		$this->subject->add('test_extension_a', $this->tables['first'], 'categories');
		$this->subject->add('test_extension_b', $this->tables['second'], 'categories');
		$this->subject->applyTcaForPreRegisteredTables();

		$this->assertNotEmpty($GLOBALS['TCA'][$this->tables['first']]['columns']['categories']);
		$this->assertNotEmpty($GLOBALS['TCA'][$this->tables['second']]['columns']['categories']);
	}

	/**
	 * @test
	 */
	public function isRegisteredReturnsTrueIfElementIsAlreadyRegistered() {
		$this->subject->add('test_extension_a', $this->tables['first'], 'categories');
		$this->assertTrue($this->subject->isRegistered($this->tables['first'], 'categories'));
	}

	/**
	 * @test
	 */
	public function isRegisteredReturnsFalseIfElementIsNotRegistered() {
		$this->subject->add('test_extension_a', $this->tables['first'], 'categories');
		$this->assertFalse($this->subject->isRegistered($this->tables['first'], '_not_registered'));
		$this->assertFalse($this->subject->isRegistered($this->tables['second'], 'categories'));
	}

	/**
	 * @test
	 */
	public function tabIsAddedForElement() {
		$this->subject->add('text_extension_a', $this->tables['first']);
		$this->subject->applyTcaForPreRegisteredTables();

		foreach($GLOBALS['TCA'][$this->tables['first']]['types'] as $typeConfig) {
			$this->assertContains('--div--;LLL:EXT:lang/locallang_tca.xlf:sys_category.tabs.category', $typeConfig['showitem']);
		}
	}

	/**
	 * @test
	 */
	public function tabIsNotAddedForElementIfFieldListIsSpecified() {
		$this->subject->add('text_extension_a', $this->tables['first'], 'categories', array('fieldList' => 'categories'));
		$this->subject->applyTcaForPreRegisteredTables();

		foreach($GLOBALS['TCA'][$this->tables['first']]['types'] as $typeConfig) {
			$this->assertNotContains('--div--;LLL:EXT:lang/locallang_tca.xlf:sys_category.tabs.category', $typeConfig['showitem']);
		}
	}

	/**
	 * @test
	 */
	public function tabIsAddedOnlyOncePerTable() {
		$this->subject->add('text_extension_a', $this->tables['first'], 'categories1');
		$this->subject->add('text_extension_a', $this->tables['first'], 'categories2');
		$this->subject->applyTcaForPreRegisteredTables();

		foreach($GLOBALS['TCA'][$this->tables['first']]['types'] as $typeConfig) {
			$this->assertSame(
				1, substr_count($typeConfig['showitem'], '--div--;LLL:EXT:lang/locallang_tca.xlf:sys_category.tabs.category')
			);
		}

	}

}
