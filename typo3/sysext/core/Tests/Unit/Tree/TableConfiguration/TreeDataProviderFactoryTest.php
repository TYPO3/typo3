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

namespace TYPO3\CMS\Core\Tests\Unit\Tree\TableConfiguration;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Tests\Unit\Tree\TableConfiguration\Fixtures\TreeDataProviderFixture;
use TYPO3\CMS\Core\Tests\Unit\Tree\TableConfiguration\Fixtures\TreeDataProviderWithConfigurationFixture;
use TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider;
use TYPO3\CMS\Core\Tree\TableConfiguration\TreeDataProviderFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TreeDataProviderFactoryTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TCA'] = [];
        $GLOBALS['TCA']['foo'] = [];
        $GLOBALS['TCA']['foo']['ctrl'] = [];
        $GLOBALS['TCA']['foo']['ctrl']['label'] = 'labelFoo';
        $GLOBALS['TCA']['foo']['columns'] = [];
    }

    /**
     * @return array
     */
    public function invalidConfigurationDataProvider(): array
    {
        return [
            'Empty Configuration' => [[], 1288215890],
            'Unknown Type' => [
                [
                    'internal_type' => 'foo',
                    'treeConfig' => [],
                ],
                1288215892
            ],
            'No foreign table' => [
                [
                    'internal_type' => 'db',
                    'treeConfig' => [],
                ],
                1288215888
            ],
            'No tree configuration' => [
                [
                    'internal_type' => 'db',
                    'foreign_table' => 'foo',
                ],
                1288215890
            ],
            'Tree configuration not array' => [
                [
                    'internal_type' => 'db',
                    'foreign_table' => 'foo',
                    'treeConfig' => 'bar',
                ],
                1288215890
            ],
            'Tree configuration missing children and parent field' => [
                [
                    'internal_type' => 'db',
                    'foreign_table' => 'foo',
                    'treeConfig' => [],
                ],
                1288215889
            ],
        ];
    }

    /**
     * @param array $tcaConfiguration
     * @param int $expectedExceptionCode
     * @test
     * @dataProvider invalidConfigurationDataProvider
     */
    public function factoryThrowsExceptionIfInvalidConfigurationIsGiven(array $tcaConfiguration, int $expectedExceptionCode): void
    {
        if (($tcaConfiguration['internal_type'] ?? '') === 'db' && is_array($tcaConfiguration['treeConfig'] ?? null)) {
            $treeDataProvider = $this->prophesize(DatabaseTreeDataProvider::class);
            GeneralUtility::addInstance(DatabaseTreeDataProvider::class, $treeDataProvider->reveal());
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode($expectedExceptionCode);

        TreeDataProviderFactory::getDataProvider($tcaConfiguration, 'foo', 'bar', ['uid' => 1]);
    }

    /**
     * @test
     */
    public function configuredDataProviderClassIsInstantiated(): void
    {
        $dataProviderMockClassName = TreeDataProviderFixture::class;
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher->reveal());

        $tcaConfiguration = [
            'treeConfig' => ['dataProvider' => $dataProviderMockClassName],
            'internal_type' => 'foo'
        ];
        $dataProvider = TreeDataProviderFactory::getDataProvider($tcaConfiguration, 'foo', 'bar', ['uid' => 1]);

        self::assertInstanceOf($dataProviderMockClassName, $dataProvider);
    }

    /**
     * @test
     */
    public function configuredDataProviderClassIsInstantiatedWithTcaConfigurationInConstructor(): void
    {
        $dataProviderMockClassName = TreeDataProviderWithConfigurationFixture::class;
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher->reveal());

        $tcaConfiguration = [
            'treeConfig' => [
                'dataProvider' => $dataProviderMockClassName,
            ],
            'internal_type' => 'foo',
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1438875249);
        TreeDataProviderFactory::getDataProvider($tcaConfiguration, 'foo', 'bar', ['uid' => 1]);
    }
}
