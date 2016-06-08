<?php
namespace TYPO3\CMS\Core\Tests\Unit\Tree\TableConfiguration;

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

use TYPO3\CMS\Core\Tests\Unit\Tree\TableConfiguration\Fixtures\TreeDataProviderFixture;
use TYPO3\CMS\Core\Tests\Unit\Tree\TableConfiguration\Fixtures\TreeDataProviderWithConfigurationFixture;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Tree\TableConfiguration\TreeDataProviderFactory;

/**
 * Test case
 */
class TreeDataProviderFactoryTest extends UnitTestCase
{
    /**
     * @var TreeDataProviderFactory
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new TreeDataProviderFactory();
        $GLOBALS['TCA'] = [];
        $GLOBALS['TCA']['foo'] = [];
        $GLOBALS['TCA']['foo']['ctrl'] = [];
        $GLOBALS['TCA']['foo']['ctrl']['label'] = 'labelFoo';
        $GLOBALS['TCA']['foo']['columns'] = [];
    }

    /**
     * @return array
     */
    public function invalidConfigurationDataProvider()
    {
        return [
            'Empty Configuration' => [[], 1288215890],
            'File Configuration' => [
                [
                    'internal_type' => 'file',
                    'treeConfig' => [],
                ],
                1288215891
            ],
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
            'Tree configuration missing childer and parent field' => [
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
     * @param string $expectedExceptionCode
     * @test
     * @dataProvider invalidConfigurationDataProvider
     */
    public function factoryThrowsExceptionIfInvalidConfigurationIsGiven(array $tcaConfiguration, $expectedExceptionCode)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode($expectedExceptionCode);

        $this->subject->getDataProvider($tcaConfiguration, 'foo', 'bar', ['uid' => 1]);
    }

    /**
     * @test
     */
    public function configuredDataProviderClassIsInstantiated()
    {
        $dataProviderMockClassName = TreeDataProviderFixture::class;

        $tcaConfiguration = [
            'treeConfig' => ['dataProvider' => $dataProviderMockClassName],
            'internal_type' => 'foo'
        ];
        $dataProvider = $this->subject->getDataProvider($tcaConfiguration, 'foo', 'bar', ['uid' => 1]);

        $this->assertInstanceOf($dataProviderMockClassName, $dataProvider);
    }

    /**
     * @test
     */
    public function configuredDataProviderClassIsInstantiatedWithTcaConfigurationInConstructor()
    {
        $dataProviderMockClassName = TreeDataProviderWithConfigurationFixture::class;

        $tcaConfiguration = [
            'treeConfig' => [
                'dataProvider' => $dataProviderMockClassName,
            ],
            'internal_type' => 'foo',
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1438875249);
        $this->subject->getDataProvider($tcaConfiguration, 'foo', 'bar', ['uid' => 1]);
    }
}
