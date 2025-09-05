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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class QueryFactoryTest extends UnitTestCase
{
    public static function getStaticAndRootLevelAndExpectedResult(): array
    {
        return [
            'Respect storage page is set when entity is neither marked as static nor as rootLevel.' => [false, false, true],
            'Respect storage page is set when entity is marked as static and rootLevel.' => [true, true, false],
            'Respect storage page is set when entity is marked as static but not rootLevel.' => [true, false, false],
            'Respect storage page is set when entity is not marked as static but as rootLevel.' => [false, true, false],
        ];
    }

    #[DataProvider('getStaticAndRootLevelAndExpectedResult')]
    #[Test]
    public function createDoesNotRespectStoragePageIfStaticOrRootLevelIsTrue(bool $static, bool $rootLevel, bool $expectedResult): void
    {
        $className = \TYPO3\CMS\Extbase\Domain\Model\Category::class;
        $dataMap = $this->getMockBuilder(DataMap::class)
            ->onlyMethods(['getIsStatic', 'getRootLevel'])
            ->setConstructorArgs(['Vendor\\Ext\\Domain\\Model\\ClubMate', 'tx_ext_domain_model_clubmate'])
            ->getMock();
        $dataMapFactoryMock = $this->createMock(DataMapFactory::class);
        $dataMapFactoryMock->method('buildDataMap')->willReturn($dataMap);
        $dataMap->method('getIsStatic')->willReturn($static);
        $dataMap->method('getRootLevel')->willReturn($rootLevel);
        $subject = new QueryFactory($this->createMock(ConfigurationManagerInterface::class), $dataMapFactoryMock);
        $query = $this->createMock(QueryInterface::class);
        $querySettings = new Typo3QuerySettings(new Context(), $this->createMock(ConfigurationManagerInterface::class));
        GeneralUtility::addInstance(QuerySettingsInterface::class, $querySettings);
        GeneralUtility::addInstance(QueryInterface::class, $query);
        $query->expects($this->once())->method('setQuerySettings')->with($querySettings);
        $subject->create($className);
        self::assertSame($expectedResult, $querySettings->getRespectStoragePage());
    }
}
