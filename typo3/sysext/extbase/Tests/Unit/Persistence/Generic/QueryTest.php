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

namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\Comparison;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\LogicalAnd;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\LogicalOr;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\PropertyValue;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class QueryTest extends UnitTestCase
{
    protected Query&MockObject&AccessibleObjectInterface $query;
    protected QuerySettingsInterface $querySettings;
    protected PersistenceManagerInterface $persistenceManager;
    protected DataMapFactory $dataMapFactory;
    protected ContainerInterface $container;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->query = $this->getAccessibleMock(Query::class, ['getSelectorName'], [], '', false);
        $this->querySettings = $this->createMock(QuerySettingsInterface::class);
        $this->query->_set('querySettings', $this->querySettings);
        $this->persistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $this->query->_set('persistenceManager', $this->persistenceManager);
        $this->dataMapFactory = $this->createMock(DataMapFactory::class);
        $this->query->_set('dataMapFactory', $this->dataMapFactory);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->query->_set('container', $this->container);
    }

    #[Test]
    public function executeReturnsQueryResultInstanceAndInjectsItself(): void
    {
        $queryResult = $this->createMock(QueryResult::class);
        $this->container->expects($this->once())->method('get')->with(QueryResultInterface::class)->willReturn($queryResult);
        $actualResult = $this->query->execute();
        self::assertSame($queryResult, $actualResult);
    }

    #[Test]
    public function executeReturnsRawObjectDataIfReturnRawQueryResultIsSet(): void
    {
        $expectedResult = [];
        $this->persistenceManager->expects($this->once())->method('getObjectDataByQuery')->with($this->query)->willReturn($expectedResult);
        $actualResult = $this->query->execute(true);
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function setLimitAcceptsOnlyIntegers(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1245071870);
        $this->query->setLimit(1.5);
    }

    #[Test]
    public function setLimitRejectsIntegersLessThanOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1245071870);
        $this->query->setLimit(0);
    }

    #[Test]
    public function setLimitSetsLimit(): void
    {
        $this->query->setLimit(15);

        self::assertSame(
            15,
            $this->query->getLimit()
        );
    }

    #[Test]
    public function unsetLimitWillResetLimitToNull(): void
    {
        $this->query->setLimit(15);
        $this->query->unsetLimit();

        self::assertNull(
            $this->query->getLimit()
        );
    }

    #[Test]
    public function setOffsetAcceptsOnlyIntegers(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1245071872);
        $this->query->setOffset(1.5);
    }

    #[Test]
    public function setOffsetRejectsIntegersLessThanZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1245071872);
        $this->query->setOffset(-1);
    }

    public static function equalsForCaseSensitiveFalseLowercasesOperandProvider(): array
    {
        return [
            'Polish alphabet' => ['name', 'ĄĆĘŁŃÓŚŹŻABCDEFGHIJKLMNOPRSTUWYZQXVąćęłńóśźżabcdefghijklmnoprstuwyzqxv', 'ąćęłńóśźżabcdefghijklmnoprstuwyzqxvąćęłńóśźżabcdefghijklmnoprstuwyzqxv'],
            'German alphabet' => ['name', 'ßÜÖÄüöä', 'ßüöäüöä'],
            'Greek alphabet' => ['name', 'Τάχιστη αλώπηξ βαφής ψημένη γη', 'τάχιστη αλώπηξ βαφής ψημένη γη'],
            'Russian alphabet' => ['name', 'АВСТРАЛИЯавстралия', 'австралияавстралия'],
        ];
    }

    /**
     * Checks if equals condition makes utf-8 argument lowercase correctly
     *
     * @param string $propertyName The name of the property to compare against
     * @param mixed $operand The value to compare with
     */
    #[DataProvider('equalsForCaseSensitiveFalseLowercasesOperandProvider')]
    #[Test]
    public function equalsForCaseSensitiveFalseLowercasesOperand(string $propertyName, $operand, string $expectedOperand): void
    {
        $qomFactory = $this->getAccessibleMock(QueryObjectModelFactory::class, ['comparison']);
        $qomFactory->expects($this->once())->method('comparison')->with(self::anything(), self::anything(), $expectedOperand);
        $this->query->method('getSelectorName')->willReturn('someSelector');
        $this->query->_set('qomFactory', $qomFactory);
        $this->query->equals($propertyName, $operand, false);
    }

    #[Test]
    public function logicalAndSupportsMultipleConstraintsAsMethodArguments(): void
    {
        $subject = new Query(
            $this->createMock(DataMapFactory::class),
            $this->createMock(PersistenceManagerInterface::class),
            new QueryObjectModelFactory(),
            $this->createMock(ContainerInterface::class)
        );

        $constraint1 = new Comparison(new PropertyValue('propertyName1'), QueryInterface::OPERATOR_EQUAL_TO, 'value1');
        $constraint2 = new Comparison(new PropertyValue('propertyName2'), QueryInterface::OPERATOR_EQUAL_TO, 'value2');
        $constraint3 = new Comparison(new PropertyValue('propertyName3'), QueryInterface::OPERATOR_EQUAL_TO, 'value3');

        $logicalAnd = $subject->logicalAnd($constraint1, $constraint2, $constraint3);
        self::assertEquals(
            new LogicalAnd(new LogicalAnd($constraint1, $constraint2), $constraint3),
            $logicalAnd
        );
    }

    #[Test]
    public function logicalOrSupportsMultipleConstraintsAsMethodArguments(): void
    {
        $subject = new Query(
            $this->createMock(DataMapFactory::class),
            $this->createMock(PersistenceManagerInterface::class),
            new QueryObjectModelFactory(),
            $this->createMock(ContainerInterface::class)
        );

        $constraint1 = new Comparison(new PropertyValue('propertyName1'), QueryInterface::OPERATOR_EQUAL_TO, 'value1');
        $constraint2 = new Comparison(new PropertyValue('propertyName2'), QueryInterface::OPERATOR_EQUAL_TO, 'value2');
        $constraint3 = new Comparison(new PropertyValue('propertyName3'), QueryInterface::OPERATOR_EQUAL_TO, 'value3');

        $logicalOr = $subject->logicalOr($constraint1, $constraint2, $constraint3);
        self::assertEquals(
            new LogicalOr(new LogicalOr($constraint1, $constraint2), $constraint3),
            $logicalOr
        );
    }
}
