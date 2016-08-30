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
class QueryFactoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var string
     */
    protected $className = 'Vendor\\Ext\\Domain\\Model\\ClubMate';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory
     */
    protected $queryFactory = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataMapper = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataMap;

    protected function setUp()
    {
        $this->dataMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap::class, ['getIsStatic', 'getRootLevel'], ['Vendor\\Ext\\Domain\\Model\\ClubMate', 'tx_ext_domain_model_clubmate']);

        $this->queryFactory = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory::class, ['dummy']);
        $this->queryFactory->_set('configurationManager',
            $this->getMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class)
        );

        $this->dataMapper = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class, ['getDataMap', 'convertClassNameToTableName']);
        $this->dataMapper->expects($this->any())->method('getDataMap')->will($this->returnValue($this->dataMap));
        $this->queryFactory->_set('dataMapper', $this->dataMapper);
    }

    public function getStaticAndRootLevelAndExpectedResult()
    {
        return [
            'Respect storage page is set when entity is neither marked as static nor as rootLevel.' => [false, false, true],
            'Respect storage page is set when entity is marked as static and rootLevel.' => [true, true, false],
            'Respect storage page is set when entity is marked as static but not rootLevel.' => [true, false, false],
            'Respect storage page is set when entity is not marked as static but as rootLevel.' => [false, true, false],
        ];
    }

    /**
     * @param bool $static
     * @param bool $rootLevel
     * @param bool $expectedResult
     *
     * @dataProvider getStaticAndRootLevelAndExpectedResult
     * @test
     */
    public function createDoesNotRespectStoragePageIfStaticOrRootLevelIsTrue($static, $rootLevel, $expectedResult)
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject $objectManager */
        $objectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->queryFactory->_set('objectManager', $objectManager);

        $this->dataMap->expects($this->any())->method('getIsStatic')->will($this->returnValue($static));
        $this->dataMap->expects($this->any())->method('getRootLevel')->will($this->returnValue($rootLevel));

        $query = $this->getMock(\TYPO3\CMS\Extbase\Persistence\QueryInterface::class);
        $objectManager->expects($this->at(0))->method('get')
            ->with(\TYPO3\CMS\Extbase\Persistence\QueryInterface::class)
            ->will($this->returnValue($query));

        $querySettings = new \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings();
        $objectManager->expects($this->at(1))->method('get')
            ->with(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::class)
            ->will($this->returnValue($querySettings));
        $query->expects($this->once())->method('setQuerySettings')->with($querySettings);
        $this->queryFactory->create($this->className);

        $this->assertSame(
            $expectedResult,
            $querySettings->getRespectStoragePage()
        );
    }
}
