<?php
declare (strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Database\Query;

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

use Prophecy\Argument;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryContext;
use TYPO3\CMS\Core\Database\Query\QueryRestrictionBuilder;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class QueryRestrictionBuilderTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $defaultTableConfig = [
        'versioningWS' => true,
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
    ];

    /**
     * @var \TYPO3\CMS\Frontend\Page\PageRepository
     */
    protected $pageRepository;

    /**
     * @var \TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
     */
    protected $expressionBuilder;

    /**
     * @var Connection|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $connection;

    /**
     * @var QueryContext
     */
    protected $queryContext;

    /**
     * Create a new database connection mock object for every test.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->connection = $this->prophesize(Connection::class);
        $this->connection->quoteIdentifier(Argument::cetera())->will(function ($args) {
            return '"' . join('"."', explode('.', $args[0])) . '"';
        });
        $this->connection->quote(Argument::cetera())->will(function ($args) {
            return "'" . $args[0] . "'";
        });
        $this->connection->getDatabasePlatform()->willReturn(new MockPlatform());

        $this->queryContext = GeneralUtility::makeInstance(QueryContext::class);
        $this->expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this->connection->reveal());
    }

    /**
     * @test
     */
    public function getVisibilityConstraintsReturnsEmptyConstraintForNoneContext()
    {
        $this->queryContext->setContext('none');

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            [],
            $this->expressionBuilder,
            $this->queryContext
        );

        $this->assertEmpty((string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getFrontendVisibilityRestrictionsSkipsUnconfiguredTables()
    {
        $this->queryContext->setContext('frontend');

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $this->assertEmpty((string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getFrontendVisibilityRestrictionsWithDefaultSettings()
    {
        $this->queryContext->setContext('frontend')
            ->setAccessTime(1459706700)
            ->setTableConfigs(['pages' => $this->defaultTableConfig]);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSql = join(' AND ', [
            '("pages"."deleted" = 0)',
            '("pages"."t3ver_state" <= 0)',
            '("pages"."pid" <> -1)',
            '("pages"."hidden" = 0)',
            '("pages"."starttime" <= 1459706700)',
            '(("pages"."endtime" = 0) OR ("pages"."endtime" > 1459706700))',
            '(("pages"."fe_group" IS NULL) OR ("pages"."fe_group" = \'\') OR ("pages"."fe_group" = \'0\'))'
        ]);

        $this->assertSame($expectedSql, (string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getFrontendVisibilityRestrictionsWithUserGroups()
    {
        $this->queryContext->setContext('frontend')
            ->setAccessTime(1459706700)
            ->setMemberGroups([1, 2])
            ->setTableConfigs(['pages' => $this->defaultTableConfig]);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSql = join(' AND ', [
            '("pages"."deleted" = 0)',
            '("pages"."t3ver_state" <= 0)',
            '("pages"."pid" <> -1)',
            '("pages"."hidden" = 0)',
            '("pages"."starttime" <= 1459706700)',
            '(("pages"."endtime" = 0) OR ("pages"."endtime" > 1459706700))',
            '(("pages"."fe_group" IS NULL) OR ("pages"."fe_group" = \'\') OR ("pages"."fe_group" = \'0\') OR (FIND_IN_SET(\'1\', "pages"."fe_group")) OR (FIND_IN_SET(\'2\', "pages"."fe_group")))'
        ]);

        $this->assertSame($expectedSql, (string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getFrontendVisibilityRestrictionsWithVersioningPreview()
    {
        $this->queryContext->setContext('frontend')
            ->setAccessTime(1459706700)
            ->setIncludePlaceholders(true)
            ->setTableConfigs(['pages' => $this->defaultTableConfig]);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSql = '("pages"."deleted" = 0) AND ("pages"."pid" <> -1)';

        $this->assertSame($expectedSql, (string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getFrontendVisibilityRestrictionsWithVersioningPreviewAndNoPreviewSet()
    {
        $this->queryContext->setContext('frontend')
            ->setAccessTime(1459706700)
            ->setIncludePlaceholders(true)
            ->setIncludeVersionedRecords(true)
            ->setTableConfigs(['pages' => $this->defaultTableConfig]);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSql = join(' AND ', [
            '("pages"."deleted" = 0)',
            '("pages"."hidden" = 0)',
            '("pages"."starttime" <= 1459706700)',
            '(("pages"."endtime" = 0) OR ("pages"."endtime" > 1459706700))',
            '(("pages"."fe_group" IS NULL) OR ("pages"."fe_group" = \'\') OR ("pages"."fe_group" = \'0\'))'
        ]);

        $this->assertSame($expectedSql, (string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getFrontendVisibilityRestrictionsWithoutDisabledColumn()
    {
        $this->queryContext->setContext('frontend')
            ->setAccessTime(1459706700)
            ->setTableConfigs(['pages' => [
                'versioningWS' => true,
                'delete' => 'deleted',
                'enablecolumns' => [
                    'starttime' => 'starttime',
                    'endtime' => 'endtime',
                    'fe_group' => 'fe_group',
                ]
            ]]);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSql = join(' AND ', [
            '("pages"."deleted" = 0)',
            '("pages"."t3ver_state" <= 0)',
            '("pages"."pid" <> -1)',
            '("pages"."starttime" <= 1459706700)',
            '(("pages"."endtime" = 0) OR ("pages"."endtime" > 1459706700))',
            '(("pages"."fe_group" IS NULL) OR ("pages"."fe_group" = \'\') OR ("pages"."fe_group" = \'0\'))'
        ]);

        $this->assertSame($expectedSql, (string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getFrontendVisibilityRestrictionsWithoutStarttimeColumn()
    {
        $this->queryContext->setContext('frontend')
            ->setAccessTime(1459706700)
            ->setTableConfigs([
                'pages' => [
                    'versioningWS' => true,
                    'delete' => 'deleted',
                    'enablecolumns' => [
                        'disabled' => 'hidden',
                        'endtime' => 'endtime',
                        'fe_group' => 'fe_group',
                    ]
                ]
            ]);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSql = join(' AND ', [
            '("pages"."deleted" = 0)',
            '("pages"."t3ver_state" <= 0)',
            '("pages"."pid" <> -1)',
            '("pages"."hidden" = 0)',
            '(("pages"."endtime" = 0) OR ("pages"."endtime" > 1459706700))',
            '(("pages"."fe_group" IS NULL) OR ("pages"."fe_group" = \'\') OR ("pages"."fe_group" = \'0\'))'
        ]);

        $this->assertSame($expectedSql, (string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getFrontendVisibilityRestrictionsWithoutEndtimeColumn()
    {
        $this->queryContext->setContext('frontend')
            ->setAccessTime(1459706700)
            ->setTableConfigs([
                'pages' => [
                    'versioningWS' => true,
                    'delete' => 'deleted',
                    'enablecolumns' => [
                        'disabled' => 'hidden',
                        'starttime' => 'starttime',
                        'fe_group' => 'fe_group',
                    ]
                ]
            ]);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSql = join(' AND ', [
            '("pages"."deleted" = 0)',
            '("pages"."t3ver_state" <= 0)',
            '("pages"."pid" <> -1)',
            '("pages"."hidden" = 0)',
            '("pages"."starttime" <= 1459706700)',
            '(("pages"."fe_group" IS NULL) OR ("pages"."fe_group" = \'\') OR ("pages"."fe_group" = \'0\'))'
        ]);

        $this->assertSame($expectedSql, (string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getFrontendVisibilityRestrictionsWithoutUsergroupsColumn()
    {
        $this->queryContext->setContext('frontend')
            ->setAccessTime(1459706700)
            ->setTableConfigs([
                'pages' => [
                    'versioningWS' => true,
                    'delete' => 'deleted',
                    'enablecolumns' => [
                        'disabled' => 'hidden',
                        'starttime' => 'starttime',
                        'endtime' => 'endtime',
                    ]
                ]
            ]);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSql = join(' AND ', [
            '("pages"."deleted" = 0)',
            '("pages"."t3ver_state" <= 0)',
            '("pages"."pid" <> -1)',
            '("pages"."hidden" = 0)',
            '("pages"."starttime" <= 1459706700)',
            '(("pages"."endtime" = 0) OR ("pages"."endtime" > 1459706700))',
        ]);

        $this->assertSame($expectedSql, (string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getFrontendVisibilityRestrictionsWithIgnoreEnableFieldsSet()
    {
        $this->queryContext->setContext('frontend')
            ->setAccessTime(1459706700)
            ->setTableConfigs(['pages' => $this->defaultTableConfig])
            ->setIgnoreEnableFields(true);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSql = '"pages"."deleted" = 0';

        $this->assertSame($expectedSql, (string)$subject->getVisibilityConstraints());
    }

    /**
     * Data provider for getFrontendVisibilityRestrictionsWithSelectiveIgnoreEnableFieldsSet
     *
     * @return array
     */
    public function getFrontendVisibilityRestrictionsIgnoreEnableFieldsDataProvider()
    {
        return [
            'disabled' => [
                ['disabled'],
            ],
            'starttime' => [
                ['starttime'],
            ],
            'endtime' => [
                ['endtime'],
            ],
            'starttime, endtime' => [
                ['starttime', 'endtime'],
            ],
            'fe_group' => [
                ['fe_group'],
            ],
            'disabled, starttime, endtime' => [
                ['disabled', 'starttime', 'endtime'],
            ],
            'disabled, starttime, endtime, fe_group' => [
                ['disabled', 'starttime', 'endtime', 'fe_group'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getFrontendVisibilityRestrictionsIgnoreEnableFieldsDataProvider
     * @param string[] $ignoreFields
     */
    public function getFrontendVisibilityRestrictionsWithSelectiveIgnoreEnableFieldsSet(array $ignoreFields)
    {
        $this->queryContext->setContext('frontend')
            ->setAccessTime(1459706700)
            ->setTableConfigs(['pages' => $this->defaultTableConfig])
            ->setIgnoreEnableFields(true)
            ->setIgnoredEnableFields($ignoreFields);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSqlFragments = [
            'deleted' => '("pages"."deleted" = 0)',
            'versioningWS' => '("pages"."t3ver_state" <= 0) AND ("pages"."pid" <> -1)',
            'disabled' => '("pages"."hidden" = 0)',
            'starttime' => '("pages"."starttime" <= 1459706700)',
            'endtime' => '(("pages"."endtime" = 0) OR ("pages"."endtime" > 1459706700))',
            'fe_group' => '(("pages"."fe_group" IS NULL) OR ("pages"."fe_group" = \'\') OR ("pages"."fe_group" = \'0\'))'
        ];

        foreach ($ignoreFields as $fragmentName) {
            unset($expectedSqlFragments[$fragmentName]);
        }

        $this->assertSame(join(' AND ', $expectedSqlFragments), (string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getFrontendVisibilityRestrictionsForMultipleTablesWithDefaultSettings()
    {
        $this->queryContext->setContext('frontend')
            ->setAccessTime(1459706700)
            ->setTableConfigs([
                'pages' => $this->defaultTableConfig,
                'tt_content' => $this->defaultTableConfig,
            ]);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => '', 'tt_content' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSqlPages = join(' AND ', [
            '("pages"."deleted" = 0)',
            '("pages"."t3ver_state" <= 0)',
            '("pages"."pid" <> -1)',
            '("pages"."hidden" = 0)',
            '("pages"."starttime" <= 1459706700)',
            '(("pages"."endtime" = 0) OR ("pages"."endtime" > 1459706700))',
            '(("pages"."fe_group" IS NULL) OR ("pages"."fe_group" = \'\') OR ("pages"."fe_group" = \'0\'))'
        ]);

        $expectedSqlTtContent = join(' AND ', [
            '("tt_content"."deleted" = 0)',
            '("tt_content"."t3ver_state" <= 0)',
            '("tt_content"."pid" <> -1)',
            '("tt_content"."hidden" = 0)',
            '("tt_content"."starttime" <= 1459706700)',
            '(("tt_content"."endtime" = 0) OR ("tt_content"."endtime" > 1459706700))',
            '(("tt_content"."fe_group" IS NULL) OR ("tt_content"."fe_group" = \'\') OR ("tt_content"."fe_group" = \'0\'))'
        ]);

        $this->assertSame(
            '(' . $expectedSqlPages . ') AND (' . $expectedSqlTtContent . ')',
            (string)$subject->getVisibilityConstraints()
        );
    }

    /**
     * @test
     */
    public function getFrontendVisibilityRestrictionsForMultipleTablesWithIgnoreEnableFields()
    {
        $this->queryContext->setContext('frontend')
            ->setAccessTime(1459706700)
            ->setTableConfigs([
                'pages' => $this->defaultTableConfig,
                'tt_content' => $this->defaultTableConfig,
            ])
            ->setIgnoreEnableFields(true);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => '', 'tt_content' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSql = join(' AND ', [
            '("pages"."deleted" = 0)',
            '("tt_content"."deleted" = 0)',
        ]);

        $this->assertSame($expectedSql, (string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getFrontendVisibilityRestrictionsForMultipleTablesWithDifferentEnableColumns()
    {
        $this->queryContext->setContext('frontend')
            ->setAccessTime(1459706700)
            ->setTableConfigs([
                'pages' => $this->defaultTableConfig,
                'tt_content' => [
                    'versioningWS' => false,
                    'delete' => 'deleted',
                    'enablecolumns' => [
                        'disabled' => 'hidden',
                    ],
                ],
            ]);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => '', 'tt_content' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSql = join(' AND ', [
            '("pages"."deleted" = 0)',
            '("pages"."t3ver_state" <= 0)',
            '("pages"."pid" <> -1)',
            '("pages"."hidden" = 0)',
            '("pages"."starttime" <= 1459706700)',
            '(("pages"."endtime" = 0) OR ("pages"."endtime" > 1459706700))',
            '(("pages"."fe_group" IS NULL) OR ("pages"."fe_group" = \'\') OR ("pages"."fe_group" = \'0\'))'
        ]);

        $this->assertSame(
            '(' . $expectedSql . ') AND (("tt_content"."deleted" = 0) AND ("tt_content"."hidden" = 0))',
            (string)$subject->getVisibilityConstraints()
        );
    }

    /**
     * @test
     */
    public function getFrontendVisibilityRestrictionsForJoinedTablesWithDefaultSettings()
    {
        $this->queryContext->setContext('frontend')
            ->setAccessTime(1459706700)
            ->setTableConfigs([
                'pages' => $this->defaultTableConfig,
                'tt_content' => $this->defaultTableConfig,
            ]);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => '', 'tt_content' => 't'],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSqlPages = join(' AND ', [
            '("pages"."deleted" = 0)',
            '("pages"."t3ver_state" <= 0)',
            '("pages"."pid" <> -1)',
            '("pages"."hidden" = 0)',
            '("pages"."starttime" <= 1459706700)',
            '(("pages"."endtime" = 0) OR ("pages"."endtime" > 1459706700))',
            '(("pages"."fe_group" IS NULL) OR ("pages"."fe_group" = \'\') OR ("pages"."fe_group" = \'0\'))'
        ]);

        $expectedSqlTtContent = join(' AND ', [
            '("t"."deleted" = 0)',
            '("t"."t3ver_state" <= 0)',
            '("t"."pid" <> -1)',
            '("t"."hidden" = 0)',
            '("t"."starttime" <= 1459706700)',
            '(("t"."endtime" = 0) OR ("t"."endtime" > 1459706700))',
            '(("t"."fe_group" IS NULL) OR ("t"."fe_group" = \'\') OR ("t"."fe_group" = \'0\'))'
        ]);

        $this->assertSame(
            '(' . $expectedSqlPages . ') AND (' . $expectedSqlTtContent . ')',
            (string)$subject->getVisibilityConstraints()
        );
    }

    /**
     * @test
     */
    public function getFrontendVisibilityRestrictionsForJoinedTablesWithIgnoreEnableFields()
    {
        $this->queryContext->setContext('frontend')
            ->setAccessTime(1459706700)
            ->setTableConfigs([
                'pages' => $this->defaultTableConfig,
                'tt_content' => $this->defaultTableConfig,
            ])
            ->setIgnoreEnableFields(true);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => '', 'tt_content' => 't'],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSql = join(' AND ', [
            '("pages"."deleted" = 0)',
            '("t"."deleted" = 0)',
        ]);

        $this->assertSame($expectedSql, (string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getFrontendVisibilityRestrictionsForJoinedTablesWithDifferentEnableColumns()
    {
        $this->queryContext->setContext('frontend')
            ->setAccessTime(1459706700)
            ->setTableConfigs([
                'pages' => $this->defaultTableConfig,
                'tt_content' => [
                    'versioningWS' => false,
                    'delete' => 'deleted',
                    'enablecolumns' => [
                        'disabled' => 'hidden',
                    ],
                ],
            ]);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => '', 'tt_content' => 't'],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSql = join(' AND ', [
            '("pages"."deleted" = 0)',
            '("pages"."t3ver_state" <= 0)',
            '("pages"."pid" <> -1)',
            '("pages"."hidden" = 0)',
            '("pages"."starttime" <= 1459706700)',
            '(("pages"."endtime" = 0) OR ("pages"."endtime" > 1459706700))',
            '(("pages"."fe_group" IS NULL) OR ("pages"."fe_group" = \'\') OR ("pages"."fe_group" = \'0\'))'
        ]);

        $this->assertSame(
            '(' . $expectedSql . ') AND (("t"."deleted" = 0) AND ("t"."hidden" = 0))',
            (string)$subject->getVisibilityConstraints()
        );
    }

    /**
     * @test
     */
    public function getBackendVisibilityRestrictionsSkipsUnconfiguredTables()
    {
        $this->queryContext->setContext('backend');

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $this->assertEmpty((string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getBackendVisibilityRestrictionsWithDefaultSettings()
    {
        $this->queryContext->setContext('backend')
            ->setAccessTime(1459706700)
            ->setTableConfigs(['pages' => $this->defaultTableConfig]);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSql = join(' AND ', [
            '("pages"."hidden" = 0)',
            '("pages"."starttime" <= 1459706700)',
            '(("pages"."endtime" = 0) OR ("pages"."endtime" > 1459706700))',
            '("pages"."deleted" = 0)',
        ]);

        $this->assertSame($expectedSql, (string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getBackendVisibilityRestrictionsWithoutDisabledColumn()
    {
        $this->queryContext->setContext('backend')
            ->setAccessTime(1459706700)
            ->setTableConfigs(['pages' => [
                'versioningWS' => true,
                'delete' => 'deleted',
                'enablecolumns' => [
                    'starttime' => 'starttime',
                    'endtime' => 'endtime',
                    'fe_group' => 'fe_group',
                ],
            ]]);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSql = join(' AND ', [
            '("pages"."starttime" <= 1459706700)',
            '(("pages"."endtime" = 0) OR ("pages"."endtime" > 1459706700))',
            '("pages"."deleted" = 0)',
        ]);

        $this->assertSame($expectedSql, (string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getBackendVisibilityRestrictionsWithoutStarttimeColumn()
    {
        $this->queryContext->setContext('backend')
            ->setAccessTime(1459706700)
            ->setTableConfigs([
                'pages' => [
                    'versioningWS' => true,
                    'delete' => 'deleted',
                    'enablecolumns' => [
                        'disabled' => 'hidden',
                        'endtime' => 'endtime',
                        'fe_group' => 'fe_group',
                    ],
                ]
            ]);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSql = join(' AND ', [
            '("pages"."hidden" = 0)',
            '(("pages"."endtime" = 0) OR ("pages"."endtime" > 1459706700))',
            '("pages"."deleted" = 0)',
        ]);

        $this->assertSame($expectedSql, (string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getBackendVisibilityRestrictionsWithoutEndtimeColumn()
    {
        $this->queryContext->setContext('backend')
            ->setAccessTime(1459706700)
            ->setTableConfigs([
                'pages' => [
                    'versioningWS' => true,
                    'delete' => 'deleted',
                    'enablecolumns' => [
                        'disabled' => 'hidden',
                        'starttime' => 'starttime',
                        'fe_group' => 'fe_group',
                    ],
                ]
            ]);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSql = join(' AND ', [
            '("pages"."hidden" = 0)',
            '("pages"."starttime" <= 1459706700)',
            '("pages"."deleted" = 0)',
        ]);

        $this->assertSame($expectedSql, (string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getBackendVisibilityRestrictionsWithIgnoreEnableFieldsSet()
    {
        $this->queryContext->setContext('backend')
            ->setAccessTime(1459706700)
            ->setTableConfigs(['pages' => $this->defaultTableConfig])
            ->setIgnoreEnableFields(true);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSql = '"pages"."deleted" = 0';

        $this->assertSame($expectedSql, (string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getBackendVisibilityRestrictionsWithIncludeDeletedSet()
    {
        $this->queryContext->setContext('backend')
            ->setAccessTime(1459706700)
            ->setTableConfigs(['pages' => $this->defaultTableConfig])
            ->setIncludeDeleted(true);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $expectedSql = join(' AND ', [
            'disabled' => '("pages"."hidden" = 0)',
            'starttime' => '("pages"."starttime" <= 1459706700)',
            'endtime' => '(("pages"."endtime" = 0) OR ("pages"."endtime" > 1459706700))',
        ]);

        $this->assertSame($expectedSql, (string)$subject->getVisibilityConstraints());
    }

    /**
     * @test
     */
    public function getBackendVisibilityRestrictionsWithoutRestrictions()
    {
        $this->queryContext->setContext('backend')
            ->setAccessTime(1459706700)
            ->setTableConfigs(['pages' => $this->defaultTableConfig])
            ->setIncludeDeleted(true)
            ->setIgnoreEnableFields(true);

        $subject = GeneralUtility::makeInstance(
            QueryRestrictionBuilder::class,
            ['pages' => ''],
            $this->expressionBuilder,
            $this->queryContext
        );

        $this->assertSame('', (string)$subject->getVisibilityConstraints());
    }

    // @todo: Test for per table overrides
}
