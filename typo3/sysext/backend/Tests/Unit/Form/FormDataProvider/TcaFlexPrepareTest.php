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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Tests\Unit\Fixtures\EventDispatcher\MockEventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TcaFlexPrepareTest extends UnitTestCase
{
    use ProphecyTrait;

    protected function setUp(): void
    {
        parent::setUp();
        // Suppress cache foo in xml helpers of GeneralUtility
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache(Argument::cetera())->willReturn($cacheFrontendProphecy->reveal());

        $eventDispatcher = new MockEventDispatcher();
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);
        GeneralUtility::addInstance(FlexFormTools::class, new FlexFormTools($eventDispatcher));
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function addDataKeepsExistingDataStructure(): void
    {
        $input = [
            'systemLanguageRows' => [],
            'tableName' => 'aTableName',
            'databaseRow' => [
                'aField' => [
                    'data' => [],
                    'meta' => [],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTableName","fieldName":"aField","dataStructureKey":"default"}',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'aFlexField' => [
                                                    'label' => 'aFlexFieldLabel',
                                                    'config' => [
                                                        'type' => 'input',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'meta' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected = $input;
        self::assertEquals($expected, (new TcaFlexPrepare())->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsParsedDataStructureArray(): void
    {
        $input = [
            'systemLanguageRows' => [],
            'tableName' => 'aTableName',
            'databaseRow' => [
                'aField' => [
                    'data' => [],
                    'meta' => [],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => [
                                'default' => '
                                    <T3DataStructure>
                                        <ROOT>
                                            <type>array</type>
                                            <el>
                                                <aFlexField>
                                                    <TCEforms>
                                                        <label>aFlexFieldLabel</label>
                                                        <config>
                                                            <type>input</type>
                                                        </config>
                                                    </TCEforms>
                                                </aFlexField>
                                            </el>
                                        </ROOT>
                                    </T3DataStructure>
                                ',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $GLOBALS['TCA']['aTableName']['columns'] = $input['processedTca']['columns'];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['dataStructureIdentifier']
            = '{"type":"tca","tableName":"aTableName","fieldName":"aField","dataStructureKey":"default"}';
        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'aFlexField' => [
                                'label' => 'aFlexFieldLabel',
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'meta' => [],
        ];

        self::assertEquals($expected, (new TcaFlexPrepare())->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsParsedDataStructureArrayWithSheets(): void
    {
        $input = [
            'systemLanguageRows' => [],
            'tableName' => 'aTableName',
            'databaseRow' => [
                'aField' => [
                    'data' => [],
                    'meta' => [],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => [
                                'default' => '
                                    <T3DataStructure>
                                        <sheets>
                                            <sDEF>
                                                <ROOT>
                                                    <TCEforms>
                                                        <sheetTitle>aTitle</sheetTitle>
                                                    </TCEforms>
                                                    <type>array</type>
                                                    <el>
                                                        <aFlexField>
                                                            <TCEforms>
                                                                <label>aFlexFieldLabel</label>
                                                                <config>
                                                                    <type>input</type>
                                                                </config>
                                                            </TCEforms>
                                                        </aFlexField>
                                                    </el>
                                                </ROOT>
                                            </sDEF>
                                        </sheets>
                                    </T3DataStructure>
                                ',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $GLOBALS['TCA']['aTableName']['columns'] = $input['processedTca']['columns'];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['dataStructureIdentifier']
            = '{"type":"tca","tableName":"aTableName","fieldName":"aField","dataStructureKey":"default"}';
        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'aFlexField' => [
                                'label' => 'aFlexFieldLabel',
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                        'sheetTitle' => 'aTitle',
                    ],
                ],
            ],
            'meta' => [],
        ];

        self::assertEquals($expected, (new TcaFlexPrepare())->addData($input));
    }

    /**
     * @test
     */
    public function addDataInitializesDatabaseRowValueIfNoDataStringIsGiven(): void
    {
        $input = [
            'databaseRow' => [],
            'tableName' => 'aTableName',
            'systemLanguageRows' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => [
                                'default' => '
                                    <T3DataStructure>
                                        <ROOT></ROOT>
                                    </T3DataStructure>
                                ',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $GLOBALS['TCA']['aTableName']['columns'] = $input['processedTca']['columns'];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['dataStructureIdentifier']
            = '{"type":"tca","tableName":"aTableName","fieldName":"aField","dataStructureKey":"default"}';
        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'ROOT' => '',
            'meta' => [],
        ];
        $expected['databaseRow']['aField'] = [
            'data' => [],
            'meta' => [],
        ];

        self::assertEquals($expected, (new TcaFlexPrepare())->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsParsedDataStructureArrayRecursive(): void
    {
        $input = [
            'systemLanguageRows' => [],
            'tableName' => 'aTableName',
            'databaseRow' => [
                'aField' => [
                    'data' => [],
                    'meta' => [],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => [
                                'default' => '
                                    <T3DataStructure>
                                        <sheets>
                                            <sTree>
                                                <ROOT>
                                                    <type>array</type>
                                                    <TCEforms>
                                                        <sheetTitle>selectTree</sheetTitle>
                                                    </TCEforms>
                                                    <el>
                                                        <select_tree_1>
                                                            <TCEforms>
                                                                <label>select_tree_1</label>
                                                                <description>select_tree_1 description</description>
                                                                <config>
                                                                    <type>select</type>
                                                                    <renderType>selectTree</renderType>
                                                                </config>
                                                            </TCEforms>
                                                        </select_tree_1>
                                                    </el>
                                                </ROOT>
                                            </sTree>
                                            <sSection>
                                                <ROOT>
                                                    <type>array</type>
                                                    <TCEforms>
                                                        <sheetTitle>section</sheetTitle>
                                                    </TCEforms>
                                                    <el>
                                                        <section_1>
                                                            <title>section_1</title>
                                                            <type>array</type>
                                                            <section>1</section>
                                                            <el>
                                                                <container_1>
                                                                    <type>array</type>
                                                                    <title>container_1</title>
                                                                    <el>
                                                                        <select_tree_2>
                                                                            <TCEforms>
                                                                                <label>select_tree_2</label>
                                                                                <description>select_tree_2 description</description>
                                                                                <config>
                                                                                    <type>select</type>
                                                                                    <renderType>selectTree</renderType>
                                                                                </config>
                                                                            </TCEforms>
                                                                        </select_tree_2>
                                                                    </el>
                                                                </container_1>
                                                            </el>
                                                        </section_1>
                                                    </el>
                                                </ROOT>
                                            </sSection>
                                        </sheets>
                                    </T3DataStructure>
                                ',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $GLOBALS['TCA']['aTableName']['columns'] = $input['processedTca']['columns'];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['dataStructureIdentifier']
            = '{"type":"tca","tableName":"aTableName","fieldName":"aField","dataStructureKey":"default"}';

        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'sheets' => [
                'sSection' => [
                    'ROOT' => [
                        'type' => 'array',
                        'sheetTitle' => 'section',
                        'el' => [
                            'section_1' => [
                                'title' => 'section_1',
                                'type' => 'array',
                                'section' => '1',
                                'el' => [
                                    'container_1' => [
                                        'type' => 'array',
                                        'title' => 'container_1',
                                        'el' => [
                                            'select_tree_2' => [
                                                'label' => 'select_tree_2',
                                                'description' => 'select_tree_2 description',
                                                'config' => [
                                                    'type' => 'select',
                                                    'renderType' => 'selectTree',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'sTree' => [
                    'ROOT' => [
                        'type' => 'array',
                        'sheetTitle' => 'selectTree',
                        'el' => [
                            'select_tree_1' => [
                                'label' => 'select_tree_1',
                                'description' => 'select_tree_1 description',
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectTree',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'meta' => [],
        ];

        self::assertEquals($expected, (new TcaFlexPrepare())->addData($input));
    }

    /**
     * Test of the data provider when called for a section with already
     * resolved flex form, e.g. in an ajax request (tcaSelectTreeAjaxFieldData),
     * which got "reduced to the relevant element only".
     *
     * @test
     */
    public function addDataMigratesResolvedFlexformTca(): void
    {
        $columnConfig = [
            'label' => 'select_section_1',
            'description' => 'select_section_1 description',
            'config' => [
                'type' => 'select',
            ],
        ];

        $input = [
            'systemLanguageRows' => [],
            'tableName' => 'aTableName',
            'databaseRow' => [
                'aField' => [
                    'data' => [],
                    'meta' => [],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => [
                                'sheets' => [
                                    'sSection' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'section_1' => [
                                                    'section' => 1,
                                                    'type' => 'array',
                                                    'el' => [
                                                        'container_1' => [
                                                            'type' => 'array',
                                                            'el' => [
                                                                'select_section_1' => [
                                                                    'TCEforms' => $columnConfig,
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTableName","fieldName":"aField","dataStructureKey":"default"}',
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds']['meta'] = [];
        $expected['processedTca']['columns']['aField']['config']['ds']
            ['sheets']['sSection']['ROOT']['el']
                ['section_1']['el']
                    ['container_1']['el']
                        ['select_section_1'] = $columnConfig;

        self::assertEquals($expected, (new TcaFlexPrepare())->addData($input));
    }
}
