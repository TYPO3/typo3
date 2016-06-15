<?php
declare (strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Database\Query\Restriction;

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

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Frontend\Page\PageRepository;

class FrontendRestrictionContainerTest extends AbstractRestrictionTestCase
{
    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
    }

    public function frontendStatesDataProvider()
    {
        return [
            'Live, no preview' => [
                'tableName' => 'aTable',
                'workspaceId' => 0,
                'workspacePreview' => false,
                'hiddenPagePreview' => false,
                'hiddenRecordPreview' => false,
                'feGroupList' => '0,-1',
                'expectedSQL' => '("aTable"."deleted" = 0) AND (("aTable"."t3ver_state" <= 0) AND ("aTable"."pid" <> -1)) AND ("aTable"."myHiddenField" = 0) AND ("aTable"."myStartTimeField" <= 42) AND (("aTable"."myEndTimeField" = 0) OR ("aTable"."myEndTimeField" > 42)) AND (("aTable"."myGroupField" IS NULL) OR ("aTable"."myGroupField" = \'\') OR ("aTable"."myGroupField" = \'0\') OR (FIND_IN_SET(\'0\', "aTable"."myGroupField")) OR (FIND_IN_SET(\'-1\', "aTable"."myGroupField")))'
            ],
            'Live, with hidden record preview' => [
                'tableName' => 'aTable',
                'workspaceId' => 0,
                'workspacePreview' => false,
                'hiddenPagePreview' => true,
                'hiddenRecordPreview' => true,
                'feGroupList' => '0,-1',
                'expectedSQL' => '("aTable"."deleted" = 0) AND (("aTable"."t3ver_state" <= 0) AND ("aTable"."pid" <> -1)) AND ("aTable"."myStartTimeField" <= 42) AND (("aTable"."myEndTimeField" = 0) OR ("aTable"."myEndTimeField" > 42)) AND (("aTable"."myGroupField" IS NULL) OR ("aTable"."myGroupField" = \'\') OR ("aTable"."myGroupField" = \'0\') OR (FIND_IN_SET(\'0\', "aTable"."myGroupField")) OR (FIND_IN_SET(\'-1\', "aTable"."myGroupField")))'
            ],
            'Workspace, with WS preview' => [
                'tableName' => 'aTable',
                'workspaceId' => 1,
                'workspacePreview' => true,
                'hiddenPagePreview' => false,
                'hiddenRecordPreview' => false,
                'feGroupList' => '0,-1',
                'expectedSQL' => '("aTable"."deleted" = 0) AND ((("aTable"."t3ver_wsid" = 0) OR ("aTable"."t3ver_wsid" = 1)) AND ("aTable"."pid" <> -1)) AND ("aTable"."myHiddenField" = 0) AND ("aTable"."myStartTimeField" <= 42) AND (("aTable"."myEndTimeField" = 0) OR ("aTable"."myEndTimeField" > 42)) AND (("aTable"."myGroupField" IS NULL) OR ("aTable"."myGroupField" = \'\') OR ("aTable"."myGroupField" = \'0\') OR (FIND_IN_SET(\'0\', "aTable"."myGroupField")) OR (FIND_IN_SET(\'-1\', "aTable"."myGroupField")))'
            ],
            'Workspace, with WS preview and hidden record preview' => [
                'tableName' => 'aTable',
                'workspaceId' => 1,
                'workspacePreview' => true,
                'hiddenPagePreview' => true,
                'hiddenRecordPreview' => true,
                'feGroupList' => '0,-1',
                'expectedSQL' => '("aTable"."deleted" = 0) AND ((("aTable"."t3ver_wsid" = 0) OR ("aTable"."t3ver_wsid" = 1)) AND ("aTable"."pid" <> -1)) AND ("aTable"."myStartTimeField" <= 42) AND (("aTable"."myEndTimeField" = 0) OR ("aTable"."myEndTimeField" > 42)) AND (("aTable"."myGroupField" IS NULL) OR ("aTable"."myGroupField" = \'\') OR ("aTable"."myGroupField" = \'0\') OR (FIND_IN_SET(\'0\', "aTable"."myGroupField")) OR (FIND_IN_SET(\'-1\', "aTable"."myGroupField")))'
            ],
            'Live page, no preview' => [
                'tableName' => 'pages',
                'workspaceId' => 0,
                'workspacePreview' => false,
                'hiddenPagePreview' => false,
                'hiddenRecordPreview' => false,
                'feGroupList' => '0,-1',
                'expectedSQL' => '("pages"."deleted" = 0) AND (("pages"."t3ver_state" <= 0) AND ("pages"."pid" <> -1)) AND ("pages"."hidden" = 0) AND ("pages"."starttime" <= 42) AND (("pages"."endtime" = 0) OR ("pages"."endtime" > 42)) AND (("pages"."fe_group" IS NULL) OR ("pages"."fe_group" = \'\') OR ("pages"."fe_group" = \'0\') OR (FIND_IN_SET(\'0\', "pages"."fe_group")) OR (FIND_IN_SET(\'-1\', "pages"."fe_group")))'
            ],
            'Live page, with hidden page preview' => [
                'tableName' => 'pages',
                'workspaceId' => 0,
                'workspacePreview' => false,
                'hiddenPagePreview' => true,
                'hiddenRecordPreview' => true,
                'feGroupList' => '0,-1',
                'expectedSQL' => '("pages"."deleted" = 0) AND (("pages"."t3ver_state" <= 0) AND ("pages"."pid" <> -1)) AND ("pages"."starttime" <= 42) AND (("pages"."endtime" = 0) OR ("pages"."endtime" > 42)) AND (("pages"."fe_group" IS NULL) OR ("pages"."fe_group" = \'\') OR ("pages"."fe_group" = \'0\') OR (FIND_IN_SET(\'0\', "pages"."fe_group")) OR (FIND_IN_SET(\'-1\', "pages"."fe_group")))'
            ],
            'Workspace page, with WS preview' => [
                'tableName' => 'pages',
                'workspaceId' => 1,
                'workspacePreview' => true,
                'hiddenPagePreview' => false,
                'hiddenRecordPreview' => false,
                'feGroupList' => '0,-1',
                'expectedSQL' => '("pages"."deleted" = 0) AND ("pages"."pid" <> -1) AND ("pages"."hidden" = 0) AND ("pages"."starttime" <= 42) AND (("pages"."endtime" = 0) OR ("pages"."endtime" > 42)) AND (("pages"."fe_group" IS NULL) OR ("pages"."fe_group" = \'\') OR ("pages"."fe_group" = \'0\') OR (FIND_IN_SET(\'0\', "pages"."fe_group")) OR (FIND_IN_SET(\'-1\', "pages"."fe_group")))'
            ],
            'Workspace page, with WS preview and hidden pages preview' => [
                'tableName' => 'pages',
                'workspaceId' => 1,
                'workspacePreview' => true,
                'hiddenPagePreview' => true,
                'hiddenRecordPreview' => true,
                'feGroupList' => '0,-1',
                'expectedSQL' => '("pages"."deleted" = 0) AND ("pages"."pid" <> -1) AND ("pages"."starttime" <= 42) AND (("pages"."endtime" = 0) OR ("pages"."endtime" > 42)) AND (("pages"."fe_group" IS NULL) OR ("pages"."fe_group" = \'\') OR ("pages"."fe_group" = \'0\') OR (FIND_IN_SET(\'0\', "pages"."fe_group")) OR (FIND_IN_SET(\'-1\', "pages"."fe_group")))'
            ],
        ];
    }

    /**
     * @param string $tableName
     * @param int $workspaceId
     * @param bool $workspacePreview
     * @param bool $hiddenPagePreview
     * @param bool $hiddenRecordPreview
     * @param string $feGroupList
     * @param string $expectedSQL
     *
     * @test
     * @dataProvider frontendStatesDataProvider
     */
    public function buildExpressionAddsCorrectClause(
        string $tableName,
        int $workspaceId,
        bool $workspacePreview,
        bool $hiddenPagePreview,
        bool $hiddenRecordPreview,
        string $feGroupList,
        string $expectedSQL
    ) {
        $GLOBALS['TCA'] = [
            'aTable' => [
                'ctrl' => [
                    'versioningWS' => 2,
                    'delete' => 'deleted',
                    'enablecolumns' => [
                        'disabled' => 'myHiddenField',
                        'starttime' => 'myStartTimeField',
                        'endtime' => 'myEndTimeField',
                        'fe_group' => 'myGroupField',
                    ],
                ],
            ],
            'pages' => array(
                'ctrl' => array(
                    'label' => 'title',
                    'tstamp' => 'tstamp',
                    'sortby' => 'sorting',
                    'type' => 'doktype',
                    'versioningWS' => true,
                    'origUid' => 't3_origuid',
                    'delete' => 'deleted',
                    'enablecolumns' => array(
                        'disabled' => 'hidden',
                        'starttime' => 'starttime',
                        'endtime' => 'endtime',
                        'fe_group' => 'fe_group'
                    ),
                ),
                'columns' => array()
            )
        ];

        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->versioningWorkspaceId = $workspaceId;
        $pageRepository->versioningPreview = $workspacePreview;

        $typoScriptFrontendController = new \stdClass();
        $typoScriptFrontendController->showHiddenPage = $hiddenPagePreview;
        $typoScriptFrontendController->showHiddenRecords = $hiddenRecordPreview;
        $typoScriptFrontendController->gr_list = $feGroupList;
        $typoScriptFrontendController->sys_page = $pageRepository;

        $dbMock = $this->getMockBuilder(DatabaseConnection::class)
            ->setMethods(['quoteStr'])
            ->getMock();
        $dbMock->expects($this->any())->method('quoteStr')->willReturnArgument(0);

        $GLOBALS['TSFE'] = $typoScriptFrontendController;
        $GLOBALS['SIM_ACCESS_TIME'] = 42;
        $GLOBALS['TYPO3_DB'] = $dbMock;

        $subject = new FrontendRestrictionContainer();
        $expression = $subject->buildExpression([$tableName => ''], $this->expressionBuilder);
        $this->assertSame($expectedSQL, (string)$expression);
    }
}
