<?php
declare(strict_types = 1);
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

use TYPO3\CMS\Core\Category\CategoryRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for CategoryRegistry
 */
class CategoryRegistryTest extends UnitTestCase
{
    /**
     * @var CategoryRegistry
     */
    protected $subject;

    /**
     * @var array
     */
    protected $tables;

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultCategorizedTables'] = 'pages';
        $GLOBALS['TCA']['pages']['columns'] = [];
        $this->subject = new CategoryRegistry();
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
    public function doesAddReturnTrueOnDefinedTable(): void
    {
        $this->assertTrue($this->subject->add('test_extension_a', $this->tables['first'], 'categories'));
    }

    /**
     * @test
     */
    public function doesAddReturnTrueOnDefinedTableTheFirstTimeAndFalseTheSecondTime(): void
    {
        $this->assertTrue($this->subject->add('test_extension_a', $this->tables['first'], 'categories'));
        $this->assertFalse($this->subject->add('test_extension_a', $this->tables['first'], 'categories'));
    }

    /**
     * @test
     */
    public function doesAddThrowExceptionOnEmptyTablename(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1369122038);

        $this->subject->add('test_extension_a', '', 'categories');
    }

    /**
     * @test
     */
    public function doesAddThrowExceptionOnEmptyExtensionKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1397836158);

        $this->subject->add('', 'foo', 'categories');
    }

    /**
     * @test
     */
    public function doesAddThrowExceptionOnInvalidTablename(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1369122038);

        $this->subject->add('test_extension_a', '', 'categories');
    }

    /**
     * @test
     */
    public function doesAddThrowExceptionOnInvalidExtensionKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1397836158);

        $this->subject->add('', 'foo', 'categories');
    }

    /**
     * @test
     */
    public function areMultipleElementsOfSameExtensionRegistered(): void
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
    public function areElementsOfDifferentExtensionsRegistered(): void
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
    public function areElementsOfDifferentExtensionsOnSameTableRegistered(): void
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
    public function areElementsOfSameExtensionOnSameTableRegistered(): void
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
    public function areDatabaseDefinitionsOfAllElementsAvailable(): void
    {
        $this->subject->add('test_extension_a', $this->tables['first'], 'categories');
        $this->subject->add('test_extension_b', $this->tables['second'], 'categories');
        $this->subject->add('test_extension_c', $this->tables['first'], 'categories');
        $definitions = $this->subject->getDatabaseTableDefinitions();
        $matches = [];
        preg_match_all('#CREATE TABLE\\s*([^ (]+)\\s*\\(\\s*([^ )]+)\\s+int\\(11\\)[^)]+\\);#mis', $definitions, $matches);
        $this->assertCount(2, $matches[0]);
        $this->assertEquals($matches[1][0], $this->tables['first']);
        $this->assertEquals($matches[2][0], 'categories');
        $this->assertEquals($matches[1][1], $this->tables['second']);
        $this->assertEquals($matches[2][1], 'categories');
    }

    /**
     * @test
     */
    public function areDatabaseDefinitionsOfParticularExtensionAvailable(): void
    {
        $this->subject->add('test_extension_a', $this->tables['first'], 'categories');
        $this->subject->add('test_extension_b', $this->tables['second'], 'categories');
        $definitions = $this->subject->getDatabaseTableDefinition('test_extension_a');
        $matches = [];
        preg_match_all('#CREATE TABLE\\s*([^ (]+)\\s*\\(\\s*([^ )]+)\\s+int\\(11\\)[^)]+\\);#mis', $definitions, $matches);
        $this->assertCount(1, $matches[0]);
        $this->assertEquals($matches[1][0], $this->tables['first']);
        $this->assertEquals($matches[2][0], 'categories');
    }

    /**
     * @test
     */
    public function areDefaultCategorizedTablesLoaded(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultCategorizedTables'] = $this->tables['first'] . ',' . $this->tables['second'];
        $this->subject->applyTcaForPreRegisteredTables();

        $this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['first']]['columns']);
        $this->assertArrayHasKey('categories', $GLOBALS['TCA'][$this->tables['second']]['columns']);
    }

    /**
     * @test
     */
    public function canApplyTca(): void
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
    public function isRegisteredReturnsTrueIfElementIsAlreadyRegistered(): void
    {
        $this->subject->add('test_extension_a', $this->tables['first'], 'categories');
        $this->assertTrue($this->subject->isRegistered($this->tables['first'], 'categories'));
    }

    /**
     * @test
     */
    public function isRegisteredReturnsFalseIfElementIsNotRegistered(): void
    {
        $this->subject->add('test_extension_a', $this->tables['first'], 'categories');
        $this->assertFalse($this->subject->isRegistered($this->tables['first'], '_not_registered'));
        $this->assertFalse($this->subject->isRegistered($this->tables['second'], 'categories'));
    }

    /**
     * @test
     */
    public function tabIsAddedForElement(): void
    {
        $this->subject->add('text_extension_a', $this->tables['first']);
        $this->subject->applyTcaForPreRegisteredTables();

        foreach ($GLOBALS['TCA'][$this->tables['first']]['types'] as $typeConfig) {
            $this->assertStringContainsString('--div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_category.tabs.category', $typeConfig['showitem']);
        }
    }

    /**
     * @test
     */
    public function tabIsNotAddedForElementIfFieldListIsSpecified(): void
    {
        $this->subject->add('text_extension_a', $this->tables['first'], 'categories', ['fieldList' => 'categories']);
        $this->subject->applyTcaForPreRegisteredTables();

        foreach ($GLOBALS['TCA'][$this->tables['first']]['types'] as $typeConfig) {
            $this->assertStringNotContainsString('--div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_category.tabs.category', $typeConfig['showitem']);
        }
    }

    /**
     * @test
     */
    public function tabIsOnlyAddedForTypesThatAreSpecifiedInTypesList(): void
    {
        $this->subject->add('text_extension_a', $this->tables['first'], 'categories', ['typesList' => '0']);
        $this->subject->applyTcaForPreRegisteredTables();
        $this->assertSame('', $GLOBALS['TCA'][$this->tables['first']]['types'][1]['showitem']);
    }

    /**
     * @test
     */
    public function tabIsAddedOnlyOncePerTable(): void
    {
        $this->subject->add('text_extension_a', $this->tables['first'], 'categories1');
        $this->subject->add('text_extension_a', $this->tables['first'], 'categories2');
        $this->subject->applyTcaForPreRegisteredTables();

        foreach ($GLOBALS['TCA'][$this->tables['first']]['types'] as $typeConfig) {
            $this->assertSame(
                1,
                substr_count($typeConfig['showitem'], '--div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_category.tabs.category')
            );
        }
    }

    /**
     * @test
     */
    public function addAllowsSettingOfTheSameTableFieldTwice(): void
    {
        $this->subject->add('text_extension_a', $this->tables['first'], 'categories1');
        $result = $this->subject->add('text_extension_a', $this->tables['first'], 'categories1', [], true);
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function addInitializesMissingTypes(): void
    {
        $this->subject->add('text_extension_a', $this->tables['first'], 'categories1');
        $GLOBALS['TCA'][$this->tables['first']]['types']['newtypeafterfirstadd'] = ['showitem' => ''];
        $this->subject->add('text_extension_a', $this->tables['first'], 'categories1', [], true);
        $this->assertSame(
            1,
            substr_count($GLOBALS['TCA'][$this->tables['first']]['types']['newtypeafterfirstadd']['showitem'], '--div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_category.tabs.category')
        );
    }
}
