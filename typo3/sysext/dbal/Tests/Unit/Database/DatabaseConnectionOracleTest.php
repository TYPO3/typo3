<?php
namespace TYPO3\CMS\Dbal\Tests\Unit\Database;

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
 * Test case
 */
class DatabaseConnectionOracleTest extends AbstractTestCase
{
    /**
     * @var \TYPO3\CMS\Dbal\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $subject;

    /**
     * Prepare a DatabaseConnection subject ready to parse oracle queries
     *
     * @return void
     */
    protected function setUp()
    {
        $configuration = [
            'handlerCfg' => [
                '_DEFAULT' => [
                    'type' => 'adodb',
                    'config' => [
                        'driver' => 'oci8',
                    ],
                ],
            ],
            'mapping' => [
                'cachingframework_cache_hash' => [
                    'mapTableName' => 'cf_cache_hash',
                ],
                'cachingframework_cache_hash_tags' => [
                    'mapTableName' => 'cf_cache_hash_tags',
                ],
                'cachingframework_cache_pages' => [
                    'mapTableName' => 'cf_cache_pages',
                ],
                'cpg_categories' => [
                    'mapFieldNames' => [
                        'pid' => 'page_id',
                    ],
                ],
                'pages' => [
                    'mapTableName' => 'my_pages',
                    'mapFieldNames' => [
                        'uid' => 'page_uid',
                    ],
                ],
                'tt_news' => [
                    'mapTableName' => 'ext_tt_news',
                    'mapFieldNames' => [
                        'uid' => 'news_uid',
                        'fe_group' => 'usergroup',
                    ],
                ],
                'tt_news_cat' => [
                    'mapTableName' => 'ext_tt_news_cat',
                    'mapFieldNames' => [
                        'uid' => 'cat_uid',
                    ],
                ],
                'tt_news_cat_mm' => [
                    'mapTableName' => 'ext_tt_news_cat_mm',
                    'mapFieldNames' => [
                        'uid_local' => 'local_uid',
                    ],
                ],
                'tx_crawler_process' => [
                    'mapTableName' => 'tx_crawler_ps',
                    'mapFieldNames' => [
                        'process_id' => 'ps_id',
                        'active' => 'is_active',
                    ],
                ],
                'tx_dam_file_tracking' => [
                    'mapFieldNames' => [
                        'file_name' => 'filename',
                        'file_path' => 'path',
                    ],
                ],
                'tx_dbal_debuglog' => [
                    'mapFieldNames' => [
                        'errorFlag' => 'errorflag',
                    ],
                ],
                'tx_templavoila_datastructure' => [
                    'mapTableName' => 'tx_templavoila_ds',
                ],
            ],
        ];

        $this->subject = $this->prepareSubject('oci8', $configuration);
    }

