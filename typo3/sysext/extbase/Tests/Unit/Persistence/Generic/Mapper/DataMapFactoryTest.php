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

namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic\Mapper;

use TYPO3\CMS\Belog\Domain\Model\LogEntry;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidClassException;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DataMapFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function buildDataMapThrowsExceptionIfClassNameIsNotKnown(): void
    {
        $this->expectException(InvalidClassException::class);
        // @TODO expectExceptionCode is 0
        $mockDataMapFactory = $this->getAccessibleMock(DataMapFactory::class, ['getControlSection'], [], '', false);
        $cacheMock = $this->getMockBuilder(VariableFrontend::class)
            ->onlyMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();
        $cacheMock->method('get')->willReturn(false);
        $mockDataMapFactory->_set('dataMapCache', $cacheMock);
        $mockDataMapFactory->_set('baseCacheIdentifier', 'PackageDependentCacheIdentifier');
        $mockDataMapFactory->buildDataMap('UnknownObject');
    }

    public static function classNameTableNameMappings(): array
    {
        return [
            'Core classes' => [LogEntry::class, 'tx_belog_domain_model_logentry'],
            'Core classes with namespaces and leading backslash' => [LogEntry::class, 'tx_belog_domain_model_logentry'],
            'Extension classes' => ['ExtbaseTeam\\BlogExample\\Domain\\Model\\Blog', 'tx_blogexample_domain_model_blog'],
            'Extension classes with namespaces and leading backslash' => ['\\ExtbaseTeam\\BlogExample\\Domain\\Model\\Blog', 'tx_blogexample_domain_model_blog'],
        ];
    }

    /**
     * @test
     * @dataProvider classNameTableNameMappings
     */
    public function resolveTableNameReturnsExpectedTablenames($className, $expected): void
    {
        $dataMapFactory = $this->getAccessibleMock(DataMapFactory::class, null, [], '', false);
        self::assertSame($expected, $dataMapFactory->_call('resolveTableName', $className));
    }
}
