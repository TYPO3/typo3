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
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Platform\MariaDBPlatform;
use TYPO3\CMS\Core\Database\Platform\SQLitePlatform;
use TYPO3\CMS\Core\Database\Schema\DefaultTcaSchema;
use TYPO3\CMS\Core\Schema\FieldTypeFactory;
use TYPO3\CMS\Core\Schema\RelationMapBuilder;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DefaultTcaSchemaTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;
    protected ?DefaultTcaSchema $subject;
    protected ?Table $defaultTable;

    public function setUp(): void
    {
        parent::setUp();
        $this->defaultTable = new Table('aTable');
    }

    #[Test]
    public function enrichKeepsGivenTablesArrayWithEmptyTca(): void
    {
        $tca = [];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        self::assertEquals(['aTable' => $this->defaultTable], $subject->enrich(['aTable' => $this->defaultTable]));
    }

    #[Test]
    public function enrichThrowsIfTcaTableIsNotDefinedInIncomingSet(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1696424993);
        $tca = [
            'aTable' => [],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $subject->enrich([]);
    }

    #[Test]
    public function enrichDoesNotAddColumnIfExists(): void
    {
        $tca['aTable']['ctrl'] = [];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));

        $table = new Table('aTable');
        $table->addColumn('uid', 'integer');
        $table->addColumn('pid', 'integer');
        $input = [];
        $input['aTable'] = $table;

        $table = new Table('aTable');
        $table->addColumn('uid', 'integer');
        $table->addColumn('pid', 'integer');
        $expected = [];
        $expected['aTable'] = $table;

        self::assertEquals($expected, $subject->enrich($input));
    }

    #[Test]
    public function enrichAddsUidAndPrimaryKey(): void
    {
        $tca['aTable']['ctrl'] = [];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
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
        self::assertSame($expectedUidColumn->toArray(), $result['aTable']->getColumn('uid')->toArray());
        self::assertEquals($expectedPrimaryKey, $result['aTable']->getPrimaryKey());
    }

    #[Test]
    public function enrichAddsPid(): void
    {
        $tca['aTable']['ctrl'] = [];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedPidColumn = new Column(
            '`pid`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedPidColumn->toArray(), $result['aTable']->getColumn('pid')->toArray());
    }

    #[Test]
    public function enrichAddsTstamp(): void
    {
        $tca['aTable']['ctrl'] = [
            'tstamp' => 'updatedon',
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`updatedon`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('updatedon')->toArray());
    }

    #[Test]
    public function enrichAddsCrdate(): void
    {
        $tca['aTable']['ctrl'] = [
            'crdate' => 'createdon',
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`createdon`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('createdon')->toArray());
    }

    #[Test]
    public function enrichAddsDeleted(): void
    {
        $tca['aTable']['ctrl'] = [
            'delete' => 'deleted',
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`deleted`',
            Type::getType('smallint'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('deleted')->toArray());
    }

    #[Test]
    public function enrichAddsDisabled(): void
    {
        $tca['aTable'] = [
            'ctrl' => [
                'enablecolumns' => [
                    'disabled' => 'disabled',
                ],
            ],
            'columns' => [
                'disabled' => [
                    'label' => 'Disabled',
                    'config' => [
                        'type' => 'check',
                        'default' => 0,
                    ],
                ],
            ],
        ];
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`disabled`',
            Type::getType('smallint'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('disabled')->toArray());
    }

    #[Test]
    public function enrichAddsStarttime(): void
    {
        $tca['aTable']['ctrl'] = [
            'enablecolumns' => [
                'starttime' => 'starttime',
            ],
        ];
        $tca['aTable']['columns']['starttime'] = [
            'label' => 'Starttime',
            'config' => [
                'type' => 'datetime',
            ],
        ];
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`starttime`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('starttime')->toArray());
    }

    #[Test]
    public function enrichAddsEndtime(): void
    {
        $tca['aTable']['ctrl'] = [
            'enablecolumns' => [
                'endtime' => 'endtime',
            ],
        ];
        $tca['aTable']['columns']['endtime'] = [
            'label' => 'endtime',
            'config' => [
                'type' => 'datetime',
            ],
        ];
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`endtime`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('endtime')->toArray());
    }

    #[Test]
    public function enrichAddsFegroup(): void
    {
        $tca['aTable']['ctrl'] = [
            'enablecolumns' => [
                'fe_group' => 'fe_group',
            ],
        ];
        $tca['aTable']['columns']['fe_group'] = [
            'label' => 'fe_group',
            'config' => [
                'type' => 'select',
            ],
        ];
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`fe_group`',
            Type::getType('string'),
            [
                'default' => '0',
                'notnull' => true,
                'length' => 255,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('fe_group')->toArray());
    }

    #[Test]
    public function enrichAddsSorting(): void
    {
        $tca['aTable']['ctrl'] = [
            'sortby' => 'sorting',
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`sorting`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('sorting')->toArray());
    }

    #[Test]
    public function enrichAddsParentKey(): void
    {
        $tca['aTable']['ctrl'] = [];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedIndex = new Index('parent', ['pid']);
        self::assertEquals($expectedIndex, $result['aTable']->getIndex('parent'));
    }

    #[Test]
    public function enrichAddsParentKeyWithDelete(): void
    {
        $tca['aTable']['ctrl'] = [
            'delete' => 'deleted',
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedIndex = new Index('parent', ['pid', 'deleted']);
        self::assertEquals($expectedIndex, $result['aTable']->getIndex('parent'));
    }

    #[Test]
    public function enrichAddsParentKeyWithDisabled(): void
    {
        $tca['aTable'] = [
            'ctrl' => [
                'enablecolumns' => [
                    'disabled' => 'disabled',
                ],
            ],
            'columns' => [
                'disabled' => [
                    'label' => 'Disabled',
                    'config' => [
                        'type' => 'check',
                        'default' => 0,
                    ],
                ],
            ],
        ];
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedIndex = new Index('parent', ['pid', 'disabled']);
        self::assertEquals($expectedIndex, $result['aTable']->getIndex('parent'));
    }

    #[Test]
    public function enrichAddsParentKeyInCorrectOrder(): void
    {
        $tca['aTable'] = [
            'ctrl' => [
                'delete' => 'deleted',
                'enablecolumns' => [
                    'disabled' => 'disabled',
                ],
            ],
            'columns' => [
                'disabled' => [
                    'label' => 'Disabled',
                    'config' => [
                        'type' => 'check',
                        'default' => 0,
                    ],
                ],
            ],
        ];
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedIndex = new Index('parent', ['pid', 'deleted', 'disabled']);
        self::assertEquals($expectedIndex, $result['aTable']->getIndex('parent'));
    }

    #[Test]
    public function enrichAddsSysLanguageUid(): void
    {
        $tca['aTable'] = [
            'ctrl' => [
                'languageField' => 'sys_language_uid',
            ],
            'columns' => [
                'sys_language_uid' => [
                    'label' => 'Language',
                    'config' => [
                        'type' => 'language',
                    ],
                ],
            ],
        ];
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`sys_language_uid`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('sys_language_uid')->toArray());
    }

    #[Test]
    public function enrichAddsL10nParent(): void
    {
        $tca['aTable'] = [
            'ctrl' => [
                'languageField' => 'sys_language_uid',
                'transOrigPointerField' => 'l10n_parent',
            ],
            'columns' => [
                'sys_language_uid' => [
                    'label' => 'Language',
                    'config' => [
                        'type' => 'language',
                    ],
                ],
                'l10n_parent' => [
                    'label' => 'Language Parent',
                    'config' => [
                        'type' => 'input',
                    ],
                ],
            ],
        ];
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`l10n_parent`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('l10n_parent')->toArray());
    }

    #[Test]
    public function enrichDoesNotAddL10nParentIfLanguageFieldIsNotDefined(): void
    {
        $tca['aTable']['ctrl'] = [
            'transOrigPointerField' => 'l10n_parent',
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $this->expectException(SchemaException::class);
        $result['aTable']->getColumn('l10n_parent');
    }

    #[Test]
    public function enrichAddsDescription(): void
    {
        $tca['aTable'] = [
            'ctrl' => [
                'descriptionColumn' => 'rowDescription',
            ],
            'columns' => [
                'rowDescription' => [
                    'config' => [
                        'type' => 'input',
                    ],
                ],
            ],
        ];
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`rowDescription`',
            Type::getType('text'),
            [
                'notnull' => false,
                'length' => 65535,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('rowDescription')->toArray());
    }

    #[Test]
    public function enrichAddsEditlock(): void
    {
        $tca['aTable'] = [
            'ctrl' => [
                'editlock' => 'editlock',
            ],
            'columns' => [
                'editlock' => [
                    'config' => [
                        'type' => 'input',
                    ],
                ],
            ],
        ];
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`editlock`',
            Type::getType('smallint'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('editlock')->toArray());
    }

    #[Test]
    public function enrichAddsL10nSource(): void
    {
        $tca['aTable'] = [
            'ctrl' => [
                'languageField' => 'sys_language_uid',
                'transOrigPointerField' => 'l10n_parent',
                'translationSource' => 'l10n_source',
            ],
            'columns' => [
                'sys_language_uid' => [
                    'label' => 'Language',
                    'config' => [
                        'type' => 'language',
                    ],
                ],
                'l10n_parent' => [
                    'label' => 'Language Parent',
                    'config' => [
                        'type' => 'input',
                    ],
                ],
                'l10n_source' => [
                    'label' => 'Language Source',
                    'config' => [
                        'type' => 'input',
                    ],
                ],
            ],
        ];
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`l10n_source`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('l10n_source')->toArray());
    }

    #[Test]
    public function enrichDoesNotAddL10nSourceIfLanguageFieldIsNotDefined(): void
    {
        $tca['aTable'] = [
            'ctrl' => [
                'translationSource' => 'l10n_source',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $this->expectException(SchemaException::class);
        $result['aTable']->getColumn('l10n_source')->toArray();
    }

    #[Test]
    public function enrichAddsL10nState(): void
    {
        $tca['aTable'] = [
            'ctrl' => [
                'languageField' => 'sys_language_uid',
                'transOrigPointerField' => 'l10n_parent',
                'translationSource' => 'l10n_source',
            ],
            'columns' => [
                'sys_language_uid' => [
                    'label' => 'Language',
                    'config' => [
                        'type' => 'language',
                    ],
                ],
                'l10n_parent' => [
                    'label' => 'Language Parent',
                    'config' => [
                        'type' => 'input',
                    ],
                ],
                'l10n_source' => [
                    'label' => 'Language Source',
                    'config' => [
                        'type' => 'input',
                    ],
                ],
            ],
        ];
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`l10n_state`',
            Type::getType('text'),
            [
                'notnull' => false,
                'length' => 65535,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('l10n_state')->toArray());
    }

    #[Test]
    public function enrichDoesNotAddL10nStateIfLanguageFieldIsNotDefined(): void
    {
        $tca['aTable']['ctrl'] = [
            'transOrigPointerField' => 'l10n_parent',
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $this->expectException(SchemaException::class);
        $result['aTable']->getColumn('l10n_state');
    }

    #[Test]
    public function enrichDoesNotAddL10nStateIfTransOrigPointerFieldIsNotDefined(): void
    {
        $tca['aTable']['ctrl'] = [
            'languageField' => 'sys_language_uid',
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $this->expectException(SchemaException::class);
        $result['aTable']->getColumn('l10n_state');
    }

    #[Test]
    public function enrichAddsT3origUid(): void
    {
        $tca['aTable']['ctrl'] = [
            'origUid' => 't3_origuid',
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`t3_origuid`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('t3_origuid')->toArray());
    }

    #[Test]
    public function enrichAddsL10nDiffsource(): void
    {
        $tca['aTable']['ctrl'] = [
            'languageField' => 'sys_language_uid',
            'transOrigPointerField' => 'l10n_parent',
            'transOrigDiffSourceField' => 'l18n_diffsource',
        ];
        $tca['aTable']['columns']['sys_language_uid'] = [
            'config' => [
                'type' => 'language',
            ],
        ];
        $tca['aTable']['columns']['l10n_parent'] = [
            'config' => [
                'type' => 'text',
            ],
        ];
        $tca['aTable']['columns']['l18n_diffsource'] = [
            'label' => 'L10n Diffsource',
            'config' => [
                'type' => 'text',
                'length' => 16777215,
                'cols' => 40,
                'rows' => 15,
            ],
        ];
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`l18n_diffsource`',
            Type::getType('blob'),
            [
                'length' => 16777215,
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('l18n_diffsource')->toArray());
    }

    #[Test]
    public function enrichAddsT3verOid(): void
    {
        $tca['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`t3ver_oid`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('t3ver_oid')->toArray());
    }

    #[Test]
    public function enrichAddsT3verWsid(): void
    {
        $tca['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`t3ver_wsid`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('t3ver_wsid')->toArray());
    }

    #[Test]
    public function enrichAddsT3verState(): void
    {
        $tca['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`t3ver_state`',
            Type::getType('smallint'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('t3ver_state')->toArray());
    }

    #[Test]
    public function enrichAddsT3verStage(): void
    {
        $tca['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`t3ver_stage`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('t3ver_stage')->toArray());
    }

    #[Test]
    public function enrichAddsLifeTimerangeDatefield(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['aBigDateField'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'range' => [
                    'lower' => mktime(0, 0, 0, 10, 28, 1979),
                    'upper' => mktime(0, 0, 0, 1, 1, 2051),
                ],
            ],
        ];

        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`aBigDateField`',
            Type::getType('bigint'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => false,
            ]
        );
        self::assertEquals($expectedColumn, $result['aTable']->getColumn('aBigDateField'));
    }

    #[Test]
    public function enrichAddsShortLifeTimerangeDatefield(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['aSmallDateField'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'range' => [
                    'lower' => mktime(0, 0, 0, 10, 28, 1979),
                    'upper' => mktime(0, 0, 0, 1, 1, 2037),
                ],
            ],
        ];

        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`aSmallDateField`',
            Type::getType('bigint'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => false,
            ]
        );
        self::assertEquals($expectedColumn, $result['aTable']->getColumn('aSmallDateField'));
    }

    #[Test]
    public function enrichAddsDefaultTimerangeDatefield(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['aDefaultDateField'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
            ],
        ];

        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`aDefaultDateField`',
            Type::getType('bigint'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => false,
            ]
        );
        self::assertEquals($expectedColumn, $result['aTable']->getColumn('aDefaultDateField'));
    }

    #[Test]
    public function enrichAddsLargeFutureTimerangeDatefield(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['aBigDateField'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'range' => [
                    'lower' => mktime(0, 0, 0, 10, 28, 1979),
                    'upper' => mktime(0, 0, 0, 1, 1, 2111),
                ],
            ],
        ];

        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`aBigDateField`',
            Type::getType('bigint'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => false,
            ]
        );
        self::assertEquals($expectedColumn, $result['aTable']->getColumn('aBigDateField'));
    }

    #[Test]
    public function enrichAddsLargePastTimerangeDatefield(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['aSmallDateField'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'range' => [
                    'lower' => mktime(0, 0, 0, 10, 28, 1888),
                    'upper' => mktime(0, 0, 0, 1, 1, 2111),
                ],
            ],
        ];

        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`aSmallDateField`',
            Type::getType('bigint'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => false,
            ]
        );
        self::assertEquals($expectedColumn, $result['aTable']->getColumn('aSmallDateField'));
    }

    #[Test]
    public function enrichAddsT3verOidIndex(): void
    {
        $tca['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedIndex = new Index('t3ver_oid', ['t3ver_oid', 't3ver_wsid']);
        self::assertEquals($expectedIndex, $result['aTable']->getIndex('t3ver_oid'));
    }

    #[Test]
    public function enrichAddsSimpleMmForSelect(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['aField']['config'] = [
            'type' => 'select',
            'foreign_table' => 'bTable',
            'MM' => 'tx_myext_atable_afield_mm',
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
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
        self::assertEquals($expectedMmTable, $result['tx_myext_atable_afield_mm']);
    }

    #[Test]
    public function enrichAddsMmWithUidWhenMultipleIsSet(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['aField']['config'] = [
            'type' => 'select',
            'foreign_table' => 'aTable',
            'MM' => 'tx_myext_atable_afield_mm',
            'multiple' => true,
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
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
        self::assertEquals($expectedMmTable, $result['tx_myext_atable_afield_mm']);
    }

    #[Test]
    public function enrichAddsMmWithTablenamesAndFieldnameWithGivenOppositeUsage(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['aField']['config'] = [
            'type' => 'select',
            'foreign_table' => 'tt_content',
            'MM' => 'tx_myext_atable_afield_mm',
            'MM_oppositeUsage' => [
                'tt_content' => [
                    'categories',
                ],
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
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
        self::assertEquals($expectedMmTable, $result['tx_myext_atable_afield_mm']);
    }

    #[Test]
    public function enrichAddsMmWithTablenamesAndFieldnameWithGroupAndTwoAllowedTables(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['aField']['config'] = [
            'type' => 'group',
            'MM' => 'tx_myext_atable_afield_mm',
            'allowed' => 'be_users, be_groups',
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
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
        self::assertEquals($expectedMmTable, $result['tx_myext_atable_afield_mm']);
    }

    #[Test]
    public function enrichAddsMmWithTablenamesAndFieldnameWithGroupAndAllowedAll(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['aField']['config'] = [
            'type' => 'group',
            'MM' => 'tx_myext_atable_afield_mm',
            'allowed' => '*',
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
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
        self::assertEquals($expectedMmTable, $result['tx_myext_atable_afield_mm']);
    }

    #[Test]
    public function enrichAddsSlug(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['slug'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'slug',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`slug`',
            Type::getType('text'),
            [
                'default' => null,
                'notnull' => false,
                'length' => 65535,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('slug')->toArray());
    }

    #[Test]
    public function enrichAddsFile(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['file'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'file',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`file`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('file')->toArray());
    }

    #[Test]
    public function enrichAddsEmail(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['email'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'email',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`email`',
            Type::getType('string'),
            [
                'length' => 255,
                'default' => '',
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('email')->toArray());
    }

    #[Test]
    public function enrichAddsNullableEmail(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['email'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'email',
                'nullable' => true,
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`email`',
            Type::getType('string'),
            [
                'length' => 255,
                'default' => null,
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('email')->toArray());
    }

    #[Test]
    public function enrichAddsCheck(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['check'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'check',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`check`',
            Type::getType('smallint'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('check')->toArray());
    }

    #[Test]
    public function enrichAddsCheckWithSpecificDefault(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['check'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'check',
                'default' => 3,
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`check`',
            Type::getType('smallint'),
            [
                'default' => 3,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('check')->toArray());
    }

    #[Test]
    public function enrichAddsCheckWithDefaultAsOne(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['check'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'check',
                'default' => 1,
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`check`',
            Type::getType('smallint'),
            [
                'default' => 1,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('check')->toArray());
    }

    #[Test]
    public function enrichAddsCheckWithDefaultAsNull(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['check'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'check',
                'default' => null,
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`check`',
            Type::getType('smallint'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        // Expectation is "0" because checkboxes are defined as NOT NULL
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('check')->toArray());
    }

    #[Test]
    public function enrichAddsFolder(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['folder'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'folder',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`folder`',
            Type::getType('text'),
            [
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('folder')->toArray());
    }

    #[Test]
    public function enrichAddsImageManipulation(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['imageManipulation'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'imageManipulation',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`imageManipulation`',
            Type::getType('text'),
            [
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('imageManipulation')->toArray());
    }

    #[Test]
    public function enrichAddsLanguage(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['language'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'language',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`language`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('language')->toArray());
    }

    #[Test]
    public function enrichAddsGroup(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['group'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'group',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`group`',
            Type::getType('text'),
            [
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('group')->toArray());
    }

    #[Test]
    public function enrichAddsGroupWithMM(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['groupWithMM'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'group',
                'MM' => 'aTable',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`groupWithMM`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('groupWithMM')->toArray());
    }

    #[Test]
    public function enrichAddsFlex(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['flex'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'flex',
                'ds' => '<T3DataStructure><ROOT></ROOT></T3DataStructure>',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`flex`',
            Type::getType('text'),
            [
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('flex')->toArray());
    }

    #[Test]
    public function enrichAddsText(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['text'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'text',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`text`',
            Type::getType('text'),
            [
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('text')->toArray());
    }

    #[Test]
    public function enrichAddsPassword(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['password'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'password',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`password`',
            Type::getType('string'),
            [
                'default' => '',
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('password')->toArray());
    }

    #[Test]
    public function enrichAddsPasswordNullable(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['password'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'password',
                'nullable' => true,
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`password`',
            Type::getType('string'),
            [
                'default' => null,
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('password')->toArray());
    }

    #[Test]
    public function enrichAddsColor(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['color'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'color',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`color`',
            Type::getType('string'),
            [
                'length' => 7,
                'default' => '',
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('color')->toArray());
    }

    #[Test]
    public function enrichAddsColorNullable(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['color'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'color',
                'nullable' => true,
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`color`',
            Type::getType('string'),
            [
                'length' => 7,
                'default' => null,
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('color')->toArray());
    }

    #[Test]
    public function enrichAddsRadioString(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['radio'] = [
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
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`radio`',
            Type::getType('string'),
            [
                'length' => 255,
                'default' => '',
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('radio')->toArray());
    }

    #[Test]
    public function enrichAddsRadioStringVerifyThatCorrectLoopIsContinued(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['radio1'] = [
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
        $tca['aTable']['columns']['radio2'] = [
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
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
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
        self::assertSame($expectedColumn1->toArray(), $result['aTable']->getColumn('radio1')->toArray());
        self::assertSame($expectedColumn2->toArray(), $result['aTable']->getColumn('radio2')->toArray());
    }

    #[Test]
    public function enrichAddsRadioStringWhenNoItemsOrUserFuncAreSet(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['radio'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'radio',
                'items' => [],
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`radio`',
            Type::getType('string'),
            [
                'length' => 255,
                'default' => '',
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('radio')->toArray());
    }

    #[Test]
    public function enrichAddsRadioStringWhenItemsProcFuncSet(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['radio'] = [
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
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`radio`',
            Type::getType('string'),
            [
                'length' => 255,
                'default' => '',
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('radio')->toArray());
    }

    #[Test]
    public function enrichAddsRadioSmallInt(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['radio'] = [
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
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`radio`',
            Type::getType('smallint'),
            [
                'default' => 0,
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('radio')->toArray());
    }

    #[Test]
    public function enrichAddsRadioInt(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['radio'] = [
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
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`radio`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('radio')->toArray());
    }

    #[Test]
    public function enrichAddsLink(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['link'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'link',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`link`',
            Type::getType('text'),
            [
                'length' => 65535,
                'default' => '',
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('link')->toArray());
    }

    #[Test]
    public function enrichAddsLinkNullable(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['link'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'link',
                'nullable' => true,
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`link`',
            Type::getType('text'),
            [
                'length' => 65535,
                'default' => null,
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('link')->toArray());
    }

    #[Test]
    public function enrichAddsInput(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['input'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'input',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`input`',
            Type::getType('string'),
            [
                'length' => 255,
                'default' => '',
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('input')->toArray());
    }

    #[Test]
    public function enrichAddsInputAndConsidersMax(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['input'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'input',
                'max' => 123,
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`input`',
            Type::getType('string'),
            [
                'length' => 123,
                'default' => '',
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('input')->toArray());
    }

    #[Test]
    public function enrichAddsInputAndConsidersNullable(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['input'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'input',
                'nullable' => true,
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`input`',
            Type::getType('string'),
            [
                'length' => 255,
                'default' => null,
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('input')->toArray());
    }

    #[Test]
    public function enrichAddsInputAndUsesTextForLongColumns(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['input'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'input',
                'max' => 256,
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`input`',
            Type::getType('text'),
            [
                'length' => 65535,
                'default' => '',
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('input')->toArray());
    }

    #[Test]
    public function enrichAddsInputAndUsesTextForLongColumnsAndNullable(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['input'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'input',
                'max' => 512,
                'nullable' => true,
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`input`',
            Type::getType('text'),
            [
                'length' => 65535,
                'default' => null,
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('input')->toArray());
    }

    #[Test]
    public function enrichAddsInlineWithMMSet(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['inline_MM'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'bTable',
                'MM' => 'cTable',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable, 'bTable' => new Table('bTable'), 'cTable' => new Table('cTable')]);
        $expectedColumn = new Column(
            '`inline_MM`',
            Type::getType('integer'),
            [
                'default' => 0,
                'unsigned' => true,
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('inline_MM')->toArray());
    }

    #[Test]
    public function enrichAddsInlineWithForeignFieldSet(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['inline_ff'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'bTable',
                'foreign_field' => 'bField',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable, 'bTable' => new Table('bTable')]);
        $expectedColumn = new Column(
            '`inline_ff`',
            Type::getType('integer'),
            [
                'default' => 0,
                'unsigned' => true,
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('inline_ff')->toArray());
    }

    #[Test]
    public function enrichAddsInlineWithForeignFieldAndChildRelationSet(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['inline_ff'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'inline',
                'foreign_field' => 'bField',
                'foreign_table' => 'bTable',
                'foreign_table_field' => 'cField',
            ],
        ];
        $tca['bTable']['columns']['bField'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'passthough',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable, 'bTable' => new Table('bTable')]);
        $expectedColumn = new Column(
            '`inline_ff`',
            Type::getType('integer'),
            [
                'default' => 0,
                'unsigned' => true,
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('inline_ff')->toArray());

        $expectedChildColumnForForeignField = new Column(
            'bField',
            Type::getType('integer'),
            [
                'default' => 0,
                'unsigned' => true,
                'notnull' => true,
            ]
        );
        self::assertSame($expectedChildColumnForForeignField->toArray(), $result['bTable']->getColumn('bField')->toArray());

        $expectedChildColumnForForeignTableField = new Column(
            'cField',
            Type::getType('string'),
            [
                'default' => '',
                'notnull' => true,
                'length' => 255,
            ]
        );
        self::assertSame($expectedChildColumnForForeignTableField->toArray(), $result['bTable']->getColumn('cField')->toArray());
    }

    #[Test]
    public function enrichAddsInlineWithoutRelationTableSet(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['inline'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'bTable',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`inline`',
            Type::getType('string'),
            [
                'default' => '',
                'notnull' => true,
                'length' => 255,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('inline')->toArray());
    }

    #[Test]
    public function enrichAddsNumberAsDecimalForNonSqlite(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['number'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`number`',
            Type::getType('decimal'),
            [
                'default' => 0.00,
                'notnull' => true,
                'precision' => 10,
                'scale' => 2,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('number')->toArray());
    }

    #[Test]
    public function enrichAddsNumberAsDecimalForSqlite(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool(SQLitePlatform::class);
        $tca['aTable']['columns']['number'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`number`',
            Type::getType('string'),
            [
                'default' => '0.00',
                'notnull' => true,
                'length' => 255,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('number')->toArray());
    }

    #[Test]
    public function enrichAddsNumberAsInteger(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['number'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'number',
                'format' => 'integer',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`number`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('number')->toArray());
    }

    #[Test]
    public function enrichAddsNumberWithoutFormatAsInteger(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['number'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'number',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`number`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('number')->toArray());
    }

    #[Test]
    public function enrichAddsNumberWithoutFormatAsIntegerWithNullable(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['number'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'number',
                'nullable' => true,
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`number`',
            Type::getType('integer'),
            [
                'default' => null,
                'notnull' => false,
                'unsigned' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('number')->toArray());
    }

    #[Test]
    public function enrichAddsNumberWithoutFormatAsIntegerWithLowerRangePositive(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['number'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'number',
                'range' => [
                    'lower' => 0,
                ],
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`number`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('number')->toArray());
    }

    #[Test]
    public function enrichAddsNumberWithoutFormatAsIntegerWithLowerRangeNegative(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['number'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'number',
                'range' => [
                    'lower' => -1,
                ],
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`number`',
            Type::getType('integer'),
            [
                'default' => 0,
                'notnull' => true,
                'unsigned' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('number')->toArray());
    }

    #[Test]
    public function enrichAddsSelectText(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['select'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => [
                    [
                        'label' => 'someLabel',
                        'value' => 'someValue',
                    ],
                ],
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`select`',
            Type::getType('text'),
            [
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('select')->toArray());
    }

    #[Test]
    public function enrichAddsSelectTextWithItemProcFunc(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['select'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'select',
                'itemsProcFunc' => 'Foo->bar',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`select`',
            Type::getType('text'),
            [
                'notnull' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('select')->toArray());
    }

    #[Test]
    public function enrichAddsSelectVarcharWhenSelectSingleAndNoItems(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['select'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`select`',
            Type::getType('string'),
            [
                'notnull' => true,
                'default' => '',
                'length' => 255,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('select')->toArray());
    }

    #[Test]
    public function enrichAddsSelectTextWithoutAnyItemsOrTable(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['select'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`select`',
            Type::getType('string'),
            [
                'notnull' => false,
                'default' => '',
                'length' => 255,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('select')->toArray());
    }

    #[Test]
    public function enrichAddsSelectStringWithLengthWithoutAnyItemsOrTable(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['select'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'dbFieldLength' => 15,
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`select`',
            Type::getType('string'),
            [
                'notnull' => false,
                'default' => '',
                'length' => 15,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('select')->toArray());
    }

    #[Test]
    public function enrichAddsSelectSingleWithMMTable(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['select'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'bTable',
                'renderType' => 'selectSingle',
                'MM' => 'aTable',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`select`',
            Type::getType('integer'),
            [
                'notnull' => true,
                'default' => 0,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('select')->toArray());
    }

    #[Test]
    public function enrichAddsSelectSingleWithOnlyForeignTable(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['select'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'aTable',
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`select`',
            Type::getType('integer'),
            [
                'notnull' => true,
                'default' => 0,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('select')->toArray());
    }

    #[Test]
    public function enrichAddsSelectSingleWithForeignTableAndIntegerItems(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['select'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'aTable',
                'items' => [
                    [
                        'label' => 'someLabel',
                        'value' => 17,
                    ],
                ],
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`select`',
            Type::getType('integer'),
            [
                'notnull' => true,
                'default' => 0,
                'unsigned' => true,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('select')->toArray());
    }

    #[Test]
    public function enrichAddsSelectSingleWithForeignTableAndSignedIntegerItems(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['select'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'aTable',
                'items' => [
                    [
                        'label' => 'someLabel',
                        'value' => -17,
                    ],
                ],
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`select`',
            Type::getType('integer'),
            [
                'notnull' => true,
                'default' => 0,
                'unsigned' => false,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('select')->toArray());
    }

    #[Test]
    public function enrichAddsSelectSingleWithForeignTableAndStringItems(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['select'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'aTable',
                'items' => [
                    [
                        'label' => 'someLabel',
                        'value' => 'someValue',
                    ],
                ],
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`select`',
            Type::getType('string'),
            [
                'notnull' => true,
                'default' => '',
                'length' => 255,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('select')->toArray());
    }

    #[Test]
    public function enrichAddsSelectMultipleWithForeignTableAndIntItems(): void
    {
        $this->mockDefaultConnectionPlatformInConnectionPool();
        $tca['aTable']['columns']['select'] = [
            'label' => 'aLabel',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingleBox',
                'foreign_table' => 'aTable',
                'items' => [
                    [
                        'label' => 'someLabel',
                        'value' => '35',
                    ],
                ],
            ],
        ];
        $subject = new DefaultTcaSchema($this->getPreparedTcaSchemaFactory($tca));
        $result = $subject->enrich(['aTable' => $this->defaultTable]);
        $expectedColumn = new Column(
            '`select`',
            Type::getType('string'),
            [
                'notnull' => false,
                'default' => '',
                'length' => 255,
            ]
        );
        self::assertSame($expectedColumn->toArray(), $result['aTable']->getColumn('select')->toArray());
    }

    private function mockDefaultConnectionPlatformInConnectionPool(string $databasePlatformClass = MariaDBPlatform::class): void
    {
        $connectionPool = $this->getMockBuilder(ConnectionPool::class)->onlyMethods(['getConnectionForTable'])->getMock();
        $mariaDbConnection = $this->getMockBuilder($databasePlatformClass)->getMock();
        $connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $connection->expects(self::any())->method('getDatabasePlatform')->willReturn($mariaDbConnection);
        $connectionPool->expects(self::any())->method('getConnectionForTable')->willReturn($connection);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool);
    }

    private function getPreparedTcaSchemaFactory(array $tca): TcaSchemaFactory
    {
        $tcaSchemaFactory = new TcaSchemaFactory(
            new RelationMapBuilder($this->createMock(FlexFormTools::class)),
            new FieldTypeFactory(),
            'null',
            new NullFrontend('null')
        );
        $tcaSchemaFactory->rebuild($tca);
        return $tcaSchemaFactory;
    }
}
