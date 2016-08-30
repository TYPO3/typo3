<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic;

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
class QueryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Query
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
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface
     */
    protected $backend;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
     */
    protected $dataMapper;

    /**
     * Sets up this test case
     *
     * @return void
     */
    protected function setUp()
    {
        $this->query = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Query::class, ['dummy'], ['someType']);
        $this->querySettings = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::class);
        $this->query->_set('querySettings', $this->querySettings);
        $this->persistenceManager = $this->getMock(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);
        $this->backend = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface::class);
        $this->backend->expects($this->any())->method('getQomFactory')->will($this->returnValue(null));
        $this->persistenceManager->expects($this->any())->method('getBackend')->will($this->returnValue($this->backend));
        $this->query->_set('persistenceManager', $this->persistenceManager);
        $this->dataMapper = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class);
        $this->query->_set('dataMapper', $this->dataMapper);
    }

    /**
     * @test
     */
    public function executeReturnsQueryResultInstanceAndInjectsItself()
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject $objectManager */
        $objectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->query->_set('objectManager', $objectManager);
        $queryResult = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\QueryResult::class, [], [], '', false);
        $objectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Extbase\Persistence\QueryResultInterface::class, $this->query)->will($this->returnValue($queryResult));
        $actualResult = $this->query->execute();
        $this->assertSame($queryResult, $actualResult);
    }

    /**
     * @test
     */
    public function executeReturnsRawObjectDataIfReturnRawQueryResultIsSet()
    {
        $this->persistenceManager->expects($this->once())->method('getObjectDataByQuery')->with($this->query)->will($this->returnValue('rawQueryResult'));
        $expectedResult = 'rawQueryResult';
        $actualResult = $this->query->execute(true);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setLimitAcceptsOnlyIntegers()
    {
        $this->query->setLimit(1.5);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setLimitRejectsIntegersLessThanOne()
    {
        $this->query->setLimit(0);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setOffsetAcceptsOnlyIntegers()
    {
        $this->query->setOffset(1.5);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setOffsetRejectsIntegersLessThanZero()
    {
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
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject $objectManager */
        $objectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface $dynamicOperand */
        $dynamicOperand = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\PropertyValueInterface::class);
        $objectManager->expects($this->any())->method('get')->will($this->returnValue($dynamicOperand));
        /** @var $qomFactory \TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory */
        $qomFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory::class, ['comparison']);
        $qomFactory->_set('objectManager', $objectManager);
        $qomFactory->expects($this->once())->method('comparison')->with($this->anything(), $this->anything(), $expectedOperand);
        $this->query->_set('qomFactory', $qomFactory);
        $this->query->equals($propertyName, $operand, false);
    }
}
