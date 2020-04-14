<?php

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

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class QueryTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Query|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $query;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface
     */
    protected $querySettings;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory
     */
    protected $dataMapFactory;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->query = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Query::class, ['getSelectorName'], ['someType']);
        $this->querySettings = $this->createMock(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::class);
        $this->query->_set('querySettings', $this->querySettings);
        $this->persistenceManager = $this->createMock(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);
        $this->query->_set('persistenceManager', $this->persistenceManager);
        $this->dataMapFactory = $this->createMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class);
        $this->query->_set('dataMapFactory', $this->dataMapFactory);
    }

    /**
     * @test
     */
    public function executeReturnsQueryResultInstanceAndInjectsItself()
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject $objectManager */
        $objectManager = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->query->_set('objectManager', $objectManager);
        $queryResult = $this->createMock(\TYPO3\CMS\Extbase\Persistence\Generic\QueryResult::class);
        $objectManager->expects(self::once())->method('get')->with(\TYPO3\CMS\Extbase\Persistence\QueryResultInterface::class, $this->query)->willReturn($queryResult);
        $actualResult = $this->query->execute();
        self::assertSame($queryResult, $actualResult);
    }

    /**
     * @test
     */
    public function executeReturnsRawObjectDataIfReturnRawQueryResultIsSet()
    {
        $this->persistenceManager->expects(self::once())->method('getObjectDataByQuery')->with($this->query)->willReturn('rawQueryResult');
        $expectedResult = 'rawQueryResult';
        $actualResult = $this->query->execute(true);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function setLimitAcceptsOnlyIntegers()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1245071870);
        $this->query->setLimit(1.5);
    }

    /**
     * @test
     */
    public function setLimitRejectsIntegersLessThanOne()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1245071870);
        $this->query->setLimit(0);
    }

    /**
     * @test
     */
    public function setOffsetAcceptsOnlyIntegers()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1245071872);
        $this->query->setOffset(1.5);
    }

    /**
     * @test
     */
    public function setOffsetRejectsIntegersLessThanZero()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1245071872);
        $this->query->setOffset(-1);
    }

    /**
     * @return array
     */
    public function equalsForCaseSensitiveFalseLowercasesOperandProvider()
    {
        return [
            'Polish alphabet' => ['name', 'ĄĆĘŁŃÓŚŹŻABCDEFGHIJKLMNOPRSTUWYZQXVąćęłńóśźżabcdefghijklmnoprstuwyzqxv', 'ąćęłńóśźżabcdefghijklmnoprstuwyzqxvąćęłńóśźżabcdefghijklmnoprstuwyzqxv'],
            'German alphabet' => ['name', 'ßÜÖÄüöä', 'ßüöäüöä'],
            'Greek alphabet' => ['name', 'Τάχιστη αλώπηξ βαφής ψημένη γη', 'τάχιστη αλώπηξ βαφής ψημένη γη'],
            'Russian alphabet' => ['name', 'АВСТРАЛИЯавстралия', 'австралияавстралия']
        ];
    }

    /**
     * Checks if equals condition makes utf-8 argument lowercase correctly
     *
     * @test
     * @dataProvider equalsForCaseSensitiveFalseLowercasesOperandProvider
     * @param string $propertyName The name of the property to compare against
     * @param mixed $operand The value to compare with
     * @param string $expectedOperand
     */
    public function equalsForCaseSensitiveFalseLowercasesOperand($propertyName, $operand, $expectedOperand)
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject $objectManager */
        $objectManager = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface $dynamicOperand */
        $dynamicOperand = $this->createMock(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\PropertyValueInterface::class);
        $objectManager->expects(self::any())->method('get')->willReturn($dynamicOperand);
        /** @var $qomFactory \TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory */
        $qomFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory::class, ['comparison']);
        $qomFactory->_set('objectManager', $objectManager);
        $qomFactory->expects(self::once())->method('comparison')->with(self::anything(), self::anything(), $expectedOperand);
        $this->query->expects(self::any())->method('getSelectorName')->willReturn('someSelector');
        $this->query->_set('qomFactory', $qomFactory);
        $this->query->equals($propertyName, $operand, false);
    }
}
