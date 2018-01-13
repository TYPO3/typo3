<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Redirects\Tests\Unit\FormDataProvider;

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

use Doctrine\DBAL\Driver\Statement;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\FormDataProvider\ValuePickerItemDataProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ValuePickerItemDataProviderTest extends UnitTestCase
{
    protected $sysRedirectResultSet = [
        'tableName' => 'sys_redirect',
        'processedTca' => [
            'columns' => [
                'source_host' => [
                    'config' => [
                        'valuePicker' => [
                            'items' => []
                        ]
                    ]
                ]
            ]
        ]
    ];

    /**
     * @test
     */
    public function addDataDoesNothingIfNoRedirectDataGiven()
    {
        $result = [
            'tableName' => 'tt_content',
        ];

        $valuePickerItemDataProvider = new ValuePickerItemDataProvider();
        $actualResult = $valuePickerItemDataProvider->addData($result);
        self::assertSame($result, $actualResult);
    }

    /**
     * @test
     */
    public function addDataAddsDomainNameAsKeyAndValueToRedirectValuePicker()
    {
        $statementProphecy = $this->setUpDatabase();
        $statementProphecy->fetchAll()->willReturn(
            [
                ['domainName' => 'foo.test'],
                ['domainName' => 'bar.test'],
            ]
        );
        $valuePickerItemDataProvider = new ValuePickerItemDataProvider();
        $actualResult = $valuePickerItemDataProvider->addData($this->sysRedirectResultSet);
        $expected = $this->sysRedirectResultSet;
        $expected['processedTca']['columns']['source_host']['config']['valuePicker']['items'] = [
            ['foo.test', 'foo.test'],
            ['bar.test', 'bar.test'],
        ];
        self::assertSame($expected, $actualResult);
    }

    /**
     * @test
     */
    public function addDataDoesNotChangeResultSetIfNoSysDomainsAreFound()
    {
        $statementProphecy = $this->setUpDatabase();
        $statementProphecy->fetchAll()->willReturn([]);
        $valuePickerItemDataProvider = new ValuePickerItemDataProvider();
        $actualResult = $valuePickerItemDataProvider->addData($this->sysRedirectResultSet);

        self::assertSame($this->sysRedirectResultSet, $actualResult);
    }

    /**
     * @return \Doctrine\DBAL\Driver\Statement|\Prophecy\Prophecy\ObjectProphecy
     */
    private function setUpDatabase(): ObjectProphecy
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphecy->getQueryBuilderForTable('sys_domain')->willReturn($queryBuilderProphecy->reveal());
        $queryRestrictionContainerProphecy = $this->prophesize(QueryRestrictionContainerInterface::class);
        $queryBuilderProphecy->getRestrictions()->willReturn($queryRestrictionContainerProphecy->reveal());
        $queryBuilderProphecy->select('domainName')->willReturn($queryBuilderProphecy->reveal());
        $queryBuilderProphecy->from('sys_domain')->willReturn($queryBuilderProphecy->reveal());
        $statementProphecy = $this->prophesize(Statement::class);
        $queryBuilderProphecy->execute()->willReturn($statementProphecy->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());
        return $statementProphecy;
    }
}
