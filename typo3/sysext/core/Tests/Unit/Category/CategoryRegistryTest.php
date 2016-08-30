<?php
namespace TYPO3\CMS\Core\Tests\Unit\Category;

/*
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
 * Testcase for CategoryRegistry
 */
class CategoryRegistryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
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
    protected function setUp()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultCategorizedTables'] = 'pages';
        $GLOBALS['TCA']['pages']['columns'] = [];
        $this->subject = new \TYPO3\CMS\Core\Category\CategoryRegistry();
        $this->tables = [
            'first' => $this->getUniqueId('first'),
            'second' => $this->getUniqueId('second')
        ];
        foreach ($this->tables as $tableName) {
            $GLOBALS['TCA'][$tableName] = [
                'ctrl' => [],
                'columns' => [],
                'types' => [
                    '0' => [
                        'showitem' => ''
                    ],
                    '1' => [
                        'showitem' => ''
                    ]
                ],
            ];
        }
    }

    /**
     * @test
     */
    public function doesAddReturnTrueOnDefinedTable()
    {
        $this->assertTrue($this->subject->add('test_extension_a', $this->tables['first'], 'categories'));
    }

    /**
     * @test
     */
    public function doesAddReturnTrueOnDefinedTableTheFirstTimeAndFalseTheSecondTime()
    {
        $this->assertTrue($this->subject->add('test_extension_a', $this->tables['first'], 'categories'));
        $this->assertFalse($this->subject->add('test_extension_a', $this->tables['first'], 'categories'));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 1369122038
     */
    public function doesAddThrowExceptionOnEmptyTablename()
    {
        $this->subject->add('test_extension_a', '', 'categories');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 1397836158
     */
    public function doesAddThrowExceptionOnEmptyExtensionKey()
    {
        $this->subject->add('', 'foo', 'categories');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 1369122038
     */
    public function doesAddThrowExceptionOnInvalidTablename()
    {
        $this->subject->add('test_extension_a', [], 'categories');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 1397836158
     */
    public function doesAddThrowExceptionOnInvalidExtensionKey()
    {
        $this->subject->add([], 'foo', 'categories');
    }

    /**
     * @test
     */
    public function areMultipleElementsOfSameExtensionRegistered()
    {
        $this->subject->add('test_extension_a', $this->tables['first'], 'categories');
        $this->subject->add('test_extension_a', $this->tables['second'], 'categories');
        $this->subject->applyTcaForPreRegisteredTables();

        $this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['first']]['columns']);
        $this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['second']]['columns']);
    }

    /**
     * @test
     */
    public function areElementsOfDifferentExtensionsRegistered()
    {
        $this->subject->add('test_extension_a', $this->tables['first'], 'categories');
        $this->subject->add('test_extension_b', $this->tables['second'], 'categories');
        $this->subject->applyTcaForPreRegisteredTables();

        $this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['first']]['columns']);
        $this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['second']]['columns']);
    }

    /**
     * @test
     */
    public function areElementsOfDifferentExtensionsOnSameTableRegistered()
    {
        $this->subject->add('test_extension_a', $this->tables['first'], 'categories1');
        $this->subject->add('test_extension_b', $this->tables['first'], 'categories2');
        $this->subject->applyTcaForPreRegisteredTables();

        $this->assertArrayHasKey('categories1', $GLOBALS['TCA'][$this->tables['first']]['columns']);
        $this->assertArrayHasKey('categories2', $GLOBALS['TCA'][$this->tables['first']]['columns']);
    }

    /**
     * @test
     */
    public function areElementsOfSameExtensionOnSameTableRegistered()
    {
        $this->subject->add('test_extension_a', $this->tables['first'], 'categories1');
        $this->subject->add('test_extension_a', $this->tables['first'], 'categories2');
        $this->subject->applyTcaForPreRegisteredTables();

        $this->assertArrayHasKey('categories1', $GLOBALS['TCA'][$this->tables['first']]['columns']);
        $this->assertArrayHasKey('categories2', $GLOBALS['TCA'][$this->tables['first']]['columns']);
    }

    /**
     * @test
     */
    public function areDatabaseDefinitionsOfAllElementsAvailable()
    {
        $this->subject->add('test_extension_a', $this->tables['first'], 'categories');
        $this->subject->add('test_extension_b', $this->tables['second'], 'categories');
        $this->subject->add('test_extension_c', $this->tables['first'], 'categories');
        $definitions = $this->subject->getDatabaseTableDefinitions();
        $matches = [];
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
    public function areDatabaseDefinitionsOfParticularExtensionAvailable()
    {
        $this->subject->add('test_extension_a', $this->tables['first'], 'categories');
        $this->subject->add('test_extension_b', $this->tables['second'], 'categories');
        $definitions = $this->subject->getDatabaseTableDefinition('test_extension_a');
        $matches = [];
        preg_match_all('#CREATE TABLE\\s*([^ (]+)\\s*\\(\\s*([^ )]+)\\s+int\\(11\\)[^)]+\\);#mis', $definitions, $matches);
        $this->assertEquals(1, count($matches[0]));
        $this->assertEquals($matches[1][0], $this->tables['first']);
        $this->assertEquals($matches[2][0], 'categories');
    }

    /**
     * @test
     */
    public function areDefaultCategorizedTablesLoaded()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultCategorizedTables'] = $this->tables['first'] . ',' . $this->tables['second'];
        $this->subject->applyTcaForPreRegisteredTables();

        $this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['first']]['columns']);
        $this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['second']]['columns']);
    }

    /**
     * @test
     */
    public function canApplyTca()
    {
        $this->subject->add('test_extension_a', $this->tables['first'], 'categories');
        $this->subject->add('test_extension_b', $this->tables['second'], 'categories');
        $this->subject->applyTcaForPreRegisteredTables();

        $this->assertNotEmpty($GLOBALS['TCA'][$this->tables['first']]['columns']['categories']);
        $this->assertNotEmpty($GLOBALS['TCA'][$this->tables['second']]['columns']['categories']);
    }

    /**
     * @test
     */
    public function isRegisteredReturnsTrueIfElementIsAlreadyRegistered()
    {
        $this->subject->add('test_extension_a', $this->tables['first'], 'categories');
        $this->assertTrue($this->subject->isRegistered($this->tables['first'], 'categories'));
    }

    /**
     * @test
     */
    public function isRegisteredReturnsFalseIfElementIsNotRegistered()
    {
        $this->subject->add('test_extension_a', $this->tables['first'], 'categories');
        $this->assertFalse($this->subject->isRegistered($this->tables['first'], '_not_registered'));
        $this->assertFalse($this->subject->isRegistered($this->tables['second'], 'categories'));
    }

    /**
     * @test
     */
    public function tabIsAddedForElement()
    {
        $this->subject->add('text_extension_a', $this->tables['first']);
        $this->subject->applyTcaForPreRegisteredTables();

        foreach ($GLOBALS['TCA'][$this->tables['first']]['types'] as $typeConfig) {
            $this->assertContains('--div--;LLL:EXT:lang/locallang_tca.xlf:sys_category.tabs.category', $typeConfig['showitem']);
        }
    }

    /**
     * @test
     */
    public function tabIsNotAddedForElementIfFieldListIsSpecified()
    {
        $this->subject->add('text_extension_a', $this->tables['first'], 'categories', ['fieldList' => 'categories']);
        $this->subject->applyTcaForPreRegisteredTables();

        foreach ($GLOBALS['TCA'][$this->tables['first']]['types'] as $typeConfig) {
            $this->assertNotContains('--div--;LLL:EXT:lang/locallang_tca.xlf:sys_category.tabs.category', $typeConfig['showitem']);
        }
    }

    /**
     * @test
     */
    public function tabIsOnlyAddedForTypesThatAreSpecifiedInTypesList()
    {
        $this->subject->add('text_extension_a', $this->tables['first'], 'categories', ['typesList' => '0']);
        $this->subject->applyTcaForPreRegisteredTables();
        $this->assertSame('', $GLOBALS['TCA'][$this->tables['first']]['types'][1]['showitem']);
    }

    /**
     * @test
     */
    public function tabIsAddedOnlyOncePerTable()
    {
        $this->subject->add('text_extension_a', $this->tables['first'], 'categories1');
        $this->subject->add('text_extension_a', $this->tables['first'], 'categories2');
        $this->subject->applyTcaForPreRegisteredTables();

        foreach ($GLOBALS['TCA'][$this->tables['first']]['types'] as $typeConfig) {
            $this->assertSame(
                1, substr_count($typeConfig['showitem'], '--div--;LLL:EXT:lang/locallang_tca.xlf:sys_category.tabs.category')
            );
        }
    }

    /**
     * @test
     */
    public function addAllowsSettingOfTheSameTableFieldTwice()
    {
        $this->subject->add('text_extension_a', $this->tables['first'], 'categories1');
        $result = $this->subject->add('text_extension_a', $this->tables['first'], 'categories1', [], true);
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function addInitializesMissingTypes()
    {
        $this->subject->add('text_extension_a', $this->tables['first'], 'categories1');
        $GLOBALS['TCA'][$this->tables['first']]['types']['newtypeafterfirstadd'] = ['showitem' => ''];
        $this->subject->add('text_extension_a', $this->tables['first'], 'categories1', [], true);
        $this->assertSame(
            1, substr_count($GLOBALS['TCA'][$this->tables['first']]['types']['newtypeafterfirstadd']['showitem'], '--div--;LLL:EXT:lang/locallang_tca.xlf:sys_category.tabs.category')
        );
    }

    /**
     * @test
     */
    public function addAddsOnlyOneSqlString()
    {
        $this->subject->add('text_extension_a', $this->tables['first'], 'categories1');
        $this->subject->add('text_extension_b', $this->tables['first'], 'categories1', [], true);
        $sqlData = $this->subject->addExtensionCategoryDatabaseSchemaToTablesDefinition([], 'text_extension_a');
        $this->assertEmpty($sqlData['sqlString'][0]);
    }
}
