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

namespace TYPO3\CMS\Backend\Tests\UnitDeprecated\Form\FormDataProvider;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TcaFlexPrepareTest extends UnitTestCase
{
    /**
     * @var TcaFlexPrepare
     */
    protected $subject;

    /**
     * @var BackendUserAuthentication|ObjectProphecy
     */
    protected $backendUserProphecy;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Suppress cache foo in xml helpers of GeneralUtility
        /** @var CacheManager|ObjectProphecy $cacheManagerProphecy */
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache(Argument::cetera())->willReturn($cacheFrontendProphecy->reveal());

        $this->subject = new TcaFlexPrepare();
    }

    /**
     * Tear down
     */
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function addDataMigratesFlexformTca()
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
                                                    <type>array</type>
                                                    <el>
                                                        <aFlexField>
                                                            <TCEforms>
                                                                <label>aFlexFieldLabel</label>
                                                                <config>
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
                                    'type' => 'none',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'meta' => [],
        ];

        self::assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataMigratesFlexformTcaInContainer()
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
                                                    <type>array</type>
                                                    <el>
                                                        <section_1>
                                                            <title>section_1</title>
                                                            <type>array</type>
                                                            <section>1</section>
                                                            <el>
                                                                <aFlexContainer>
                                                                    <type>array</type>
                                                                    <title>aFlexContainerLabel</title>
                                                                    <el>
                                                                        <aFlexField>
                                                                            <TCEforms>
                                                                                <label>aFlexFieldLabel</label>
                                                                                <config>
                                                                                </config>
                                                                            </TCEforms>
                                                                        </aFlexField>
                                                                    </el>
                                                                </aFlexContainer>
                                                            </el>
                                                        </section_1>
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
                            'section_1' => [
                                'title' => 'section_1',
                                'type' => 'array',
                                'section' => '1',
                                'el' => [
                                    'aFlexContainer' => [
                                        'type' => 'array',
                                        'title' => 'aFlexContainerLabel',
                                        'el' => [
                                            'aFlexField' => [
                                                'label' => 'aFlexFieldLabel',
                                                'config' => [
                                                    'type' => 'none',
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
            'meta' => [],
        ];

        self::assertEquals($expected, $this->subject->addData($input));
    }
}
