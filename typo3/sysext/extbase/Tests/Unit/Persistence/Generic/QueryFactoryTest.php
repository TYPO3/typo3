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

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class QueryFactoryTest extends UnitTestCase
{
    /**
     * @var string
     */
    protected $className = 'Vendor\\Ext\\Domain\\Model\\ClubMate';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory
     */
    protected $queryFactory;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dataMapFactory;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dataMap;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject $objectManager */
        $this->objectManager = $this->createMock(ObjectManager::class);

        $this->dataMap = $this->getMockBuilder(DataMap::class)
            ->setMethods(['getIsStatic', 'getRootLevel'])
            ->setConstructorArgs(['Vendor\\Ext\\Domain\\Model\\ClubMate', 'tx_ext_domain_model_clubmate'])
            ->getMock();

        $this->dataMapFactory = $this->getMockBuilder(DataMapFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['buildDataMap', 'convertClassNameToTableName'])
            ->getMock();
        $this->dataMapFactory->expects(self::any())->method('buildDataMap')->willReturn($this->dataMap);

        $this->queryFactory = new QueryFactory(
            $this->objectManager,
            $this->createMock(ConfigurationManagerInterface::class),
            $this->dataMapFactory
        );
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
        $this->dataMap->expects(self::any())->method('getIsStatic')->willReturn($static);
        $this->dataMap->expects(self::any())->method('getRootLevel')->willReturn($rootLevel);

        $query = $this->createMock(QueryInterface::class);
        $this->objectManager->expects(self::at(0))->method('get')
            ->with(QueryInterface::class)
            ->willReturn($query);

        $querySettings = new Typo3QuerySettings();
        $this->objectManager->expects(self::at(1))->method('get')
            ->with(QuerySettingsInterface::class)
            ->willReturn($querySettings);
        $query->expects(self::once())->method('setQuerySettings')->with($querySettings);
        $this->queryFactory->create($this->className);

        self::assertSame(
            $expectedResult,
            $querySettings->getRespectStoragePage()
        );
    }
}
