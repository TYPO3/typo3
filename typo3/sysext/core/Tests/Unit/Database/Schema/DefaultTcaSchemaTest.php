<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Schema;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use TYPO3\CMS\Core\Database\Schema\DefaultTcaSchema;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DefaultTcaSchemaTest extends UnitTestCase
{
    /**
     * @var DefaultTcaSchema
     */
    protected $subject;

    /**
     * @var Table
     */
    protected $defaultTable;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = new DefaultTcaSchema();
        $this->defaultTable = new Table('aTable');
    }

    /**
     * @test
     */
    public function enrichKeepsGivenTablesArrayWithEmptyTca()
    {
        $GLOBALS['TCA'] = [];
        self::assertEquals([$this->defaultTable], $this->subject->enrich([$this->defaultTable]));
    }

    /**
     * @test
     */
    public function enrichDoesNotAddColumnIfExists()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [];

        $table = new Table('aTable');
        $table->addColumn('uid', 'integer');
        $table->addColumn('pid', 'integer');
        $input = [];
        $input[] = $table;

        $table = new Table('aTable');
        $table->addColumn('uid', 'integer');
        $table->addColumn('pid', 'integer');
        $expected = [];
        $expected[] = $table;

        self::assertEquals($expected, $this->subject->enrich($input));
    }

    /**
     * @test
     */
    public function enrichDoesNotAddColumnIfTableExistsMultipleTimesAndUidExists()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [];

        $table = new Table('aTable');
        $table->addColumn('foo', 'integer');
        $input = [];
        $input[] = $table;
        $table = new Table('aTable');
        $table->addColumn('uid', 'integer');
        $table->addColumn('pid', 'integer');
        $input[] = $table;

        $table = new Table('aTable');
        $table->addColumn('foo', 'integer');
        $expected = [];
        $expected[] = $table;
        $table = new Table('aTable');
        $table->addColumn('uid', 'integer');
        $table->addColumn('pid', 'integer');
        $expected[] = $table;

        self::assertEquals($expected, $this->subject->enrich($input));
    }

    /**
     * @test
     */
    public function enrichAddsFieldToFirstTableDefinitionOfThatName()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [];

        $table = new Table('aTable');
        $table->addColumn('foo', 'integer');
        $input = [];
        $input[] = $table;
        $table = new Table('aTable');
        $table->addColumn('bar', 'integer');
        $input[] = $table;

        $result = $this->subject->enrich($input);

        self::assertInstanceOf(Column::class, $result[0]->getColumn('uid'));
    }

    /**
     * @test
     */
    public function enrichAddsUidAndPrimaryKey()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedUidColumn = new Column(
            '`uid`',
            Type::getType('integer'),
            [
                'notnull' => true,
                'unsigned' => true,
                'autoincrement' => true,
            ]
        );
        $expectedPrimaryKey = new Index('primary', ['uid'], true, true);
        self::assertEquals($expectedUidColumn, $result[0]->getColumn('uid'));
        self::assertEquals($expectedPrimaryKey, $result[0]->getPrimaryKey());
    }

    /**
     * @test
     */
    public function enrichAddsPid()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedPidColumn = new Column(
            '`pid`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertEquals($expectedPidColumn, $result[0]->getColumn('pid'));
    }

    /**
     * @test
     */
    public function enrichAddsSignedPidWithEnabledWorkspace()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedPidColumn = new Column(
            '`pid`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => false,
            ]
        );
        self::assertEquals($expectedPidColumn, $result[0]->getColumn('pid'));
    }

    /**
     * @test
     */
    public function enrichAddsTstamp()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'tstamp' => 'updatedon',
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`updatedon`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('updatedon'));
    }

    /**
     * @test
     */
    public function enrichAddsCrdate()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'crdate' => 'createdon',
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`createdon`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('createdon'));
    }

    /**
     * @test
     */
    public function enrichAddsCruserid()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'cruser_id' => 'createdby',
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`createdby`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('createdby'));
    }

    /**
     * @test
     */
    public function enrichAddsDeleted()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'delete' => 'deleted',
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`deleted`',
            Type::getType('smallint'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('deleted'));
    }

    /**
     * @test
     */
    public function enrichAddsDisabled()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'enablecolumns' => [
                'disabled' => 'disabled',
            ]
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`disabled`',
            Type::getType('smallint'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('disabled'));
    }

    /**
     * @test
     */
    public function enrichAddsStarttime()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'enablecolumns' => [
                'starttime' => 'starttime',
            ]
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`starttime`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('starttime'));
    }

    /**
     * @test
     */
    public function enrichAddsEndtime()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'enablecolumns' => [
                'endtime' => 'endtime',
            ]
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`endtime`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('endtime'));
    }

    /**
     * @test
     */
    public function enrichAddsFegroup()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'enablecolumns' => [
                'fe_group' => 'fe_group',
            ]
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`fe_group`',
            Type::getType('string'),
            [
                'default' => '0',
                'notnull' => true,
                'length' => 255,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('fe_group'));
    }

    /**
     * @test
     */
    public function enrichAddsSorting()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'sortby' => 'sorting',
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`sorting`',
            Type::getType('integer'),
            [
                'default' => '0',
                'notnull' => true,
                'unsigned' => false,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('sorting'));
    }

    /**
     * @test
     */
    public function enrichAddsParentKey()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedIndex = new Index('parent', ['pid']);
        self::assertEquals($expectedIndex, $result[0]->getIndex('parent'));
    }

    /**
     * @test
     */
    public function enrichAddsParentKeyWithDelete()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'delete' => 'deleted',
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedIndex = new Index('parent', ['pid', 'deleted']);
        self::assertEquals($expectedIndex, $result[0]->getIndex('parent'));
    }

    /**
     * @test
     */
    public function enrichAddsParentKeyWithDisabled()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'enablecolumns' => [
                'disabled' => 'disabled',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedIndex = new Index('parent', ['pid', 'disabled']);
        self::assertEquals($expectedIndex, $result[0]->getIndex('parent'));
    }

    /**
     * @test
     */
    public function enrichAddsParentKeyInCorrectOrder()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'delete' => 'deleted',
            'enablecolumns' => [
                'disabled' => 'disabled',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedIndex = new Index('parent', ['pid', 'deleted', 'disabled']);
        self::assertEquals($expectedIndex, $result[0]->getIndex('parent'));
    }

    /**
     * @test
     */
    public function enrichAddsSysLanguageUid()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'languageField' => 'sys_language_uid',
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`sys_language_uid`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => false,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('sys_language_uid'));
    }

    /**
     * @test
     */
    public function enrichAddsL10nParent()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'languageField' => 'sys_language_uid',
            'transOrigPointerField' => 'l10n_parent',
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`l10n_parent`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('l10n_parent'));
    }

    /**
     * @test
     */
    public function enrichDoesNotAddL10nParentIfLanguageFieldIsNotDefined()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'transOrigPointerField' => 'l10n_parent',
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $this->expectException(SchemaException::class);
        $result[0]->getColumn('l10n_parent');
    }

    /**
     * @test
     */
    public function enrichAddsDescription()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'descriptionColumn' => 'rowDescription',
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`rowDescription`',
            Type::getType('text'),
            [
                'notnull' => false,
                'length' => 65535,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('rowDescription'));
    }

    /**
     * @test
     */
    public function enrichAddsEditlock()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'editlock' => 'editlock'
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`editlock`',
            Type::getType('smallint'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('editlock'));
    }

    /**
     * @test
     */
    public function enrichAddsL10nSource()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'languageField' => 'sys_language_uid',
            'translationSource' => 'l10n_source',
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`l10n_source`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('l10n_source'));
    }

    /**
     * @test
     */
    public function enrichDoesNotAddL10nSourceIfLanguageFieldIsNotDefined()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'translationSource' => 'l10n_source',
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $this->expectException(SchemaException::class);
        $result[0]->getColumn('l10n_source');
    }

    /**
     * @test
     */
    public function enrichAddsL10nState()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'languageField' => 'sys_language_uid',
            'transOrigPointerField' => 'l10n_parent',
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`l10n_state`',
            Type::getType('text'),
            [
                'notnull' => false,
                'length' => 65535,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('l10n_state'));
    }

    /**
     * @test
     */
    public function enrichDoesNotAddL10nStateIfLanguageFieldIsNotDefined()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'transOrigPointerField' => 'l10n_parent',
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $this->expectException(SchemaException::class);
        $result[0]->getColumn('l10n_state');
    }

    /**
     * @test
     */
    public function enrichDoesNotAddL10nStateIfTransOrigPointerFieldIsNotDefined()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'languageField' => 'sys_language_uid',
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $this->expectException(SchemaException::class);
        $result[0]->getColumn('l10n_state');
    }

    /**
     * @test
     */
    public function enrichAddsT3origUid()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'origUid' => 't3_origuid',
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`t3_origuid`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('t3_origuid'));
    }

    /**
     * @test
     */
    public function enrichAddsL10nDiffsource()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'transOrigDiffSourceField' => 'l18n_diffsource',
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`l18n_diffsource`',
            Type::getType('blob'),
            [
                'length' => 16777215,
                'notnull' => false,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('l18n_diffsource'));
    }

    /**
     * @test
     */
    public function enrichAddsT3verOid()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`t3ver_oid`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('t3ver_oid'));
    }

    /**
     * @test
     */
    public function enrichAddsT3verWsid()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`t3ver_wsid`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('t3ver_wsid'));
    }

    /**
     * @test
     */
    public function enrichAddsT3verState()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`t3ver_state`',
            Type::getType('smallint'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => false,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('t3ver_state'));
    }

    /**
     * @test
     */
    public function enrichAddsT3verStage()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`t3ver_stage`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => false,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('t3ver_stage'));
    }

    /**
     * @test
     */
    public function enrichAddsT3verCount()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`t3ver_count`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('t3ver_count'));
    }

    /**
     * @test
     */
    public function enrichAddsT3verTstamp()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`t3ver_tstamp`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('t3ver_tstamp'));
    }

    /**
     * @test
     */
    public function enrichAddsT3verMoveId()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`t3ver_move_id`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertEquals($expectedColumn, $result[0]->getColumn('t3ver_move_id'));
    }

    /**
     * @test
     */
    public function enrichAddsT3verOidIndex()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedIndex = new Index('t3ver_oid', ['t3ver_oid', 't3ver_wsid']);
        self::assertEquals($expectedIndex, $result[0]->getIndex('t3ver_oid'));
    }
}
