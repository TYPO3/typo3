<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexFetch;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class TcaFlexFetchTest extends UnitTestCase
{
    /**
     * @var TcaFlexFetch
     */
    protected $subject;

    /**
     * @var BackendUserAuthentication|ObjectProphecy
     */
    protected $backendUserProphecy;

    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    protected function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();

        // Suppress cache foo in xml helpers of GeneralUtility
        /** @var CacheManager|ObjectProphecy $cacheManagerProphecy */
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache(Argument::cetera())->willReturn($cacheFrontendProphecy->reveal());

        $this->subject = new TcaFlexFetch();
    }

    protected function tearDown()
    {
        GeneralUtility::purgeInstances();
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function addDataSetsParsedDataStructureArray()
    {
        $input = [
            'systemLanguageRows' => [],
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

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'ROOT' => [
                'type' => 'array',
                'el' => [
                    'aFlexField' => [
                        'TCEforms' => [
                            'label' => 'aFlexFieldLabel',
                            'config' => [
                                'type' => 'input',
                            ],
                        ],
                    ],
                ],
            ],
            'meta' => [],
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsParsedDataStructureArrayWithSheets()
    {
        $input = [
            'systemLanguageRows' => [],
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

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'aFlexField' => [
                                'TCEforms' => [
                                    'label' => 'aFlexFieldLabel',
                                    'config' => [
                                        'type' => 'input',
                                    ],
                                ],
                            ],
                        ],
                        'TCEforms' => [
                            'sheetTitle' => 'aTitle',
                        ],
                    ],
                ],
            ],
            'meta' => [],
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfDataStructureCanNotBeParsed()
    {
        $input = [
            'systemLanguageRows' => [],
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => ''
                        ],
                    ],
                ],
            ],
        ];

        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1440506893);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataInitializesDatabaseRowValueIfNoDataStringIsGiven()
    {
        $input = [
            'databaseRow' => [],
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

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'ROOT' => '',
            'meta' => [],
        ];
        $expected['databaseRow']['aField'] = [
            'data' => [],
            'meta' => []
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }
}