    /**
     * @test
     */
    public function runningADOdbDriverReturnsTrueWithOci8ForOci8DefaultDriverConfiguration()
    {
        $this->assertTrue($this->subject->runningADOdbDriver('oci8'));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/21780
     */
    public function sqlHintIsRemoved()
    {
        $result = $this->subject->SELECTquery('/*! SQL_NO_CACHE */ content', 'tx_realurl_urlencodecache', '1=1');
        $expected = 'SELECT "content" FROM "tx_realurl_urlencodecache" WHERE 1 = 1';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     */
    public function canCompileInsertWithFields()
    {
        $parseString = 'INSERT INTO static_territories (uid, pid, tr_iso_nr, tr_parent_iso_nr, tr_name_en) ';
        $parseString .= 'VALUES (\'1\', \'0\', \'2\', \'0\', \'Africa\');';
        $components = $this->subject->SQLparser->_callRef('parseINSERT', $parseString);
        $this->assertTrue(is_array($components), $components);
        $insert = $this->subject->SQLparser->compileSQL($components);
        $expected = [
            'uid' => '1',
            'pid' => '0',
            'tr_iso_nr' => '2',
            'tr_parent_iso_nr' => '0',
            'tr_name_en' => 'Africa'
        ];
        $this->assertEquals($expected, $insert);
    }

    /**
     * @test
     */
    public function canCompileExtendedInsert()
    {
        $tableFields = ['uid', 'pid', 'tr_iso_nr', 'tr_parent_iso_nr', 'tr_name_en'];
        $this->subject->cache_fieldType['static_territories'] = array_flip($tableFields);
        $parseString = 'INSERT INTO static_territories VALUES (\'1\', \'0\', \'2\', \'0\', \'Africa\'),(\'2\', \'0\', \'9\', \'0\', \'Oceania\'),' . '(\'3\', \'0\', \'19\', \'0\', \'Americas\'),(\'4\', \'0\', \'142\', \'0\', \'Asia\');';
        $components = $this->subject->SQLparser->_callRef('parseINSERT', $parseString);
        $this->assertTrue(is_array($components), $components);
        $insert = $this->subject->SQLparser->compileSQL($components);
        $insertCount = count($insert);
        $this->assertEquals(4, $insertCount);
        for ($i = 0; $i < $insertCount; $i++) {
            foreach ($tableFields as $field) {
                $this->assertTrue(isset($insert[$i][$field]), 'Could not find ' . $field . ' column');
            }
        }
    }

    /**
     * @test
     */
    public function sqlForInsertWithMultipleRowsIsValid()
    {
        $fields = ['uid', 'pid', 'title', 'body'];
        $rows = [
            ['1', '2', 'Title #1', 'Content #1'],
            ['3', '4', 'Title #2', 'Content #2'],
            ['5', '6', 'Title #3', 'Content #3']
        ];
        $result = $this->subject->INSERTmultipleRows('tt_content', $fields, $rows);
        $expected[0] = 'INSERT INTO "tt_content" ( "uid", "pid", "title", "body" ) VALUES ( \'1\', \'2\', \'Title #1\', \'Content #1\' )';
        $expected[1] = 'INSERT INTO "tt_content" ( "uid", "pid", "title", "body" ) VALUES ( \'3\', \'4\', \'Title #2\', \'Content #2\' )';
        $expected[2] = 'INSERT INTO "tt_content" ( "uid", "pid", "title", "body" ) VALUES ( \'5\', \'6\', \'Title #3\', \'Content #3\' )';
        $resultCount = count($result);
        $this->assertEquals(count($expected), $resultCount);
        for ($i = 0; $i < $resultCount; $i++) {
            $this->assertTrue(is_array($result[$i]), 'Expected array: ' . $result[$i]);
            $this->assertEquals(1, count($result[$i]));
            $this->assertEquals($expected[$i], $this->cleanSql($result[$i][0]));
        }
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/23431
     */
    public function groupConditionsAreProperlyTransformed()
    {
        $result = $this->subject->SELECTquery('*', 'pages', 'pid=0 AND pages.deleted=0 AND pages.hidden=0 AND pages.starttime<=1281620460 ' . 'AND (pages.endtime=0 OR pages.endtime>1281620460) AND NOT pages.t3ver_state>0 ' . 'AND pages.doktype<200 AND (pages.fe_group=\'\' OR pages.fe_group IS NULL OR ' . 'pages.fe_group=\'0\' OR FIND_IN_SET(\'0\',pages.fe_group) OR FIND_IN_SET(\'-1\',pages.fe_group))');
        $expected = 'SELECT * FROM "pages" WHERE "pid" = 0 AND "pages"."deleted" = 0 AND "pages"."hidden" = 0 ' . 'AND "pages"."starttime" <= 1281620460 AND ("pages"."endtime" = 0 OR "pages"."endtime" > 1281620460) ' . 'AND NOT "pages"."t3ver_state" > 0 AND "pages"."doktype" < 200 AND ("pages"."fe_group" = \'\' ' . 'OR "pages"."fe_group" IS NULL OR "pages"."fe_group" = \'0\' OR \',\'||"pages"."fe_group"||\',\' LIKE \'%,0,%\' ' . 'OR \',\'||"pages"."fe_group"||\',\' LIKE \'%,-1,%\')';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    ///////////////////////////////////////
    // Tests concerning quoting
    ///////////////////////////////////////
    /**
     * @test
     */
    public function selectQueryIsProperlyQuoted()
    {
        $result = $this->subject->SELECTquery('uid', 'tt_content', 'pid=1', 'cruser_id', 'tstamp');
        $expected = 'SELECT "uid" FROM "tt_content" WHERE "pid" = 1 GROUP BY "cruser_id" ORDER BY "tstamp"';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     */
    public function truncateQueryIsProperlyQuoted()
    {
        $result = $this->subject->TRUNCATEquery('be_users');
        $expected = 'TRUNCATE TABLE "be_users"';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/15535
     */
    public function distinctFieldIsProperlyQuoted()
    {
        $result = $this->subject->SELECTquery('COUNT(DISTINCT pid)', 'tt_content', '1=1');
        $expected = 'SELECT COUNT(DISTINCT "pid") FROM "tt_content" WHERE 1 = 1';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/19999
     * @remark Remapping is not expected here
     */
    public function multipleInnerJoinsAreProperlyQuoted()
    {
        $result = $this->subject->SELECTquery('*', 'tt_news_cat INNER JOIN tt_news_cat_mm ON tt_news_cat.uid = tt_news_cat_mm.uid_foreign INNER JOIN tt_news ON tt_news.uid = tt_news_cat_mm.uid_local', '1=1');
        $expected = 'SELECT * FROM "tt_news_cat"';
        $expected .= ' INNER JOIN "tt_news_cat_mm" ON "tt_news_cat"."uid"="tt_news_cat_mm"."uid_foreign"';
        $expected .= ' INNER JOIN "tt_news" ON "tt_news"."uid"="tt_news_cat_mm"."uid_local"';
        $expected .= ' WHERE 1 = 1';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/17554
     */
    public function stringsWithinInClauseAreProperlyQuoted()
    {
        $result = $this->subject->SELECTquery('COUNT(DISTINCT tx_dam.uid) AS count', 'tx_dam', 'tx_dam.pid IN (1) AND tx_dam.file_type IN (\'gif\',\'png\',\'jpg\',\'jpeg\') AND tx_dam.deleted = 0');
        $expected = 'SELECT COUNT(DISTINCT "tx_dam"."uid") AS "count" FROM "tx_dam"';
        $expected .= ' WHERE "tx_dam"."pid" IN (1) AND "tx_dam"."file_type" IN (\'gif\',\'png\',\'jpg\',\'jpeg\') AND "tx_dam"."deleted" = 0';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/21502
     * @remark Remapping is not expected here
     */
    public function concatAfterLikeOperatorIsProperlyQuoted()
    {
        $result = $this->subject->SELECTquery('*', 'sys_refindex, tx_dam_file_tracking', 'sys_refindex.tablename = \'tx_dam_file_tracking\'' . ' AND sys_refindex.ref_string LIKE CONCAT(tx_dam_file_tracking.file_path, tx_dam_file_tracking.file_name)');
        $expected = 'SELECT * FROM "sys_refindex", "tx_dam_file_tracking" WHERE "sys_refindex"."tablename" = \'tx_dam_file_tracking\'';
        $expected .= ' AND (instr(LOWER("sys_refindex"."ref_string"), concat("tx_dam_file_tracking"."file_path","tx_dam_file_tracking"."file_name"),1,1) > 0)';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/21268
     */
    public function cachingFrameworkQueryIsProperlyQuoted()
    {
        $currentTime = time();
        $result = $this->subject->SELECTquery('content', 'cache_hash', 'identifier = ' . $this->subject->fullQuoteStr('abbbabaf2d4b3f9a63e8dde781f1c106', 'cache_hash') . ' AND (crdate + lifetime >= ' . $currentTime . ' OR lifetime = 0)');
        $expected = 'SELECT "content" FROM "cache_hash" WHERE "identifier" = \'abbbabaf2d4b3f9a63e8dde781f1c106\' AND ("crdate"+"lifetime" >= ' . $currentTime . ' OR "lifetime" = 0)';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/21268
     */
    public function calculatedFieldsAreProperlyQuoted()
    {
        $currentTime = time();
        $result = $this->subject->SELECTquery('identifier', 'cachingframework_cache_pages', 'crdate + lifetime < ' . $currentTime . ' AND lifetime > 0');
        $expected = 'SELECT "identifier" FROM "cachingframework_cache_pages" WHERE "crdate"+"lifetime" < ' . $currentTime . ' AND "lifetime" > 0';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     */
    public function numericColumnsAreNotQuoted()
    {
        $result = $this->subject->SELECTquery('1', 'be_users', 'username = \'_cli_scheduler\' AND admin = 0 AND be_users.deleted = 0');
        $expected = 'SELECT 1 FROM "be_users" WHERE "username" = \'_cli_scheduler\' AND "admin" = 0 AND "be_users"."deleted" = 0';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    ///////////////////////////////////////
    // Tests concerning remapping
    ///////////////////////////////////////
    /**
     * @test
     * @see http://forge.typo3.org/issues/19999
     * @remark Remapping is expected here
     */
    public function tablesAndFieldsAreRemappedInMultipleJoins()
    {
        $selectFields = '*';
        $fromTables = 'tt_news_cat INNER JOIN tt_news_cat_mm ON tt_news_cat.uid = tt_news_cat_mm.uid_foreign INNER JOIN tt_news ON tt_news.uid = tt_news_cat_mm.uid_local';
        $whereClause = '1=1';
        $groupBy = '';
        $orderBy = '';
        $remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);

        $result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
        $expected = 'SELECT * FROM "ext_tt_news_cat"';
        $expected .= ' INNER JOIN "ext_tt_news_cat_mm" ON "ext_tt_news_cat"."cat_uid"="ext_tt_news_cat_mm"."uid_foreign"';
        $expected .= ' INNER JOIN "ext_tt_news" ON "ext_tt_news"."news_uid"="ext_tt_news_cat_mm"."local_uid"';
        $expected .= ' WHERE 1 = 1';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see https://forge.typo3.org/issues/67067
     */
    public function tablesAreUnmappedInAdminGetTables()
    {
        $handlerMock = $this->getMock('\ADODB_mock', ['MetaTables'], [], '', false);
        $handlerMock->expects($this->any())->method('MetaTables')->will($this->returnValue(['cf_cache_hash']));
        $this->subject->handlerInstance['_DEFAULT'] = $handlerMock;

        $actual = $this->subject->admin_get_tables();
        $expected = ['cachingframework_cache_hash' => ['Name' => 'cachingframework_cache_hash']];
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/17918
     */
    public function fieldWithinSqlFunctionIsRemapped()
    {
        $selectFields = 'tstamp, script, SUM(exec_time) AS calc_sum, COUNT(*) AS qrycount, MAX(errorFlag) AS error';
        $fromTables = 'tx_dbal_debuglog';
        $whereClause = '1=1';
        $groupBy = '';
        $orderBy = '';
        $remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);

        $result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
        $expected = 'SELECT "tstamp", "script", SUM("exec_time") AS "calc_sum", COUNT(*) AS "qrycount", MAX("errorflag") AS "error" FROM "tx_dbal_debuglog" WHERE 1 = 1';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/17918
     */
    public function tableAndFieldWithinSqlFunctionIsRemapped()
    {
        $selectFields = 'MAX(tt_news_cat.uid) AS biggest_id';
        $fromTables = 'tt_news_cat INNER JOIN tt_news_cat_mm ON tt_news_cat.uid = tt_news_cat_mm.uid_foreign';
        $whereClause = 'tt_news_cat_mm.uid_local > 50';
        $groupBy = '';
        $orderBy = '';
        $remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);

        $result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
        $expected = 'SELECT MAX("ext_tt_news_cat"."cat_uid") AS "biggest_id" FROM "ext_tt_news_cat"';
        $expected .= ' INNER JOIN "ext_tt_news_cat_mm" ON "ext_tt_news_cat"."cat_uid"="ext_tt_news_cat_mm"."uid_foreign"';
        $expected .= ' WHERE "ext_tt_news_cat_mm"."local_uid" > 50';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/21502
     * @remark Remapping is expected here
     */
    public function concatAfterLikeOperatorIsRemapped()
    {
        $selectFields = '*';
        $fromTables = 'sys_refindex, tx_dam_file_tracking';
        $whereClause = 'sys_refindex.tablename = \'tx_dam_file_tracking\'' . ' AND sys_refindex.ref_string LIKE CONCAT(tx_dam_file_tracking.file_path, tx_dam_file_tracking.file_name)';
        $groupBy = '';
        $orderBy = '';
        $remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);

        $result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
        $expected = 'SELECT * FROM "sys_refindex", "tx_dam_file_tracking" WHERE "sys_refindex"."tablename" = \'tx_dam_file_tracking\'';
        $expected .= ' AND (instr(LOWER("sys_refindex"."ref_string"), concat("tx_dam_file_tracking"."path","tx_dam_file_tracking"."filename"),1,1) > 0)';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/17341
     */
    public function fieldIsMappedOnRightSideOfAJoinCondition()
    {
        $selectFields = 'cpg_categories.uid, cpg_categories.name';
        $fromTables = 'cpg_categories, pages';
        $whereClause = 'pages.uid = cpg_categories.pid AND pages.deleted = 0 AND 1 = 1';
        $groupBy = '';
        $orderBy = 'cpg_categories.pos';
        $remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);

        $result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
        $expected = 'SELECT "cpg_categories"."uid", "cpg_categories"."name" FROM "cpg_categories", "my_pages" WHERE "my_pages"."page_uid" = "cpg_categories"."page_id"';
        $expected .= ' AND "my_pages"."deleted" = 0 AND 1 = 1 ORDER BY "cpg_categories"."pos"';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/22640
     */
    public function fieldFromAliasIsRemapped()
    {
        $selectFields = 'news.uid';
        $fromTables = 'tt_news AS news';
        $whereClause = 'news.uid = 1';
        $groupBy = '';
        $orderBy = '';
        $remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);

        $result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
        $expected = 'SELECT "news"."news_uid" FROM "ext_tt_news" AS "news" WHERE "news"."news_uid" = 1';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * Trick here is that we already have a mapping for both table tt_news and table tt_news_cat
     * (see tests/fixtures/oci8.config.php) which is used as alias name.
     *
     * @test
     * @see http://forge.typo3.org/issues/22640
     */
    public function fieldFromAliasIsRemappedWithoutBeingTricked()
    {
        $selectFields = 'tt_news_cat.uid';
        $fromTables = 'tt_news AS tt_news_cat';
        $whereClause = 'tt_news_cat.uid = 1';
        $groupBy = '';
        $orderBy = '';
        $remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);

        $result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
        $expected = 'SELECT "tt_news_cat"."news_uid" FROM "ext_tt_news" AS "tt_news_cat" WHERE "tt_news_cat"."news_uid" = 1';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/22640
     */
    public function fieldFromAliasInJoinIsRemapped()
    {
        $selectFields = 'cat.uid, cat_mm.uid_local, news.uid';
        $fromTables = 'tt_news_cat AS cat' . ' INNER JOIN tt_news_cat_mm AS cat_mm ON cat.uid = cat_mm.uid_foreign' . ' INNER JOIN tt_news AS news ON news.uid = cat_mm.uid_local';
        $whereClause = '1=1';
        $groupBy = '';
        $orderBy = '';
        $remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);

        $result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
        $expected = 'SELECT "cat"."cat_uid", "cat_mm"."local_uid", "news"."news_uid"';
        $expected .= ' FROM "ext_tt_news_cat" AS "cat"';
        $expected .= ' INNER JOIN "ext_tt_news_cat_mm" AS "cat_mm" ON "cat"."cat_uid"="cat_mm"."uid_foreign"';
        $expected .= ' INNER JOIN "ext_tt_news" AS "news" ON "news"."news_uid"="cat_mm"."local_uid"';
        $expected .= ' WHERE 1 = 1';

        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/22640
     */
    public function aliasRemappingWithInSubqueryDoesNotAffectMainQuery()
    {
        $selectFields = 'foo.uid';
        $fromTables = 'tt_news AS foo INNER JOIN tt_news_cat_mm ON tt_news_cat_mm.uid_local = foo.uid';
        $whereClause = 'tt_news_cat_mm.uid_foreign IN (SELECT foo.uid FROM tt_news_cat AS foo WHERE foo.hidden = 0)';
        $groupBy = '';
        $orderBy = 'foo.uid';
        $remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
        $result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
        $expected = 'SELECT "foo"."news_uid" FROM "ext_tt_news" AS "foo"';
        $expected .= ' INNER JOIN "ext_tt_news_cat_mm" ON "ext_tt_news_cat_mm"."local_uid"="foo"."news_uid"';
        $expected .= ' WHERE "ext_tt_news_cat_mm"."uid_foreign" IN (';
        $expected .= 'SELECT "foo"."cat_uid" FROM "ext_tt_news_cat" AS "foo" WHERE "foo"."hidden" = 0';
        $expected .= ')';
        $expected .= ' ORDER BY "foo"."news_uid"';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/22640
     */
    public function aliasRemappingWithExistsSubqueryDoesNotAffectMainQuery()
    {
        $selectFields = 'foo.uid';
        $fromTables = 'tt_news AS foo INNER JOIN tt_news_cat_mm ON tt_news_cat_mm.uid_local = foo.uid';
        $whereClause = 'EXISTS (SELECT foo.uid FROM tt_news_cat AS foo WHERE foo.hidden = 0)';
        $groupBy = '';
        $orderBy = 'foo.uid';
        $remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
        $result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
        $expected = 'SELECT "foo"."news_uid" FROM "ext_tt_news" AS "foo"';
        $expected .= ' INNER JOIN "ext_tt_news_cat_mm" ON "ext_tt_news_cat_mm"."local_uid"="foo"."news_uid"';
        $expected .= ' WHERE EXISTS (';
        $expected .= 'SELECT "foo"."cat_uid" FROM "ext_tt_news_cat" AS "foo" WHERE "foo"."hidden" = 0';
        $expected .= ')';
        $expected .= ' ORDER BY "foo"."news_uid"';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/22640
     */
    public function aliasRemappingSupportsNestedSubqueries()
    {
        $selectFields = 'foo.uid';
        $fromTables = 'tt_news AS foo';
        $whereClause = 'uid IN (' . 'SELECT foobar.uid_local FROM tt_news_cat_mm AS foobar WHERE uid_foreign IN (' . 'SELECT uid FROM tt_news_cat WHERE deleted = 0' . '))';
        $groupBy = '';
        $orderBy = '';
        $remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);

        $result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
        $expected = 'SELECT "foo"."news_uid" FROM "ext_tt_news" AS "foo"';
        $expected .= ' WHERE "news_uid" IN (';
        $expected .= 'SELECT "foobar"."local_uid" FROM "ext_tt_news_cat_mm" AS "foobar" WHERE "uid_foreign" IN (';
        $expected .= 'SELECT "cat_uid" FROM "ext_tt_news_cat" WHERE "deleted" = 0';
        $expected .= ')';
        $expected .= ')';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/22640
     */
    public function remappingDoesNotMixUpAliasesInSubquery()
    {
        $selectFields = 'pages.uid';
        $fromTables = 'tt_news AS pages INNER JOIN tt_news_cat_mm AS cat_mm ON cat_mm.uid_local = pages.uid';
        $whereClause = 'pages.pid IN (SELECT uid FROM pages WHERE deleted = 0 AND cat_mm.uid_local != 100)';
        $groupBy = '';
        $orderBy = 'pages.uid';
        $remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);

        $result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
        $expected = 'SELECT "pages"."news_uid" FROM "ext_tt_news" AS "pages"';
        $expected .= ' INNER JOIN "ext_tt_news_cat_mm" AS "cat_mm" ON "cat_mm"."local_uid"="pages"."news_uid"';
        $expected .= ' WHERE "pages"."pid" IN (';
        $expected .= 'SELECT "page_uid" FROM "my_pages" WHERE "deleted" = 0 AND "cat_mm"."local_uid" != 100';
        $expected .= ')';
        $expected .= ' ORDER BY "pages"."news_uid"';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/22716
     */
    public function likeIsRemappedAccordingToFieldTypeWithString()
    {
        $this->subject->cache_fieldType['tt_content']['bodytext']['metaType'] = 'B';
        $result = $this->subject->SELECTquery('*', 'tt_content', 'tt_content.bodytext LIKE \'foo%\'');
        $expected = 'SELECT * FROM "tt_content" WHERE (dbms_lob.instr(LOWER("tt_content"."bodytext"), \'foo\',1,1) > 0)';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/22716
     */
    public function likeIsRemappedAccordingToFieldTypeWithInteger()
    {
        $this->subject->cache_fieldType['tt_content']['bodytext']['metaType'] = 'B';
        $result = $this->subject->SELECTquery('*', 'fe_users', 'fe_users.usergroup LIKE \'2\'');
        $expected = 'SELECT * FROM "fe_users" WHERE (instr(LOWER("fe_users"."usergroup"), \'2\',1,1) > 0)';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/23282
     */
    public function notLikeIsRemappedAccordingToFieldTypeWithString()
    {
        $this->subject->cache_fieldType['tt_content']['bodytext']['metaType'] = 'B';
        $result = $this->subject->SELECTquery('*', 'tt_content', 'tt_content.bodytext NOT LIKE \'foo%\'');
        $expected = 'SELECT * FROM "tt_content" WHERE NOT (dbms_lob.instr(LOWER("tt_content"."bodytext"), \'foo\',1,1) > 0)';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/23282
     */
    public function notLikeIsRemappedAccordingToFieldTypeWithInteger()
    {
        $this->subject->cache_fieldType['tt_content']['bodytext']['metaType'] = 'B';
        $result = $this->subject->SELECTquery('*', 'fe_users', 'fe_users.usergroup NOT LIKE \'2\'');
        $expected = 'SELECT * FROM "fe_users" WHERE NOT (instr(LOWER("fe_users"."usergroup"), \'2\',1,1) > 0)';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/22716
     */
    public function instrIsUsedForCEOnPages()
    {
        $result = $this->subject->SELECTquery('*', 'tt_content', 'uid IN (62) AND tt_content.deleted=0 AND tt_content.t3ver_state<=0' . ' AND tt_content.hidden=0 AND (tt_content.starttime<=1264487640)' . ' AND (tt_content.endtime=0 OR tt_content.endtime>1264487640)' . ' AND (tt_content.fe_group=\'\' OR tt_content.fe_group IS NULL OR tt_content.fe_group=\'0\'' . ' OR (tt_content.fe_group LIKE \'%,0,%\' OR tt_content.fe_group LIKE \'0,%\' OR tt_content.fe_group LIKE \'%,0\'' . ' OR tt_content.fe_group=\'0\')' . ' OR (tt_content.fe_group LIKE\'%,-1,%\' OR tt_content.fe_group LIKE \'-1,%\' OR tt_content.fe_group LIKE \'%,-1\'' . ' OR tt_content.fe_group=\'-1\'))');
        $expected = 'SELECT * FROM "tt_content"';
        $expected .= ' WHERE "uid" IN (62) AND "tt_content"."deleted" = 0 AND "tt_content"."t3ver_state" <= 0';
        $expected .= ' AND "tt_content"."hidden" = 0 AND ("tt_content"."starttime" <= 1264487640)';
        $expected .= ' AND ("tt_content"."endtime" = 0 OR "tt_content"."endtime" > 1264487640)';
        $expected .= ' AND ("tt_content"."fe_group" = \'\' OR "tt_content"."fe_group" IS NULL OR "tt_content"."fe_group" = \'0\'';
        $expected .= ' OR ((instr(LOWER("tt_content"."fe_group"), \',0,\',1,1) > 0)';
        $expected .= ' OR (instr(LOWER("tt_content"."fe_group"), \'0,\',1,1) > 0)';
        $expected .= ' OR (instr(LOWER("tt_content"."fe_group"), \',0\',1,1) > 0)';
        $expected .= ' OR "tt_content"."fe_group" = \'0\')';
        $expected .= ' OR ((instr(LOWER("tt_content"."fe_group"), \',-1,\',1,1) > 0)';
        $expected .= ' OR (instr(LOWER("tt_content"."fe_group"), \'-1,\',1,1) > 0)';
        $expected .= ' OR (instr(LOWER("tt_content"."fe_group"), \',-1\',1,1) > 0)';
        $expected .= ' OR "tt_content"."fe_group" = \'-1\'))';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    ///////////////////////////////////////
    // Tests concerning DB management
    ///////////////////////////////////////
    /**
     * @test
     * @see http://forge.typo3.org/issues/21616
     */
    public function notNullableColumnsWithDefaultEmptyStringAreCreatedAsNullable()
    {
        $parseString = '
			CREATE TABLE tx_realurl_uniqalias (
				uid int(11) NOT NULL auto_increment,
				tstamp int(11) DEFAULT \'0\' NOT NULL,
				tablename varchar(60) DEFAULT \'\' NOT NULL,
				field_alias varchar(255) DEFAULT \'\' NOT NULL,
				field_id varchar(60) DEFAULT \'\' NOT NULL,
				value_alias varchar(255) DEFAULT \'\' NOT NULL,
				value_id int(11) DEFAULT \'0\' NOT NULL,
				lang int(11) DEFAULT \'0\' NOT NULL,
				expire int(11) DEFAULT \'0\' NOT NULL,

				PRIMARY KEY (uid),
				KEY tablename (tablename),
				KEY bk_realurl01 (field_alias,field_id,value_id,lang,expire),
				KEY bk_realurl02 (tablename,field_alias,field_id,value_alias(220),expire)
			);
		';
        $components = $this->subject->SQLparser->_callRef('parseCREATETABLE', $parseString);
        $this->assertTrue(is_array($components), 'Not an array: ' . $components);
        $sqlCommands = $this->subject->SQLparser->compileSQL($components);
        $this->assertTrue(is_array($sqlCommands), 'Not an array: ' . $sqlCommands);
        $this->assertEquals(6, count($sqlCommands));
        $expected = $this->cleanSql('
			CREATE TABLE "tx_realurl_uniqalias" (
				"uid" NUMBER(20) NOT NULL,
				"tstamp" NUMBER(20) DEFAULT 0,
				"tablename" VARCHAR(60) DEFAULT \'\',
				"field_alias" VARCHAR(255) DEFAULT \'\',
				"field_id" VARCHAR(60) DEFAULT \'\',
				"value_alias" VARCHAR(255) DEFAULT \'\',
				"value_id" NUMBER(20) DEFAULT 0,
				"lang" NUMBER(20) DEFAULT 0,
				"expire" NUMBER(20) DEFAULT 0,
				PRIMARY KEY ("uid")
			)
		');
        $this->assertEquals($expected, $this->cleanSql($sqlCommands[0]));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/20470
     * @see http://forge.typo3.org/issues/21616
     */
    public function defaultValueIsProperlyQuotedInCreateTable()
    {
        $parseString = '
			CREATE TABLE tx_test (
				uid int(11) NOT NULL auto_increment,
				lastname varchar(60) DEFAULT \'unknown\' NOT NULL,
				firstname varchar(60) DEFAULT \'\' NOT NULL,
				language varchar(2) NOT NULL,
				tstamp int(11) DEFAULT \'0\' NOT NULL,

				PRIMARY KEY (uid),
				KEY name (name)
			);
		';
        $components = $this->subject->SQLparser->_callRef('parseCREATETABLE', $parseString);
        $this->assertTrue(is_array($components), 'Not an array: ' . $components);
        $sqlCommands = $this->subject->SQLparser->compileSQL($components);
        $this->assertTrue(is_array($sqlCommands), 'Not an array: ' . $sqlCommands);
        $this->assertEquals(4, count($sqlCommands));
        $expected = $this->cleanSql('
			CREATE TABLE "tx_test" (
				"uid" NUMBER(20) NOT NULL,
				"lastname" VARCHAR(60) DEFAULT \'unknown\',
				"firstname" VARCHAR(60) DEFAULT \'\',
				"language" VARCHAR(2) DEFAULT \'\',
				"tstamp" NUMBER(20) DEFAULT 0,
				PRIMARY KEY ("uid")
			)
		');
        $this->assertEquals($expected, $this->cleanSql($sqlCommands[0]));
    }

    ///////////////////////////////////////
    // Tests concerning subqueries
    ///////////////////////////////////////
    /**
     * @test
     * @see http://forge.typo3.org/issues/21688
     */
    public function inWhereClauseWithSubqueryIsProperlyQuoted()
    {
        $result = $this->subject->SELECTquery('*', 'tx_crawler_queue', 'process_id IN (SELECT process_id FROM tx_crawler_process WHERE active=0 AND deleted=0)');
        $expected = 'SELECT * FROM "tx_crawler_queue" WHERE "process_id" IN (SELECT "process_id" FROM "tx_crawler_process" WHERE "active" = 0 AND "deleted" = 0)';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/21688
     */
    public function subqueryIsRemappedForInWhereClause()
    {
        $selectFields = '*';
        $fromTables = 'tx_crawler_queue';
        $whereClause = 'process_id IN (SELECT process_id FROM tx_crawler_process WHERE active=0 AND deleted=0)';
        $groupBy = '';
        $orderBy = '';
        $remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
        $result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
        $expected = 'SELECT * FROM "tx_crawler_queue" WHERE "process_id" IN (SELECT "ps_id" FROM "tx_crawler_ps" WHERE "is_active" = 0 AND "deleted" = 0)';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/21718
     */
    public function cachingFrameworkQueryIsSupported()
    {
        $currentTime = time();
        $result = $this->subject->DELETEquery('cachingframework_cache_hash_tags', 'identifier IN (' . $this->subject->SELECTsubquery('identifier', 'cachingframework_cache_pages', ('crdate + lifetime < ' . $currentTime . ' AND lifetime > 0')) . ')');
        $expected = 'DELETE FROM "cachingframework_cache_hash_tags" WHERE "identifier" IN (';
        $expected .= 'SELECT "identifier" FROM "cachingframework_cache_pages" WHERE "crdate"+"lifetime" < ' . $currentTime . ' AND "lifetime" > 0';
        $expected .= ')';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/21718
     */
    public function cachingFrameworkQueryIsRemapped()
    {
        $currentTime = time();
        $table = 'cachingframework_cache_hash_tags';
        $where = 'identifier IN (' . $this->subject->SELECTsubquery('identifier', 'cachingframework_cache_pages', ('crdate + lifetime < ' . $currentTime . ' AND lifetime > 0')) . ')';

        // Perform remapping (as in method exec_DELETEquery)
        $tableArray = $this->subject->_call('map_needMapping', $table);
        // Where clause:
        $whereParts = $this->subject->SQLparser->parseWhereClause($where);
        $this->subject->_callRef('map_sqlParts', $whereParts, $tableArray[0]['table']);
        $where = $this->subject->SQLparser->compileWhereClause($whereParts, false);
        // Table name:
        if ($this->subject->mapping[$table]['mapTableName']) {
            $table = $this->subject->mapping[$table]['mapTableName'];
        }

        $result = $this->subject->DELETEquery($table, $where);
        $expected = 'DELETE FROM "cf_cache_hash_tags" WHERE "identifier" IN (';
        $expected .= 'SELECT "identifier" FROM "cf_cache_pages" WHERE "crdate"+"lifetime" < ' . $currentTime . ' AND "lifetime" > 0';
        $expected .= ')';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/21688
     */
    public function existsWhereClauseIsProperlyQuoted()
    {
        $result = $this->subject->SELECTquery('*', 'tx_crawler_process', 'active = 0 AND NOT EXISTS (' . $this->subject->SELECTsubquery('*', 'tx_crawler_queue', 'tx_crawler_queue.process_id = tx_crawler_process.process_id AND tx_crawler_queue.exec_time = 0)') . ')');
        $expected = 'SELECT * FROM "tx_crawler_process" WHERE "active" = 0 AND NOT EXISTS (';
        $expected .= 'SELECT * FROM "tx_crawler_queue" WHERE "tx_crawler_queue"."process_id" = "tx_crawler_process"."process_id" AND "tx_crawler_queue"."exec_time" = 0';
        $expected .= ')';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/21688
     */
    public function subqueryIsRemappedForExistsWhereClause()
    {
        $selectFields = '*';
        $fromTables = 'tx_crawler_process';
        $whereClause = 'active = 0 AND NOT EXISTS (' . $this->subject->SELECTsubquery('*', 'tx_crawler_queue', 'tx_crawler_queue.process_id = tx_crawler_process.process_id AND tx_crawler_queue.exec_time = 0') . ')';
        $groupBy = '';
        $orderBy = '';
        $remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);

        $result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
        $expected = 'SELECT * FROM "tx_crawler_ps" WHERE "is_active" = 0 AND NOT EXISTS (';
        $expected .= 'SELECT * FROM "tx_crawler_queue" WHERE "tx_crawler_queue"."process_id" = "tx_crawler_ps"."ps_id" AND "tx_crawler_queue"."exec_time" = 0';
        $expected .= ')';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    ///////////////////////////////////////
    // Tests concerning advanced operators
    ///////////////////////////////////////
    /**
     * @test
     * @see http://forge.typo3.org/issues/21903
     */
    public function caseStatementIsProperlyQuoted()
    {
        $result = $this->subject->SELECTquery('process_id, CASE active' . ' WHEN 1 THEN ' . $this->subject->fullQuoteStr('one', 'tx_crawler_process') . ' WHEN 2 THEN ' . $this->subject->fullQuoteStr('two', 'tx_crawler_process') . ' ELSE ' . $this->subject->fullQuoteStr('out of range', 'tx_crawler_process') . ' END AS number', 'tx_crawler_process', '1=1');
        $expected = 'SELECT "process_id", CASE "active" WHEN 1 THEN \'one\' WHEN 2 THEN \'two\' ELSE \'out of range\' END AS "number" FROM "tx_crawler_process" WHERE 1 = 1';

        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/21903
     */
    public function caseStatementIsProperlyRemapped()
    {
        $selectFields = 'process_id, CASE active' . ' WHEN 1 THEN ' . $this->subject->fullQuoteStr('one', 'tx_crawler_process') . ' WHEN 2 THEN ' . $this->subject->fullQuoteStr('two', 'tx_crawler_process') . ' ELSE ' . $this->subject->fullQuoteStr('out of range', 'tx_crawler_process') . ' END AS number';
        $fromTables = 'tx_crawler_process';
        $whereClause = '1=1';
        $groupBy = '';
        $orderBy = '';
        $remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);

        $result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
        $expected = 'SELECT "ps_id", CASE "is_active" WHEN 1 THEN \'one\' WHEN 2 THEN \'two\' ELSE \'out of range\' END AS "number" ';
        $expected .= 'FROM "tx_crawler_ps" WHERE 1 = 1';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/21903
     */
    public function caseStatementWithExternalTableIsProperlyRemapped()
    {
        $selectFields = 'process_id, CASE tt_news.uid' . ' WHEN 1 THEN ' . $this->subject->fullQuoteStr('one', 'tt_news') . ' WHEN 2 THEN ' . $this->subject->fullQuoteStr('two', 'tt_news') . ' ELSE ' . $this->subject->fullQuoteStr('out of range', 'tt_news') . ' END AS number';
        $fromTables = 'tx_crawler_process, tt_news';
        $whereClause = '1=1';
        $groupBy = '';
        $orderBy = '';
        $remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);

        $result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
        $expected = 'SELECT "ps_id", CASE "ext_tt_news"."news_uid" WHEN 1 THEN \'one\' WHEN 2 THEN \'two\' ELSE \'out of range\' END AS "number" ';
        $expected .= 'FROM "tx_crawler_ps", "ext_tt_news" WHERE 1 = 1';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/21902
     */
    public function locateStatementIsProperlyQuoted()
    {
        $result = $this->subject->SELECTquery('*, CASE WHEN' . ' LOCATE(' . $this->subject->fullQuoteStr('(fce)', 'tx_templavoila_tmplobj') . ', datastructure)>0 THEN 2' . ' ELSE 1' . ' END AS scope', 'tx_templavoila_tmplobj', '1=1');
        $expected = 'SELECT *, CASE WHEN INSTR("datastructure", \'(fce)\') > 0 THEN 2 ELSE 1 END AS "scope" FROM "tx_templavoila_tmplobj" WHERE 1 = 1';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/21902
     */
    public function locateStatementWithPositionIsProperlyQuoted()
    {
        $result = $this->subject->SELECTquery('*, CASE WHEN' . ' LOCATE(' . $this->subject->fullQuoteStr('(fce)', 'tx_templavoila_tmplobj') . ', datastructure, 4)>0 THEN 2' . ' ELSE 1' . ' END AS scope', 'tx_templavoila_tmplobj', '1=1');
        $expected = 'SELECT *, CASE WHEN INSTR("datastructure", \'(fce)\', 4) > 0 THEN 2 ELSE 1 END AS "scope" FROM "tx_templavoila_tmplobj" WHERE 1 = 1';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/17552
     */
    public function IfNullIsProperlyRemapped()
    {
        $result = $this->subject->SELECTquery('*', 'tt_news_cat_mm', 'IFNULL(tt_news_cat_mm.uid_foreign,0) IN (21,22)');
        $expected = 'SELECT * FROM "tt_news_cat_mm" WHERE NVL("tt_news_cat_mm"."uid_foreign", 0) IN (21,22)';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/23087
     */
    public function findInSetIsProperlyRemapped()
    {
        $result = $this->subject->SELECTquery('*', 'fe_users', 'FIND_IN_SET(10, usergroup)');
        $expected = 'SELECT * FROM "fe_users" WHERE \',\'||"usergroup"||\',\' LIKE \'%,10,%\'';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/23087
     */
    public function findInSetFieldIsProperlyRemapped()
    {
        $selectFields = 'fe_group';
        $fromTables = 'tt_news';
        $whereClause = 'FIND_IN_SET(10, fe_group)';
        $groupBy = '';
        $orderBy = '';
        $remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);

        $result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
        $expected = 'SELECT "usergroup" FROM "ext_tt_news" WHERE \',\'||"ext_tt_news"."usergroup"||\',\' LIKE \'%,10,%\'';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/22959
     */
    public function listQueryIsProperlyRemapped()
    {
        $result = $this->subject->SELECTquery('*', 'fe_users', $this->subject->listQuery('usergroup', 10, 'fe_users'));
        $expected = 'SELECT * FROM "fe_users" WHERE \',\'||"usergroup"||\',\' LIKE \'%,10,%\'';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     * @see http://forge.typo3.org/issues/21514
     */
    public function likeBinaryOperatorIsRemoved()
    {
        $result = $this->subject->SELECTquery('*', 'tt_content', 'bodytext LIKE BINARY \'test\'');
        $expected = 'SELECT * FROM "tt_content" WHERE (dbms_lob.instr("bodytext", \'test\',1,1) > 0)';
        $this->assertEquals($expected, $this->cleanSql($result));
    }

    /**
     * @test
     */
    public function expressionListWithNotInIsConcatenatedWithAnd()
    {
        $listMaxExpressions = 1000;

        $mockSpecificsOci8 = $this->getAccessibleMock(\TYPO3\CMS\Dbal\Database\Specifics\Oci8Specifics::class, [], [], '', false);
        $mockSpecificsOci8->expects($this->any())->method('getSpecific')->will($this->returnValue($listMaxExpressions));

        $items = range(0, 1250);
        $where = 'uid NOT IN(' . implode(',', $items) . ')';
        $result = $this->subject->SELECTquery('*', 'tt_content', $where);

        $chunks = array_chunk($items, $listMaxExpressions);
        $whereExpr = [];
        foreach ($chunks as $chunk) {
            $whereExpr[] = '"uid" NOT IN (' . implode(',', $chunk) . ')';
        }

        /**
         * $expectedWhere:
         * (
         *        "uid" NOT IN (1,2,3,4,...,1000)
         *    AND "uid" NOT IN (1001,1002,...,1250)
         * )
         */
        $expectedWhere = '(' . implode(' AND ', $whereExpr) . ')';
        $expectedQuery = 'SELECT * FROM "tt_content" WHERE ' . $expectedWhere;
        $this->assertEquals($expectedQuery, $this->cleanSql($result));
    }

    /**
     * @test
     */
    public function expressionListWithInIsConcatenatedWithOr()
    {
        $listMaxExpressions = 1000;

        $mockSpecificsOci8 = $this->getAccessibleMock(\TYPO3\CMS\Dbal\Database\Specifics\Oci8Specifics::class, [], [], '', false);
        $mockSpecificsOci8->expects($this->any())->method('getSpecific')->will($this->returnValue($listMaxExpressions));

        $items = range(0, 1250);
        $where = 'uid IN(' . implode(',', $items) . ')';
        $result = $this->subject->SELECTquery('*', 'tt_content', $where);

        $chunks = array_chunk($items, $listMaxExpressions);
        $whereExpr = [];
        foreach ($chunks as $chunk) {
            $whereExpr[] = '"uid" IN (' . implode(',', $chunk) . ')';
        }

        /**
         * $expectedWhere:
         * (
         *        "uid" IN (1,2,3,4,...,1000)
         *     OR "uid" IN (1001,1002,...,1250)
         * )
         */
        $expectedWhere = '(' . implode(' OR ', $whereExpr) . ')';
        $expectedQuery = 'SELECT * FROM "tt_content" WHERE ' . $expectedWhere;
        $this->assertEquals($expectedQuery, $this->cleanSql($result));
    }

    /**
     * @test
     */
    public function expressionListIsUnchanged()
    {
        $listMaxExpressions = 1000;

        $mockSpecificsOci8 = $this->getAccessibleMock(\TYPO3\CMS\Dbal\Database\Specifics\Oci8Specifics::class, [], [], '', false);
        $mockSpecificsOci8->expects($this->any())->method('getSpecific')->will($this->returnValue($listMaxExpressions));

        $result = $this->subject->SELECTquery('*', 'tt_content', 'uid IN (0,1,2,3,4,5,6,7,8,9,10)');

        $expectedQuery = 'SELECT * FROM "tt_content" WHERE "uid" IN (0,1,2,3,4,5,6,7,8,9,10)';
        $this->assertEquals($expectedQuery, $this->cleanSql($result));
    }

    /**
     * @test
     */
    public function expressionListBracesAreSetCorrectly()
    {
        $listMaxExpressions = 1000;

        $mockSpecificsOci8 = $this->getAccessibleMock(\TYPO3\CMS\Dbal\Database\Specifics\Oci8Specifics::class, [], [], '', false);
        $mockSpecificsOci8->expects($this->any())->method('getSpecific')->will($this->returnValue($listMaxExpressions));

        $items = range(0, 1250);
        $where = 'uid = 1981 AND uid IN(' . implode(',', $items) . ') OR uid = 42';
        $result = $this->subject->SELECTquery('uid, pid', 'tt_content', $where);

        $chunks = array_chunk($items, $listMaxExpressions);
        $whereExpr = [];
        foreach ($chunks as $chunk) {
            $whereExpr[] = '"uid" IN (' . implode(',', $chunk) . ')';
        }

        /**
         * $expectedWhere:
         * "uid" = 1981 AND (
         *        "uid" IN (1,2,3,4,...,1000)
         *     OR "uid" IN (1001,1002,...,1250)
         * ) OR "uid" = 42
         */
        $expectedWhere = '"uid" = 1981 AND (' . implode(' OR ', $whereExpr) . ') OR "uid" = 42';
        $expectedQuery = 'SELECT "uid", "pid" FROM "tt_content" WHERE ' . $expectedWhere;
        $this->assertEquals($expectedQuery, $this->cleanSql($result));
    }

    /**
     * @test
     */
    public function multipleExpressiosInWhereClauseAreBracedCorrectly()
    {
        $listMaxExpressions = 1000;

        $mockSpecificsOci8 = $this->getAccessibleMock(\TYPO3\CMS\Dbal\Database\Specifics\Oci8Specifics::class, [], [], '', false);
        $mockSpecificsOci8->expects($this->any())->method('getSpecific')->will($this->returnValue($listMaxExpressions));

        $INitems = range(0, 1250);
        $NOTINItems = range(0, 1001);
        $where = 'uid = 1981 AND uid IN(' . implode(',', $INitems) . ') OR uid = 42 AND uid NOT IN(' . implode(',', $NOTINItems) . ')';
        $result = $this->subject->SELECTquery('uid, pid', 'tt_content', $where);

        $chunks = array_chunk($INitems, $listMaxExpressions);
        $INItemsWhereExpr = [];
        foreach ($chunks as $chunk) {
            $INItemsWhereExpr[] = '"uid" IN (' . implode(',', $chunk) . ')';
        }

        $chunks = array_chunk($NOTINItems, $listMaxExpressions);
        $NOTINItemsWhereExpr = [];
        foreach ($chunks as $chunk) {
            $NOTINItemsWhereExpr[] = '"uid" NOT IN (' . implode(',', $chunk) . ')';
        }

        /**
         * $expectedWhere:
         * "uid" = 1981 AND (
         *        "uid" IN (1,2,3,4,...,1000)
         *     OR "uid" IN (1001,1002,...,1250)
         * ) OR "uid" = 42 AND (
         *        "uid" NOT IN (1,2,3,4,...,1000)
         *    AND "uid" NOT IN (1001)
         * )
         */
        $expectedWhere = '"uid" = 1981 AND (' . implode(' OR ', $INItemsWhereExpr) . ') OR "uid" = 42 AND (' . implode(' AND ', $NOTINItemsWhereExpr) . ')';
        $expectedQuery = 'SELECT "uid", "pid" FROM "tt_content" WHERE ' . $expectedWhere;
        $this->assertEquals($expectedQuery, $this->cleanSql($result));
    }
}
