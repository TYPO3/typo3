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
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use TYPO3\CMS\Core\Database\Schema\DefaultTcaSchema;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DefaultTcaSchemaTest extends UnitTestCase
{
    protected ?DefaultTcaSchema $subject;
    protected ?Table $defaultTable;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = new DefaultTcaSchema();
        $this->defaultTable = new Table('aTable');
    }

    /**
     * @test
     */
    public function enrichKeepsGivenTablesArrayWithEmptyTca(): void
    {
        $GLOBALS['TCA'] = [];
        self::assertEquals([$this->defaultTable], $this->subject->enrich([$this->defaultTable]));
    }

    /**
     * @test
     */
    public function enrichThrowsIfTcaTableIsNotDefinedInIncomingSet(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1696424993);
        $GLOBALS['TCA'] = [
            'aTable' => [],
        ];
        $this->subject->enrich([]);
    }

    /**
     * @test
     */
    public function enrichDoesNotAddColumnIfExists(): void
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
    public function enrichDoesNotAddColumnIfTableExistsMultipleTimesAndUidExists(): void
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
    public function enrichAddsFieldToFirstTableDefinitionOfThatName(): void
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
    public function enrichAddsUidAndPrimaryKey(): void
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
        self::assertSame($expectedUidColumn->toArray(), $result[0]->getColumn('uid')->toArray());
        self::assertEquals($expectedPrimaryKey, $result[0]->getPrimaryKey());
    }

    /**
     * @test
     */
    public function enrichAddsPid(): void
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
        self::assertSame($expectedPidColumn->toArray(), $result[0]->getColumn('pid')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsTstamp(): void
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
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('updatedon')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsCrdate(): void
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
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('createdon')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsDeleted(): void
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
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('deleted')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsDisabled(): void
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'enablecolumns' => [
                'disabled' => 'disabled',
            ],
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
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('disabled')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsStarttime(): void
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'enablecolumns' => [
                'starttime' => 'starttime',
            ],
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
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('starttime')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsEndtime(): void
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'enablecolumns' => [
                'endtime' => 'endtime',
            ],
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
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('endtime')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsFegroup(): void
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'enablecolumns' => [
                'fe_group' => 'fe_group',
            ],
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
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('fe_group')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsSorting(): void
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'sortby' => 'sorting',
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`sorting`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('sorting')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsParentKey(): void
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedIndex = new Index('parent', ['pid']);
        self::assertEquals($expectedIndex, $result[0]->getIndex('parent'));
    }

    /**
     * @test
     */
    public function enrichAddsParentKeyWithDelete(): void
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
    public function enrichAddsParentKeyWithDisabled(): void
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
    public function enrichAddsParentKeyInCorrectOrder(): void
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
    public function enrichAddsSysLanguageUid(): void
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
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('sys_language_uid')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsL10nParent(): void
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
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('l10n_parent')->toArray());
    }

    /**
     * @test
     */
    public function enrichDoesNotAddL10nParentIfLanguageFieldIsNotDefined(): void
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
    public function enrichAddsDescription(): void
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
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('rowDescription')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsEditlock(): void
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'editlock' => 'editlock',
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
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('editlock')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsL10nSource(): void
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
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('l10n_source')->toArray());
    }

    /**
     * @test
     */
    public function enrichDoesNotAddL10nSourceIfLanguageFieldIsNotDefined(): void
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
    public function enrichAddsL10nState(): void
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
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('l10n_state')->toArray());
    }

    /**
     * @test
     */
    public function enrichDoesNotAddL10nStateIfLanguageFieldIsNotDefined(): void
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
    public function enrichDoesNotAddL10nStateIfTransOrigPointerFieldIsNotDefined(): void
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
    public function enrichAddsT3origUid(): void
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
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('t3_origuid')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsL10nDiffsource(): void
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
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('l18n_diffsource')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsT3verOid(): void
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
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('t3ver_oid')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsT3verWsid(): void
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
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('t3ver_wsid')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsT3verState(): void
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
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('t3ver_state')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsT3verStage(): void
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
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('t3ver_stage')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsT3verOidIndex(): void
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedIndex = new Index('t3ver_oid', ['t3ver_oid', 't3ver_wsid']);
        self::assertEquals($expectedIndex, $result[0]->getIndex('t3ver_oid'));
    }

    /**
     * @test
     */
    public function enrichAddsSimpleMmForSelect(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['aField']['config'] = [
            'type' => 'select',
            'MM' => 'tx_myext_atable_afield_mm',
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedMmTable = new Table(
            'tx_myext_atable_afield_mm',
            [
                new Column(
                    '`uid_local`',
                    new IntegerType(),
                    [
                        'default' => 0,
                        'unsigned' => true,
                    ]
                ),
                new Column(
                    '`uid_foreign`',
                    new IntegerType(),
                    [
                        'default' => 0,
                        'unsigned' => true,
                    ]
                ),
                new Column(
                    '`sorting`',
                    new IntegerType(),
                    [
                        'default' => 0,
                        'unsigned' => true,
                    ]
                ),
                new Column(
                    '`sorting_foreign`',
                    new IntegerType(),
                    [
                        'default' => 0,
                        'unsigned' => true,
                    ]
                ),
            ],
            [
                new Index(
                    'uid_local',
                    ['uid_local']
                ),
                new Index(
                    'uid_foreign',
                    ['uid_foreign']
                ),
                new Index(
                    'primary',
                    ['uid_local', 'uid_foreign'],
                    true,
                    true
                ),
            ]
        );
        self::assertEquals($expectedMmTable, $result[1]);
    }

    /**
     * @test
     */
    public function enrichAddsMmWithUidWhenMultipleIsSet(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['aField']['config'] = [
            'type' => 'select',
            'MM' => 'tx_myext_atable_afield_mm',
            'multiple' => true,
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedMmTable = new Table(
            'tx_myext_atable_afield_mm',
            [
                new Column(
                    '`uid_local`',
                    new IntegerType(),
                    [
                        'default' => 0,
                        'unsigned' => true,
                    ]
                ),
                new Column(
                    '`uid_foreign`',
                    new IntegerType(),
                    [
                        'default' => 0,
                        'unsigned' => true,
                    ]
                ),
                new Column(
                    '`sorting`',
                    new IntegerType(),
                    [
                        'default' => 0,
                        'unsigned' => true,
                    ]
                ),
                new Column(
                    '`sorting_foreign`',
                    new IntegerType(),
                    [
                        'default' => 0,
                        'unsigned' => true,
                    ]
                ),
                new Column(
                    '`uid`',
                    new IntegerType(),
                    [
                        'default' => null,
                        'autoincrement' => true,
                        'unsigned' => true,
                    ]
                ),
            ],
            [
                new Index(
                    'uid_local',
                    ['uid_local']
                ),
                new Index(
                    'uid_foreign',
                    ['uid_foreign']
                ),
                new Index(
                    'primary',
                    ['uid'],
                    true,
                    true
                ),
            ]
        );
        self::assertEquals($expectedMmTable, $result[1]);
    }

    /**
     * @test
     */
    public function enrichAddsMmWithTablenamesAndFieldname(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['aField']['config'] = [
            'type' => 'select',
            'MM' => 'tx_myext_atable_afield_mm',
            'MM_oppositeUsage' => [
                'tt_content' => [
                    'categories',
                ],
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedMmTable = new Table(
            'tx_myext_atable_afield_mm',
            [
                new Column(
                    '`uid_local`',
                    new IntegerType(),
                    [
                        'default' => 0,
                        'unsigned' => true,
                    ]
                ),
                new Column(
                    '`uid_foreign`',
                    new IntegerType(),
                    [
                        'default' => 0,
                        'unsigned' => true,
                    ]
                ),
                new Column(
                    '`sorting`',
                    new IntegerType(),
                    [
                        'default' => 0,
                        'unsigned' => true,
                    ]
                ),
                new Column(
                    '`sorting_foreign`',
                    new IntegerType(),
                    [
                        'default' => 0,
                        'unsigned' => true,
                    ]
                ),
                new Column(
                    '`tablenames`',
                    new StringType(),
                    [
                        'default' => '',
                        'length' => 64,
                    ]
                ),
                new Column(
                    '`fieldname`',
                    new StringType(),
                    [
                        'default' => '',
                        'length' => 64,
                    ]
                ),
            ],
            [
                new Index(
                    'uid_local',
                    ['uid_local']
                ),
                new Index(
                    'uid_foreign',
                    ['uid_foreign']
                ),
                new Index(
                    'primary',
                    ['uid_local', 'uid_foreign', 'tablenames', 'fieldname'],
                    true,
                    true
                ),
            ]
        );
        self::assertEquals($expectedMmTable, $result[1]);
    }

    /**
     * @test
     */
    public function enrichAddsSlug(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['slug'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'slug',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`slug`',
            Type::getType('string'),
            [
                'default' => null,
                'notnull' => false,
                'length' => 2048,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('slug')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsFile(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['file'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'file',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`file`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('file')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsEmail(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['email'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'email',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`email`',
            Type::getType('string'),
            [
                'length' => 255,
                'default' => '',
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('email')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsNullableEmail(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['email'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'email',
                'nullable' => true,
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`email`',
            Type::getType('string'),
            [
                'length' => 255,
                'default' => null,
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('email')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsCheck(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['check'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'check',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`check`',
            Type::getType('smallint'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('check')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsFolder(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['folder'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'folder',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`folder`',
            Type::getType('text'),
            [
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('folder')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsImageManipulation(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['imageManipulation'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'imageManipulation',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`imageManipulation`',
            Type::getType('text'),
            [
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('imageManipulation')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsLanguage(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['language'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'language',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`language`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('language')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsGroup(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['group'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'group',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`group`',
            Type::getType('text'),
            [
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('group')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsGroupWithMM(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['groupWithMM'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'group',
                'MM' => 'aTable',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`groupWithMM`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('groupWithMM')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsFlex(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['flex'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'flex',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`flex`',
            Type::getType('text'),
            [
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('flex')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsText(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['text'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'text',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`text`',
            Type::getType('text'),
            [
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('text')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsPassword(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['password'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'password',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`password`',
            Type::getType('string'),
            [
                'default' => '',
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('password')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsPasswordNullable(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['password'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'password',
                'nullable' => true,
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`password`',
            Type::getType('string'),
            [
                'default' => null,
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('password')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsColor(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['color'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'color',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`color`',
            Type::getType('string'),
            [
                'length' => 7,
                'default' => '',
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('color')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsColorNullable(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['color'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'color',
                'nullable' => true,
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`color`',
            Type::getType('string'),
            [
                'length' => 7,
                'default' => null,
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('color')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsRadioString(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['radio'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'radio',
                'items' => [
                    [
                        'label' => 'Radio 1',
                        'value' => 'item1',
                    ],
                    [
                        'label' => 'Radio 2',
                        'value' => 'item2',
                    ],
                ],
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`radio`',
            Type::getType('string'),
            [
                'length' => 255,
                'default' => '',
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('radio')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsRadioStringVerifyThatCorrectLoopIsContinued(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['radio1'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'radio',
                'items' => [
                    [
                        'label' => 'Radio 1',
                        'value' => 'item1',
                    ],
                    [
                        'label' => 'Radio 2',
                        'value' => 'item2',
                    ],
                ],
            ],
        ];
        $GLOBALS['TCA']['aTable']['columns']['radio2'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'radio',
                'items' => [
                    [
                        'label' => 'Radio 1',
                        'value' => 'item1',
                    ],
                    [
                        'label' => 'Radio 2',
                        'value' => 'item2',
                    ],
                ],
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn1 = new Column(
            '`radio1`',
            Type::getType('string'),
            [
                'length' => 255,
                'default' => '',
                'notnull' => true,
            ]
        );
        $expectedColumn2 = new Column(
            '`radio2`',
            Type::getType('string'),
            [
                'length' => 255,
                'default' => '',
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn1->toArray(), $result[0]->getColumn('radio1')->toArray());
        self::assertSame($expectedColumn2->toArray(), $result[0]->getColumn('radio2')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsRadioStringWhenNoItemsOrUserFuncAreSet(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['radio'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'radio',
                'items' => [],
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`radio`',
            Type::getType('string'),
            [
                'length' => 255,
                'default' => '',
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('radio')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsRadioStringWhenItemsProcFuncSet(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['radio'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'radio',
                'itemsProcFunc' => 'Foo->bar',
                'items' => [
                    [
                        'label' => 'Radio 1',
                        'value' => '0',
                    ],
                    [
                        'label' => 'Radio 2',
                        'value' => '1',
                    ],
                ],
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`radio`',
            Type::getType('string'),
            [
                'length' => 255,
                'default' => '',
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('radio')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsRadioSmallInt(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['radio'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'radio',
                'items' => [
                    [
                        'label' => 'Radio 1',
                        'value' => '0',
                    ],
                    [
                        'label' => 'Radio 2',
                        'value' => '1',
                    ],
                    [
                        'label' => 'Radio 3',
                        'value' => '2',
                    ],
                ],
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`radio`',
            Type::getType('smallint'),
            [
                'default' => 0,
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('radio')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsRadioInt(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['radio'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'radio',
                'items' => [
                    [
                        'label' => 'Radio 1',
                        'value' => '0',
                    ],
                    [
                        'label' => 'Radio 2',
                        'value' => '1',
                    ],
                    [
                        'label' => 'Radio 3',
                        'value' => '32768',
                    ],
                ],
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`radio`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('radio')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsLink(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['link'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'link',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`link`',
            Type::getType('string'),
            [
                'length' => 2048,
                'default' => '',
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('link')->toArray());
    }
    /**
     * @test
     */
    public function enrichAddsLinkNullable(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['link'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'link',
                'nullable' => true,
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`link`',
            Type::getType('string'),
            [
                'length' => 2048,
                'default' => null,
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('link')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsInlineWithMMSet(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['inline_MM'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'bTable',
                'MM' => 'cTable',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable, new Table('bTable'), new Table('cTable')]);
        $expectedColumn = new Column(
            '`inline_MM`',
            Type::getType('integer'),
            [
                'default' => 0,
                'unsigned' => true,
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('inline_MM')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsInlineWithForeignFieldSet(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['inline_ff'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'bTable',
                'foreign_field' => 'bField',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable, new Table('bTable')]);
        $expectedColumn = new Column(
            '`inline_ff`',
            Type::getType('integer'),
            [
                'default' => 0,
                'unsigned' => true,
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('inline_ff')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsInlineWithForeignFieldAndChildRelationSet(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['inline_ff'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'inline',
                'foreign_field' => 'bField',
                'foreign_table' => 'bTable',
                'foreign_table_field' => 'cField',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable, new Table('bTable')]);
        $expectedColumn = new Column(
            '`inline_ff`',
            Type::getType('integer'),
            [
                'default' => 0,
                'unsigned' => true,
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('inline_ff')->toArray());

        $expectedChildColumnForForeignField = new Column(
            'bField',
            Type::getType('integer'),
            [
                'default' => 0,
                'unsigned' => true,
                'notnull' => true,
            ]
        );
        self::assertSame($expectedChildColumnForForeignField->toArray(), $result[1]->getColumn('bField')->toArray());

        $expectedChildColumnForForeignTableField = new Column(
            'cField',
            Type::getType('string'),
            [
                'default' => '',
                'notnull' => true,
                'length' => 255,
            ]
        );
        self::assertSame($expectedChildColumnForForeignTableField->toArray(), $result[1]->getColumn('cField')->toArray());
    }

    /**
     * @test
     */
    public function enrichAddsInlineWithoutRelationTableSet(): void
    {
        $GLOBALS['TCA']['aTable']['columns']['inline'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'bTable',
            ],
        ];
        $result = $this->subject->enrich([$this->defaultTable]);
        $expectedColumn = new Column(
            '`inline`',
            Type::getType('string'),
            [
                'default' => '',
                'notnull' => true,
                'length' => 255,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result[0]->getColumn('inline')->toArray());
    }
}
