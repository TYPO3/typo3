<?php

namespace TYPO3\CMS\Core\Tests\Unit\Preparations;

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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Preparations\TcaPreparation;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class TcaPreparationTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    public function prepareQuotingOfTableNamesAndColumnNamesDataProvider()
    {
        return [
            [
                [
                    'aTable' => [
                        'columns' => [
                            'foo' => [
                                'config' => [
                                    'type' => 'inline',
                                    'foreign_table_where' => 'AND {#tt_content}.{#CType} IN (\'text\',\'textpic\',\'textmedia\') ORDER BY {#tt_content}.{#CType} ASC',
                                    'MM_table_where' => 'AND {#uid_local} = ###REC_FIELD_category###',
                                    'search' => [
                                        'andWhere' => '{#CType}=\'text\' OR {#CType}=\'textpic\' OR {#CType}=\'textmedia\' AND {#title}=\'foo\'',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'foo' => [
                                'config' => [
                                    'type' => 'inline',
                                    'foreign_table_where' => 'AND `tt_content`.`CType` IN (\'text\',\'textpic\',\'textmedia\') ORDER BY `tt_content`.`CType` ASC',
                                    'MM_table_where' => 'AND `uid_local` = ###REC_FIELD_category###',
                                    'search' => [
                                        'andWhere' => '`CType`=\'text\' OR `CType`=\'textpic\' OR `CType`=\'textmedia\' AND `title`=\'foo\'',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider prepareQuotingOfTableNamesAndColumnNamesDataProvider
     */
    public function prepareQuotingOfTableNamesAndColumnNames(array $input, array $expected)
    {
        $connection = $this->prophesize(Connection::class);
        $connection->quoteIdentifier('tt_content')->willReturn('`tt_content`');
        $connection->quoteIdentifier('CType')->willReturn('`CType`');
        $connection->quoteIdentifier('uid_local')->willReturn('`uid_local`');
        $connection->quoteIdentifier('title')->willReturn('`title`');
        $connectionPool = $this->prophesize(ConnectionPool::class);
        $connectionPool->getConnectionForTable(Argument::any())->willReturn($connection->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool->reveal());
        $subject = new TcaPreparation();
        $this->assertEquals($expected, $subject->prepare($input));
    }
}
