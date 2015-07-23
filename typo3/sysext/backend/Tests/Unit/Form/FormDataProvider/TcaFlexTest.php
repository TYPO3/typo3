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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlex;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Test case
 */
class TcaFlexTest extends UnitTestCase {

	/**
	 * @var TcaFlex
	 */
	protected $subject;

	/**
	 * @var BackendUserAuthentication|ObjectProphecy
	 */
	protected $backendUserProphecy;

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	public function setUp() {
		$this->singletonInstances = GeneralUtility::getSingletonInstances();

		// Suppress cache foo in xml helpers of GeneralUtility
		/** @var CacheManager|ObjectProphecy $cacheManagerProphecy */
		$cacheManagerProphecy = $this->prophesize(CacheManager::class);
		GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
		$cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
		$cacheManagerProphecy->getCache(Argument::cetera())->willReturn($cacheFrontendProphecy->reveal());

		/** @var BackendUserAuthentication|ObjectProphecy backendUserProphecy */
		$this->backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
		$GLOBALS['BE_USER'] = $this->backendUserProphecy->reveal();
		$GLOBALS['BE_USER']->groupData['non_exclude_fields'] = '';

		// Some tests call FormDataCompiler for sub elements. Those tests have functional test characteristics.
		// This is ok for the time being, but this settings takes care only parts of the compiler are called
		// to have less dependencies.
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['flexFormSegment'] = [];

		$this->subject = new TcaFlex();
	}

