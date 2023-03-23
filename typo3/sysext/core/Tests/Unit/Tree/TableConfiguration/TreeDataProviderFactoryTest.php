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
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Tests\Unit\Tree\TableConfiguration\Fixtures\TreeDataProviderFixture;
use TYPO3\CMS\Core\Tests\Unit\Tree\TableConfiguration\Fixtures\TreeDataProviderWithConfigurationFixture;
use TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider;
use TYPO3\CMS\Core\Tree\TableConfiguration\TreeDataProviderFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

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

    public static function invalidConfigurationDataProvider(): array
    {
        return [
            'Empty Configuration' => [[], 1288215890],
            'Unknown Type' => [
                [
                    'type' => 'folder',
                    'treeConfig' => [],
                ],
                1288215892,
            ],
            'No foreign table' => [
                [
                    'type' => 'group',
                    'treeConfig' => [],
                ],
                1288215888,
            ],
            'No tree configuration' => [
                [
                    'type' => 'group',
                    'foreign_table' => 'foo',
                ],
                1288215890,
            ],
            'Tree configuration not array' => [
                [
                    'type' => 'group',
                    'foreign_table' => 'foo',
                    'treeConfig' => 'bar',
                ],
                1288215890,
            ],
            'Tree configuration missing children and parent field' => [
                [
                    'type' => 'group',
                    'foreign_table' => 'foo',
                    'treeConfig' => [],
                ],
                1288215889,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidConfigurationDataProvider
     */
    public function factoryThrowsExceptionIfInvalidConfigurationIsGiven(array $tcaConfiguration, int $expectedExceptionCode): void
    {
        if (isset($tcaConfiguration['type']) && $tcaConfiguration['type'] !== 'folder' && is_array($tcaConfiguration['treeConfig'] ?? null)) {
            $treeDataProvider = $this->createMock(DatabaseTreeDataProvider::class);
            GeneralUtility::addInstance(DatabaseTreeDataProvider::class, $treeDataProvider);
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
        GeneralUtility::addInstance(EventDispatcherInterface::class, new NoopEventDispatcher());

        $tcaConfiguration = [
            'treeConfig' => ['dataProvider' => $dataProviderMockClassName],
            'type' => 'folder',
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
        GeneralUtility::addInstance(EventDispatcherInterface::class, new NoopEventDispatcher());

        $tcaConfiguration = [
            'treeConfig' => [
                'dataProvider' => $dataProviderMockClassName,
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1438875249);
        TreeDataProviderFactory::getDataProvider($tcaConfiguration, 'foo', 'bar', ['uid' => 1]);
    }
}
