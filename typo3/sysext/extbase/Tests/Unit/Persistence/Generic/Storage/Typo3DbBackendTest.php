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

namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic\Storage;

use Doctrine\DBAL\Result;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractValueObject;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Service\CacheService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class Typo3DbBackendTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * Due to nested PageRepository / FrontendRestriction Container issues, the Context object is set
     * @var bool
     */
    protected $resetSingletonInstances = true;

    /**
     * @return array
     */
    public function uidOfAlreadyPersistedValueObjectIsDeterminedCorrectlyDataProvider(): array
    {
        return [
            'isFrontendEnvironment' => [true],
            'isBackendEnvironment' => [false],
        ];
    }

    /**
     * @test
     * @dataProvider uidOfAlreadyPersistedValueObjectIsDeterminedCorrectlyDataProvider
     * @todo: This mocks WAY too much - drop or create functional to be useful
     */
    public function uidOfAlreadyPersistedValueObjectIsDeterminedCorrectly(bool $isFrontendEnvironment): void
    {
        $mockValueObject = $this->getMockBuilder(AbstractValueObject::class)
            ->onlyMethods(['_getProperties'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockValueObject->expects(self::once())->method('_getProperties')
            ->willReturn(['propertyName' => 'propertyValue']);
        $mockColumnMap = $this->getMockBuilder(DataMap::class)
            ->onlyMethods(['isPersistableProperty'])
            ->addMethods(['getColumnName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockColumnMap->method('getColumnName')->willReturn('column_name');
        $tableName = 'tx_foo_table';
        $mockDataMap = $this->getMockBuilder(DataMap::class)
            ->onlyMethods(['isPersistableProperty', 'getColumnMap', 'getTableName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockDataMap->method('isPersistableProperty')->willReturn(true);
        $mockDataMap->method('getColumnMap')->willReturn($mockColumnMap);
        $mockDataMap->method('getTableName')->willReturn($tableName);
        $mockDataMapper = $this->getMockBuilder(DataMapper::class)
            ->onlyMethods(['getDataMap', 'getPlainValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockDataMapper->expects(self::once())->method('getDataMap')
            ->willReturn($mockDataMap);
        $mockDataMapper->expects(self::once())->method('getPlainValue')
            ->willReturn('plainPropertyValue');
        $expectedUid = 52;

        $expressionBuilderProphet = $this->prophesize(ExpressionBuilder::class);
        $expressionBuilderProphet->eq(Argument::cetera())->willReturn('1 = 1');
        $queryResultProphet = $this->prophesize(Result::class);
        $queryResultProphet->fetchOne(Argument::cetera())->willReturn($expectedUid);
        $queryBuilderProphet = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphet->executeQuery()->willReturn($queryResultProphet->reveal());
        $queryBuilderProphet->expr()->willReturn($expressionBuilderProphet->reveal());
        $queryBuilderProphet->createNamedParameter(Argument::cetera())->willReturnArgument(0);
        $queryBuilderProphet->select('uid')->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->from($tableName)->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->where(Argument::cetera())->willReturn($queryBuilderProphet->reveal());
        $connectionPoolProphet = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphet->getQueryBuilderForTable(Argument::cetera())->willReturn($queryBuilderProphet->reveal());

        if ($isFrontendEnvironment) {
            $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        } else {
            $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        }

        if ($isFrontendEnvironment) {
            $queryBuilderProphet->setRestrictions(Argument::type(FrontendRestrictionContainer::class))
                ->shouldBeCalled();
        }

        GeneralUtility::addInstance(DataMapper::class, $mockDataMapper);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());
        $subject = new Typo3DbBackend(
            $this->prophesize(CacheService::class)->reveal()
        );
        $result = $subject->getUidOfAlreadyPersistedValueObject($mockValueObject);
        self::assertSame($expectedUid, $result);
    }

    /**
     * @test
     * @todo: This mocks WAY too much - drop or create functional to be useful
     */
    public function overlayLanguageAndWorkspaceChangesUidIfInPreview(): void
    {
        $comparisonRow = [
            'uid' => '42',
            'pid' => '42',
            '_ORIG_uid' => '43',
        ];
        $row = [
            'uid' => '42',
            'pid' => '42',
        ];
        $workspaceVersion = [
            'uid' => '43',
            'pid' => '42',
        ];
        $mockQuerySettings = $this->getMockBuilder(Typo3QuerySettings::class)
            ->addMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();

        $workspaceUid = 2;

        $sourceMock = new \TYPO3\CMS\Extbase\Persistence\Generic\Qom\Selector('tx_foo', 'Tx_Foo');
        $context = new Context([
            'workspace' => new WorkspaceAspect($workspaceUid),
        ]);
        $pageRepositoryMock = $this->getMockBuilder(PageRepository::class)
            ->onlyMethods(['getWorkspaceVersionOfRecord'])
            ->setConstructorArgs([$context])
            ->getMock();
        $this->container = $this->createMock(ContainerInterface::class);
        $query = new Query($this->createMock(DataMapFactory::class), $this->createMock(PersistenceManagerInterface::class), $this->createMock(QueryObjectModelFactory::class), $this->container);
        $query->setQuerySettings($mockQuerySettings);
        $pageRepositoryMock->expects(self::once())->method('getWorkspaceVersionOfRecord')->with($workspaceUid, 'tx_foo', '42')->willReturn($workspaceVersion);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        GeneralUtility::setSingletonInstance(Context::class, $context);
        GeneralUtility::addInstance(PageRepository::class, $pageRepositoryMock);
        $mockTypo3DbBackend = $this->getAccessibleMock(Typo3DbBackend::class, ['dummy'], [], '', false);
        self::assertSame([$comparisonRow], $mockTypo3DbBackend->_call('overlayLanguageAndWorkspace', $sourceMock, [$row], $query));
    }
}