	protected function tearDown() {
		GeneralUtility::purgeInstances();
		GeneralUtility::resetSingletonInstances($this->singletonInstances);
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionIfBothSheetsAndRootDefined() {
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
										<ROOT></ROOT>
										<sheets></sheets>
									</T3DataStructure>
								',
							],
						],
					],
				],
			],
		];

		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1440676540);

		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataSetsParsedDataStructureArray() {
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
			'meta' => [
				'availableLanguageCodes' => [],
			],
		];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataSetsParsedDataStructureArrayWithSheets() {
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
								'label' => 'aFlexFieldLabel',
								'config' => [
									'type' => 'input',
								],
							],
						],
						'TCEforms' => [
							'sheetTitle' => 'aTitle',
						],
					],
				],
			],
			'meta' => [
				'availableLanguageCodes' => [],
			],
		];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionIfDataStructureCanNotBeParsed() {
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
	public function addDataInitializesDatabaseRowValueIfNoDataStringIsGiven() {
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
			'meta' => [
				'availableLanguageCodes' => [],
			],
		];
		$expected['databaseRow']['aField'] = [
			'data' => [],
			'meta' => []
		];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataOverwritesDataStructureLangDisableIfSetViaPageTsConfig() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
									<T3DataStructure>
										<ROOT></ROOT>
									</T3DataStructure>
								',
							],
						],
					],
				],
			],
			'pageTsConfigMerged' => [
				'TCEFORM.' => [
					'aTable.' => [
						'aField.' => [
							'aFlex.' => [
								'langDisable' => 1,
							] ,
						],
					],
				],
			],
		];

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'ROOT' => '',
			'meta' => [
				'availableLanguageCodes' => [],
				'langDisable' => 1,
			],
		];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataOverwritesDataStructureLangChildrenIfSetViaPageTsConfig() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
									<T3DataStructure>
										<ROOT></ROOT>
									</T3DataStructure>
								',
							],
						],
					],
				],
			],
			'pageTsConfigMerged' => [
				'TCEFORM.' => [
					'aTable.' => [
						'aField.' => [
							'aFlex.' => [
								'langChildren' => 0,
							],
						],
					],
				],
			],
		];

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'ROOT' => '',
			'meta' => [
				'availableLanguageCodes' => [],
				'langChildren' => 0,
			],
		];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataRemovesSheetIfDisabledByPageTsConfig() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
									<T3DataStructure>
										<sheets>
											<aSheet>
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
											</aSheet>
										</sheets>
									</T3DataStructure>
								',
							],
						],
					],
				],
			],
			'pageTsConfigMerged' => [
				'TCEFORM.' => [
					'aTable.' => [
						'aField.' => [
							'aFlex.' => [
								'aSheet.' => [
									'disabled' => 1,
								],
							],
						],
					],
				],
			],
		];

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'meta' => [
				'availableLanguageCodes' => [],
			],
			'sheets' => [],
		];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataRemovesSingleSheetIfDisabledByPageTsConfig() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
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
			'pageTsConfigMerged' => [
				'TCEFORM.' => [
					'aTable.' => [
						'aField.' => [
							'aFlex.' => [
								'sDEF.' => [
									'disabled' => 1,
								],
							],
						],
					],
				],
			],
		];

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'meta' => [
				'availableLanguageCodes' => [],
			],
			'sheets' => [],
		];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataSetsSheetTitleFromPageTsConfig() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
									<T3DataStructure>
										<sheets>
											<aSheet>
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
											</aSheet>
										</sheets>
									</T3DataStructure>
								',
							],
						],
					],
				],
			],
			'pageTsConfigMerged' => [
				'TCEFORM.' => [
					'aTable.' => [
						'aField.' => [
							'aFlex.' => [
								'aSheet.' => [
									'sheetTitle' => 'aTitle',
								],
							],
						],
					],
				],
			],
		];

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'meta' => [
				'availableLanguageCodes' => [],
			],
			'sheets' => [
				'aSheet' => [
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
						'TCEforms' => [
							'sheetTitle' => 'aTitle',
						],
					],
				],
			],
		];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataSetsSheetDescriptionFromPageTsConfig() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
									<T3DataStructure>
										<sheets>
											<aSheet>
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
											</aSheet>
										</sheets>
									</T3DataStructure>
								',
							],
						],
					],
				],
			],
			'pageTsConfigMerged' => [
				'TCEFORM.' => [
					'aTable.' => [
						'aField.' => [
							'aFlex.' => [
								'aSheet.' => [
									'sheetDescription' => 'aDescription',
								],
							],
						],
					],
				],
			],
		];

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'meta' => [
				'availableLanguageCodes' => [],
			],
			'sheets' => [
				'aSheet' => [
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
						'TCEforms' => [
							'sheetDescription' => 'aDescription',
						],
					],
				],
			],
		];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataSetsSheetShortDescriptionFromPageTsConfig() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
									<T3DataStructure>
										<sheets>
											<aSheet>
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
											</aSheet>
										</sheets>
									</T3DataStructure>
								',
							],
						],
					],
				],
			],
			'pageTsConfigMerged' => [
				'TCEFORM.' => [
					'aTable.' => [
						'aField.' => [
							'aFlex.' => [
								'aSheet.' => [
									'sheetDescription' => 'sheetShortDescr',
								],
							],
						],
					],
				],
			],
		];

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'meta' => [
				'availableLanguageCodes' => [],
			],
			'sheets' => [
				'aSheet' => [
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
						'TCEforms' => [
							'sheetDescription' => 'sheetShortDescr',
						],
					],
				],
			],
		];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataSetsSheetShortDescriptionForSingleSheetFromPageTsConfig() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
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
			'pageTsConfigMerged' => [
				'TCEFORM.' => [
					'aTable.' => [
						'aField.' => [
							'aFlex.' => [
								'sDEF.' => [
									'sheetDescription' => 'sheetShortDescr',
								],
							],
						],
					],
				],
			],
		];

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'meta' => [
				'availableLanguageCodes' => [],
			],
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
						'TCEforms' => [
							'sheetDescription' => 'sheetShortDescr',
						],
					],
				],
			],
		];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataRemovesExcludeFieldFromDataStructure() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
									<T3DataStructure>
										<ROOT>
											<type>array</type>
											<el>
												<aFlexField>
													<TCEforms>
														<label>aFlexFieldLabel</label>
														<exclude>1</exclude>
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
			'pageTsConfigMerged' => [],
		];

		$this->backendUserProphecy->isAdmin()->shouldBeCalled()->willReturn(FALSE);
		$GLOBALS['BE_USER']->groupData['non_exclude_fields'] = '';

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'meta' => [
				'availableLanguageCodes' => [],
			],
			'sheets' => [
				'sDEF' => [
					'ROOT' => [
						'type' => 'array',
						'el' => [],
					],
				],
			],
		];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataKeepsExcludeFieldInDataStructureWithUserAccess() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
									<T3DataStructure>
										<ROOT>
											<type>array</type>
											<el>
												<aFlexField>
													<TCEforms>
														<label>aFlexFieldLabel</label>
														<exclude>1</exclude>
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
			'pageTsConfigMerged' => [],
		];

		$this->backendUserProphecy->isAdmin()->shouldBeCalled()->willReturn(FALSE);
		$GLOBALS['BE_USER']->groupData['non_exclude_fields'] = 'aTable:aField;aFlex;sDEF;aFlexField';

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'meta' => [
				'availableLanguageCodes' => [],
			],
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
								'exclude' => '1',
							],
						],
					],
				],
			],
		];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataKeepsExcludeFieldInDataStructureForAdminUser() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
									<T3DataStructure>
										<ROOT>
											<type>array</type>
											<el>
												<aFlexField>
													<TCEforms>
														<label>aFlexFieldLabel</label>
														<exclude>1</exclude>
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
			'pageTsConfigMerged' => [],
		];

		$this->backendUserProphecy->isAdmin()->shouldBeCalled()->willReturn(TRUE);
		$GLOBALS['BE_USER']->groupData['non_exclude_fields'] = '';

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'meta' => [
				'availableLanguageCodes' => [],
			],
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
								'exclude' => '1',
							],
						],
					],
				],
			],
		];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataRemovesPageTsDisabledFieldFromDataStructure() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
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
			'pageTsConfigMerged' => [
				'TCEFORM.' => [
					'aTable.' => [
						'aField.' => [
							'aFlex.' => [
								'sDEF.' => [
									'aFlexField.' => [
										'disabled' => 1,
									],
								],
							],
						],
					],
				],
			],
		];

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'meta' => [
				'availableLanguageCodes' => [],
			],
			'sheets' => [
				'sDEF' => [
					'ROOT' => [
						'type' => 'array',
						'el' => [],
					],
				],
			],
		];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataCleansLanguageDisabledDataValues() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [
						'sDEF' => [
							'lDEF' => [
								'input_1' => [
									'vDEF' => 'input1 text',
									'vDEF.vDEFbase' => 'base',
									'_TRANSFORM_vDEF.vDEFbase' => 'transform',
									'vRemoveMe' => 'removeMe',
									'vRemoveMe.vDEFbase' => 'removeMe',
									'_TRANSFORM_vRemoveMe.vDEFbase' => 'removeMe',
								],
								'section_1' => [
									'el' => [
										'1' => [
											'container_1' => [
												'el' => [
													'input_2' => [
														'vDEF' => 'input2 text',
														'vRemoveMe' => 'removeMe',
													]
												],
											],
										],
									],
								],
								'invalid1' => 'keepMe',
								'invalid2' => [
									'el' => [
										'1' => [
											'keepMe',
										],
									],
								],
								'invalid3' => [
									'el' => [
										'1' => [
											'container_2' => 'keepMe',
										],
									],
								],
								'invalid4' => [
									'el' => [
										'1' => [
											'container_2' => [
												'el' => 'keepMe',
											],
										],
									],
								],
								'invalid5' => [
									'el' => [
										'1' => [
											'container_2' => [
												'el' => [
													'field' => 'keepMe',
												],
											],
										],
									],
								],
							],
							'lRemoveMe' => [],
						],
					],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [
				0 => [
					'uid' => 0,
					'title' => 'aLanguageTitle',
					'iso' => 'DEF',
				],
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
									<T3DataStructure>
										<ROOT></ROOT>
										<meta>
											<langDisabled>1</langDisabled>
										</meta>
									</T3DataStructure>
								',
							],
						],
					],
				],
			],
		];

		$this->backendUserProphecy->isAdmin()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess('0')->shouldBeCalled()->willReturn(TRUE);

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'ROOT' => '',
			'meta' => [
				'availableLanguageCodes' => [
					0 => 'DEF',
				],
				'langDisabled' => '1',
			],
		];

		unset($expected['databaseRow']['aField']['data']['sDEF']['lRemoveMe']);
		unset($expected['databaseRow']['aField']['data']['sDEF']['lDEF']['input_1']['vRemoveMe']);
		unset($expected['databaseRow']['aField']['data']['sDEF']['lDEF']['input_1']['vRemoveMe.vDEFbase']);
		unset($expected['databaseRow']['aField']['data']['sDEF']['lDEF']['input_1']['_TRANSFORM_vRemoveMe.vDEFbase']);
		unset($expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['el']['1']['container_1']['el']['input_2']['vRemoveMe']);

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataRemovesDataValuesIfUserHasNoAccess() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [
						'sDEF' => [
							'lDEF' => [
								'input_1' => [
									'vDEF' => 'input1 text',
								],
								'section_1' => [
									'el' => [
										'1' => [
											'container_1' => [
												'el' => [
													'input_2' => [
														'vDEF' => 'input2 text',
														'vNoAccess' => 'removeMe',
													]
												],
											],
										],
									],
								],
							],
							'lNoAccess' => [],
						],
					],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [
				0 => [
					'uid' => 0,
					'iso' => 'DEF',
				],
				1 => [
					'uid' => 1,
					'iso' => 'NoAccess',
				],
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
									<T3DataStructure>
										<ROOT></ROOT>
										<meta>
											<langDisabled>0</langDisabled>
										</meta>
									</T3DataStructure>
								',
							],
						],
					],
				],
			],
		];

		$this->backendUserProphecy->isAdmin()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess('0')->shouldBeCalled()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess('1')->shouldBeCalled()->willReturn(FALSE);

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'ROOT' => '',
			'meta' => [
				'availableLanguageCodes' => [
					0 => 'DEF',
				],
				'langDisabled' => '0',
			],
		];

		unset($expected['databaseRow']['aField']['data']['sDEF']['lNoAccess']);
		unset($expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['el']['1']['container_1']['el']['input_2']['vNoAccess']);

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataAddsNewLanguageDataValues() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [
						'sDEF' => [
							'lDEF' => [
								'input_1' => [
									'vDEF' => 'input1 text',
								],
							],
						],
					],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [
				0 => [
					'uid' => 0,
					'iso' => 'DEF',
				],
				1 => [
					'uid' => 1,
					'iso' => 'EN',
				],
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
									<T3DataStructure>
										<ROOT></ROOT>
										<meta>
											<langDisabled>0</langDisabled>
										</meta>
									</T3DataStructure>
								',
							],
						],
					],
				],
			],
		];

		$this->backendUserProphecy->isAdmin()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess('0')->shouldBeCalled()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess('1')->shouldBeCalled()->willReturn(TRUE);

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'ROOT' => '',
			'meta' => [
				'availableLanguageCodes' => [
					0 => 'DEF',
					1 => 'EN',
				],
				'langDisabled' => '0',
			],
		];

		$expected['databaseRow']['aField']['data']['sDEF']['lEN'] = [];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataRemovesDataValuesIfPageOverlayCheckIsEnabled() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [
						'sDEF' => [
							'lDEF' => [],
							'lEN' => [],
							'lNoOverlay' => [],
						],
					],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [
				0 => [
					'uid' => 0,
					'iso' => 'DEF',
				],
				1 => [
					'uid' => 1,
					'iso' => 'EN',
				],
				2 => [
					'uid' => 2,
					'iso' => 'NoOverlay',
				],
			],
			'userTsConfig' => [
				'options.' => [
					'checkPageLanguageOverlay' => '1',
				],
			],
			'pageLanguageOverlayRows' => [
				0 => [
					'uid' => 1,
					'sys_language_uid' => 1,
				],
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
									<T3DataStructure>
										<ROOT></ROOT>
										<meta>
											<langDisabled>0</langDisabled>
										</meta>
									</T3DataStructure>
								',
							],
						],
					],
				],
			],
		];

		$this->backendUserProphecy->isAdmin()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess('0')->shouldBeCalled()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess('1')->shouldBeCalled()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess('2')->shouldBeCalled()->willReturn(TRUE);

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'ROOT' => '',
			'meta' => [
				'availableLanguageCodes' => [
					0 => 'DEF',
					1 => 'EN',
				],
				'langDisabled' => '0',
			],
		];

		unset($expected['databaseRow']['aField']['data']['sDEF']['lNoOverlay']);

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataRemovesLanguageDataValuesIfUserHasNoAccessWithLangChildren() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [
						'sDEF' => [
							'lDEF' => [
								'input_1' => [
									'vDEF' => 'input1 text',
									'vNoAccess' => 'removeMe',
								],
								'section_1' => [
									'el' => [
										'1' => [
											'container_1' => [
												'el' => [
													'input_2' => [
														'vDEF' => 'input2 text',
														'vNoAccess' => 'removeMe',
													]
												],
											],
										],
									],
								],
							],
							'lNoAccess' => [],
						],
					],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [
				0 => [
					'uid' => 0,
					'iso' => 'DEF',
				],
				1 => [
					'uid' => 1,
					'iso' => 'NoAccess',
				],
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
									<T3DataStructure>
										<ROOT></ROOT>
										<meta>
											<langDisabled>0</langDisabled>
											<langChildren>1</langChildren>
										</meta>
									</T3DataStructure>
								',
							],
						],
					],
				],
			],
		];

		$this->backendUserProphecy->isAdmin()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess('0')->shouldBeCalled()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess('1')->shouldBeCalled()->willReturn(FALSE);

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'ROOT' => '',
			'meta' => [
				'availableLanguageCodes' => [
					0 => 'DEF',
				],
				'langDisabled' => '0',
				'langChildren' => '1',
			],
		];

		unset($expected['databaseRow']['aField']['data']['sDEF']['lNoAccess']);
		unset($expected['databaseRow']['aField']['data']['sDEF']['lDEF']['input_1']['vNoAccess']);
		unset($expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['el']['1']['container_1']['el']['input_2']['vNoAccess']);

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataRemovesDataValuesIfPageOverlayCheckIsEnabledWithLangChildren() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [
						'sDEF' => [
							'lDEF' => [
								'input_1' => [
									'vDEF' => 'input1 text',
									'vEN' => 'input1 en text',
									'vNoOverlay' => 'removeMe',
								],
							],
						],
					],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [
				0 => [
					'uid' => 0,
					'iso' => 'DEF',
				],
				1 => [
					'uid' => 1,
					'iso' => 'EN',
				],
				2 => [
					'uid' => 2,
					'iso' => 'NoOverlay',
				],
			],
			'userTsConfig' => [
				'options.' => [
					'checkPageLanguageOverlay' => '1',
				],
			],
			'pageLanguageOverlayRows' => [
				0 => [
					'uid' => 1,
					'sys_language_uid' => 1,
				],
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
									<T3DataStructure>
										<ROOT></ROOT>
										<meta>
											<langDisabled>0</langDisabled>
											<langChildren>1</langChildren>
										</meta>
									</T3DataStructure>
								',
							],
						],
					],
				],
			],
		];

		$this->backendUserProphecy->isAdmin()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess('0')->shouldBeCalled()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess('1')->shouldBeCalled()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess('2')->shouldBeCalled()->willReturn(TRUE);

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'ROOT' => '',
			'meta' => [
				'availableLanguageCodes' => [
					0 => 'DEF',
					1 => 'EN',
				],
				'langDisabled' => '0',
				'langChildren' => '1',
			],
		];

		unset($expected['databaseRow']['aField']['data']['sDEF']['lDEF']['input_1']['vNoOverlay']);

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataHandlesPageTsConfigSettingsOfSingleFlexField() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [
				0 => [
					'uid' => 0,
					'iso' => 'DEF',
				],
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
									<T3DataStructure>
										<ROOT>
											<type>array</type>
											<el>
												<aFlexField>
													<TCEforms>
														<label>aFlexFieldLabel</label>
														<config>
															<type>radio</type>
															<items>
																<numIndex index="0">
																	<numIndex index="0">aLabel</numIndex>
																	<numIndex index="1">aValue</numIndex>
																</numIndex>
															</items>
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
			'pageTsConfigMerged' => [
				'TCEFORM.' => [
					'aTable.' => [
						'aField.' => [
							'aFlex.' => [
								'sDEF.' => [
									'aFlexField.' => [
										'altLabels.' => [
											'0' => 'labelOverride',
										],
									],
								],
							],
						],
					],
				],
			],
		];

		$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['flexFormSegment'] = [
			\TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class => [],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$this->backendUserProphecy->isAdmin()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess(Argument::cetera())->willReturn(TRUE);

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'meta' => [
				'availableLanguageCodes' => [
					0 => 'DEF',
				],
			],
			'sheets' => [
				'sDEF' => [
					'ROOT' => [
						'type' => 'array',
						'el' => [
							'aFlexField' => [
								'label' => 'aFlexFieldLabel',
								'config' => [
									'type' => 'radio',
									'items' => [
										0 => [
											0 => 'labelOverride',
											1 => 'aValue',
										],
									],
								],
							],
						],
					],
				],
			],
		];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataSetsDefaultValueFromFlexTcaForField() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [],
					'meta' => [],
				],
				'pointerField' => 'aFlex',
			],
			'systemLanguageRows' => [
				0 => [
					'uid' => 0,
					'iso' => 'DEF',
				],
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'flex',
							'ds_pointerField' => 'pointerField',
							'ds' => [
								'aFlex' => '
									<T3DataStructure>
										<ROOT>
											<type>array</type>
											<el>
												<aFlexField>
													<TCEforms>
														<label>aFlexFieldLabel</label>
														<config>
															<type>input</type>
															<default>defaultValue</default>
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
			'pageTsConfigMerged' => [],
		];

		$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['flexFormSegment'] = [
			\TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class => [],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$this->backendUserProphecy->isAdmin()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess(Argument::cetera())->willReturn(TRUE);

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'meta' => [
				'availableLanguageCodes' => [
					0 => 'DEF',
				],
			],
			'sheets' => [
				'sDEF' => [
					'ROOT' => [
						'type' => 'array',
						'el' => [
							'aFlexField' => [
								'label' => 'aFlexFieldLabel',
								'config' => [
									'type' => 'input',
									'default' => 'defaultValue',
								],
							],
						],
					],
				],
			],
		];

		$expected['databaseRow']['aField']['data']['sDEF']['lDEF']['aFlexField']['vDEF'] = 'defaultValue';

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataSetsDefaultValueFromFlexTcaForFieldInLocalizedSheet() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [],
					'meta' => [],
				],
			],
			'systemLanguageRows' => [
				0 => [
					'uid' => 0,
					'iso' => 'DEF',
				],
				1 => [
					'uid' => 1,
					'iso' => 'EN',
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
															<default>defaultValue</default>
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
			'pageTsConfigMerged' => [],
		];

		$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['flexFormSegment'] = [
			\TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class => [],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$this->backendUserProphecy->isAdmin()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess(Argument::cetera())->willReturn(TRUE);

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'meta' => [
				'availableLanguageCodes' => [
					0 => 'DEF',
					1 => 'EN',
				],
			],
			'sheets' => [
				'sDEF' => [
					'ROOT' => [
						'type' => 'array',
						'el' => [
							'aFlexField' => [
								'label' => 'aFlexFieldLabel',
								'config' => [
									'type' => 'input',
									'default' => 'defaultValue',
								],
							],
						],
					],
				],
			],
		];

		$expected['databaseRow']['aField']['data']['sDEF']['lDEF']['aFlexField']['vDEF'] = 'defaultValue';
		$expected['databaseRow']['aField']['data']['sDEF']['lEN']['aFlexField']['vDEF'] = 'defaultValue';

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataSetsDefaultValueFromFlexTcaForFieldInLocalizedSheetWithLangChildren() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [],
					'meta' => [],
				],
			],
			'systemLanguageRows' => [
				0 => [
					'uid' => 0,
					'iso' => 'DEF',
				],
				1 => [
					'uid' => 1,
					'iso' => 'EN',
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
										<meta>
											<langChildren>1</langChildren>
										</meta>
										<ROOT>
											<type>array</type>
											<el>
												<aFlexField>
													<TCEforms>
														<label>aFlexFieldLabel</label>
														<config>
															<type>input</type>
															<default>defaultValue</default>
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
			'pageTsConfigMerged' => [],
		];

		$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['flexFormSegment'] = [
			\TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class => [],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$this->backendUserProphecy->isAdmin()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess(Argument::cetera())->willReturn(TRUE);

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'meta' => [
				'langChildren' => 1,
				'availableLanguageCodes' => [
					0 => 'DEF',
					1 => 'EN',
				],
			],
			'sheets' => [
				'sDEF' => [
					'ROOT' => [
						'type' => 'array',
						'el' => [
							'aFlexField' => [
								'label' => 'aFlexFieldLabel',
								'config' => [
									'type' => 'input',
									'default' => 'defaultValue',
								],
							],
						],
					],
				],
			],
		];

		$expected['databaseRow']['aField']['data']['sDEF']['lDEF']['aFlexField']['vDEF'] = 'defaultValue';
		$expected['databaseRow']['aField']['data']['sDEF']['lDEF']['aFlexField']['vEN'] = 'defaultValue';

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionForDataStructureTypeArrayWithoutSection() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [],
					'meta' => [],
				],
			],
			'systemLanguageRows' => [
				0 => [
					'uid' => 0,
					'iso' => 'DEF',
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
													<type>array</type>
													<TCEforms></TCEforms>
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
			'pageTsConfigMerged' => [],
		];

		$this->backendUserProphecy->isAdmin()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess(Argument::cetera())->willReturn(TRUE);

		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1440685208);

		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionForDataStructureSectionWithoutTypeArray() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [],
					'meta' => [],
				],
			],
			'systemLanguageRows' => [
				0 => [
					'uid' => 0,
					'iso' => 'DEF',
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
													<section>1</section>
													<TCEforms></TCEforms>
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
			'pageTsConfigMerged' => [],
		];

		$this->backendUserProphecy->isAdmin()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess(Argument::cetera())->willReturn(TRUE);

		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1440685208);

		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataSetsValuesAndStructureForSectionContainerElementsNoLangChildren() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [
						'sDEF' => [
							'lDEF' => [
								'section_1' => [
									'el' => [
										'1' => [
											'container_1' => [
												// It should set the default value for aFlexField here
												'el' => [
												],
											],
										],
										'2' => [
											'container_1' => [
												'el' => [
													'aFlexField' => [
														// It should keep this value
														'vDEF' => 'dbValue',
													],
												],
											],
										],
									],
								],
							],
							'lEN' => [
								'section_1' => [
									'el' => [
										'1' => [
											'container_1' => [
												// It should add the default value for aFlexField here
											],
										],
									],
								],
							],
						],
					],
					'meta' => [],
				],
			],
			'systemLanguageRows' => [
				0 => [
					'uid' => 0,
					'iso' => 'DEF',
				],
				1 => [
					'uid' => 1,
					'iso' => 'EN',
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
												<section_1>
													<type>array</type>
													<section>1</section>
													<el>
														<container_1>
															<type>array</type>
															<el>
																<aFlexField>
																	<TCEforms>
																		<label>aFlexFieldLabel</label>
																		<config>
																			<type>input</type>
																			<default>defaultValue</default>
																		</config>
																	</TCEforms>
																</aFlexField>
															</el>
														</container_1>
													</el>
												</section_1>
											</el>
										</ROOT>
									</T3DataStructure>
								',
							],
						],
					],
				],
			],
			'pageTsConfigMerged' => [],
		];

		$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['flexFormSegment'] = [
			\TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class => [],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$this->backendUserProphecy->isAdmin()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess(Argument::cetera())->willReturn(TRUE);

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'meta' => [
				'availableLanguageCodes' => [
					0 => 'DEF',
					1 => 'EN',
				],
			],
			'sheets' => [
				'sDEF' => [
					'ROOT' => [
						'type' => 'array',
						'el' => [
							'section_1' => [
								'type' => 'array',
								'section' => '1',
								'el' => [
									'container_1' => [
										'type' => 'array',
										'el' => [
											'aFlexField' => [
												'label' => 'aFlexFieldLabel',
												'config' => [
													'type' => 'input',
													'default' => 'defaultValue',
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
		];

		// A default value for existing container field aFlexField should have been set
		$expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['el']['1']['container_1']['el']['aFlexField']['vDEF'] = 'defaultValue';
		// Also for the other defined language
		$expected['databaseRow']['aField']['data']['sDEF']['lEN']['section_1']['el']['1']['container_1']['el']['aFlexField']['vDEF'] = 'defaultValue';

		// Dummy row values for container_1 on lDEF sheet
		$expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['templateRows']['container_1']['el']['aFlexField']['vDEF'] = 'defaultValue';
		// Dummy row values for container_1 on lDEF sheet
		$expected['databaseRow']['aField']['data']['sDEF']['lEN']['section_1']['templateRows']['container_1']['el']['aFlexField']['vDEF'] = 'defaultValue';

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataSetsValuesAndStructureForSectionContainerElementsWithLangChildren() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => [
					'data' => [
						'sDEF' => [
							'lDEF' => [
								'section_1' => [
									'el' => [
										'1' => [
											'container_1' => [
												// It should set a default for both vDEF and vEN
												'el' => [
												],
											],
										],
										'2' => [
											'container_1' => [
												'el' => [
													'aFlexField' => [
														// It should keep this value
														'vDEF' => 'dbValue',
														// It should set a default for vEN
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
				],
			],
			'systemLanguageRows' => [
				0 => [
					'uid' => 0,
					'iso' => 'DEF',
				],
				1 => [
					'uid' => 1,
					'iso' => 'EN',
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
										<meta>
											<langChildren>1</langChildren>
										</meta>
										<ROOT>
											<type>array</type>
											<el>
												<section_1>
													<type>array</type>
													<section>1</section>
													<el>
														<container_1>
															<type>array</type>
															<el>
																<aFlexField>
																	<TCEforms>
																		<label>aFlexFieldLabel</label>
																		<config>
																			<type>input</type>
																			<default>defaultValue</default>
																		</config>
																	</TCEforms>
																</aFlexField>
															</el>
														</container_1>
													</el>
												</section_1>
											</el>
										</ROOT>
									</T3DataStructure>
								',
							],
						],
					],
				],
			],
			'pageTsConfigMerged' => [],
		];

		$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['flexFormSegment'] = [
			\TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class => [],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$this->backendUserProphecy->isAdmin()->willReturn(TRUE);
		$this->backendUserProphecy->checkLanguageAccess(Argument::cetera())->willReturn(TRUE);

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['ds'] = [
			'meta' => [
				'langChildren' => 1,
				'availableLanguageCodes' => [
					0 => 'DEF',
					1 => 'EN',
				],
			],
			'sheets' => [
				'sDEF' => [
					'ROOT' => [
						'type' => 'array',
						'el' => [
							'section_1' => [
								'type' => 'array',
								'section' => '1',
								'el' => [
									'container_1' => [
										'type' => 'array',
										'el' => [
											'aFlexField' => [
												'label' => 'aFlexFieldLabel',
												'config' => [
													'type' => 'input',
													'default' => 'defaultValue',
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
		];

		// A default value for existing container field aFlexField should have been set
		$expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['el']['1']['container_1']['el']['aFlexField']['vDEF'] = 'defaultValue';
		$expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['el']['1']['container_1']['el']['aFlexField']['vEN'] = 'defaultValue';
		// Also for the other defined language
		$expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['el']['2']['container_1']['el']['aFlexField']['vEN'] = 'defaultValue';

		// There should be a templateRow for container_1 with defaultValue set for both languages
		$expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['templateRows']['container_1']['el']['aFlexField']['vDEF'] = 'defaultValue';
		$expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['templateRows']['container_1']['el']['aFlexField']['vEN'] = 'defaultValue';

		$this->assertEquals($expected, $this->subject->addData($input));
	}

}
