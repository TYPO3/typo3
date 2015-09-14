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
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class TcaSelectItemsTest extends UnitTestCase {

	/**
	 * @var TcaSelectItems
	 */
	protected $subject;

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = [];

	public function setUp() {
		$this->singletonInstances = GeneralUtility::getSingletonInstances();
		$this->subject = new TcaSelectItems();
	}

	protected function tearDown() {
		GeneralUtility::purgeInstances();
		GeneralUtility::resetSingletonInstances($this->singletonInstances);
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function addDataKeepExistingItems() {
		$input = [
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'radio',
							'items' => [
								0 => [
									'foo',
									'bar',
								],
							],
						],
					],
					'anotherField' => [
						'config' => [
							'type' => 'group',
							'items' => [
								0 => [
									'foo',
									'bar',
								],
							],
						],
					],
				],
			],
		];
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$expected = $input;
		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionIfAnItemIsNotAnArray() {
		$input = [
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'items' => [
								0 => 'foo',
							],
						],
					],
				],
			],
		];

		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1439288036);

		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataTranslatesItemLabels() {
		$input = [
			'databaseRow' => [
				'aField' => 'aValue',
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'items' => [
								0 => [
									0 => 'aLabel',
									1 => 'aValue',
								],
							],
						],
					],
				],
			],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();

		$languageService->sL('aLabel')->shouldBeCalled()->willReturn('translated');

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['items'][0][0] = 'translated';
		$expected['processedTca']['columns']['aField']['config']['items'][0][2] = NULL;
		$expected['processedTca']['columns']['aField']['config']['items'][0][3] = NULL;

		$expected['databaseRow']['aField'] = ['aValue'];

		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataKeepsIconFromItem() {
		$input = [
			'databaseRow' => [
				'aField' => 'aValue',
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'items' => [
								0 => [
									0 => 'aLabel',
									1 => 'aValue',
									2 => 'an-icon-reference',
									3 => NULL,
								],
							],
						],
					],
				],
			],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$expected = $input;
		$expected['databaseRow']['aField'] = ['aValue'];

		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionWithUnknownSpecialValue() {
		$input = [
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'special' => 'anUnknownValue',
						],
					],
				],
			],
		];

		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1439298496);

		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataAddsTablesWithSpecialTables() {
		$input = [
			'databaseRow' => [
				'aField' => 'aValue',
			],
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'special' => 'tables',
						],
					],
				],
			],
		];
		$GLOBALS['TCA'] = [
			'notInResult' => [
				'ctrl' => [
					'adminOnly' => TRUE,
				],
			],
			'aTable' => [
				'ctrl' => [
					'title' => 'aTitle',
				],
			],
		];
		$GLOBALS['TCA_DESCR']['aTable']['columns']['']['description'] = 'aDescription';

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();

		$languageService->sL('aTitle')->shouldBeCalled()->willReturnArgument(0);
		$languageService->loadSingleTableDescription('aTable')->shouldBeCalled();

		$expected = $input;
		$expected['databaseRow']['aField'] = ['aValue'];
		$expected['processedTca']['columns']['aField']['config']['items'] = [
			0 => [
				0 => 'aTitle',
				1 => 'aTable',
				2 => 'status-status-icon-missing',
				3 => [
					'description' => 'aDescription',
				],
			]
		];

		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataAddsTablesWithSpecialPageTypes() {
		$input = [
			'databaseRow' => [
				'aField' => 'aValue',
			],
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'special' => 'pagetypes',
							'items' => [],
						],
					],
				],
			],
		];
		$GLOBALS['TCA'] = [
			'pages' => [
				'columns' => [
					'doktype' => [
						'config' => [
							'items' => [
								0 => [
									0 => 'aLabel',
									1 => 'aValue',
								],
							],
						],
					],
				],
			],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();

		$languageService->sL('aLabel')->shouldBeCalled()->willReturnArgument(0);

		$expected = $input;
		$expected['databaseRow']['aField'] = ['aValue'];
		$expected['processedTca']['columns']['aField']['config']['items'] = [
			0 => [
				0 => 'aLabel',
				1 => 'aValue',
				2 => 'status-status-icon-missing',
				3 => NULL,
			]
		];

		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * Data provider
	 */
	public function addDataAddsExcludeFieldsWithSpecialExcludeDataProvider() {
		return [
			'Table with exclude and non exclude field returns exclude item' => [
				[
					// input tca
					'fooTable' => [
						'ctrl' => [
							'title' => 'fooTableTitle',
						],
						'columns' => [
							'bar' => [
								'label' => 'barColumnTitle',
								'exclude' => 1
							],
							'baz' => [
								'label' => 'bazColumnTitle',
							],
						],
					],
				],
				[
					// expected items
					0 => [
						0 => 'fooTableTitle',
						1 => '--div--',
						2 => 'status-status-icon-missing',
						3 => NULL,
					],
					1 => [
						0 => 'barColumnTitle (bar)',
						1 => 'fooTable:bar',
						2 => 'empty-empty',
						3 => NULL,
					],
				],
			],
			'Root level table with ignored root level restriction returns exclude item' => [
				[
					// input tca
					'fooTable' => [
						'ctrl' => [
							'title' => 'fooTableTitle',
							'rootLevel' => TRUE,
							'security' => [
								'ignoreRootLevelRestriction' => TRUE,
							],
						],
						'columns' => [
							'bar' => [
								'label' => 'barColumnTitle',
								'exclude' => 1,
							],
						],
					],
				],
				[
					// expected items
					0 => [
						0 => 'fooTableTitle',
						1 => '--div--',
						2 => 'status-status-icon-missing',
						3 => NULL,
					],
					1 => [
						0 => 'barColumnTitle (bar)',
						1 => 'fooTable:bar',
						2 => 'empty-empty',
						3 => NULL,
					],
				],
			],
			'Root level table without ignored root level restriction returns no item' => [
				[
					// input tca
					'fooTable' => [
						'ctrl' => [
							'title' => 'fooTableTitle',
							'rootLevel' => TRUE,
						],
						'columns' => [
							'bar' => [
								'label' => 'barColumnTitle',
								'exclude' => 1,
							],
						],
					],
				],
				[
					// no items
				],
			],
			'Admin table returns no item' => [
				[
					// input tca
					'fooTable' => [
						'ctrl' => [
							'title' => 'fooTableTitle',
							'adminOnly' => TRUE,
						],
						'columns' => [
							'bar' => [
								'label' => 'barColumnTitle',
								'exclude' => 1,
							],
						],
					],
				],
				[
					// no items
				],
			],
		];
	}

	/**
	 * @test
	 * @dataProvider addDataAddsExcludeFieldsWithSpecialExcludeDataProvider
	 */
	public function addDataAddsExcludeFieldsWithSpecialExclude($tca, $expectedItems) {
		$input = [
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'special' => 'exclude',
						],
					],
				],
			],
		];
		$GLOBALS['TCA'] = $tca;

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->loadSingleTableDescription(Argument::cetera())->willReturn(NULL);
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$result = $this->subject->addData($input);

		$this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
	}

	/**
	 * @test
	 */
	public function addDataAddsExcludeFieldsFromFlexWithSpecialExclude() {
		$input = [
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'special' => 'exclude',
						],
					],
				],
			],
		];

		$GLOBALS['TCA'] = [
			'fooTable' => [
				'ctrl' => [
					'title' => 'fooTableTitle',
				],
				'columns' => [
					'aFlexField' => [
						'label' => 'aFlexFieldTitle',
						'config' => [
							'type' => 'flex',
							'title' => 'title',
							'ds' => [
								'dummy' => '
									<T3DataStructure>
										<ROOT>
											<type>array</type>
											<el>
												<input1>
													<TCEforms>
														<label>flexInputLabel</label>
														<exclude>1</exclude>
														<config>
															<type>input</type>
															<size>23</size>
														</config>
													</TCEforms>
												</input1>
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

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->loadSingleTableDescription(Argument::cetera())->willReturn(NULL);
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		// Needed to suppress a cache in xml2array
		/** @var DatabaseConnection|ObjectProphecy $database */
		$database = $this->prophesize(DatabaseConnection::class);
		$GLOBALS['TYPO3_DB'] = $database->reveal();

		$expectedItems = [
			0 => [
				0 => 'fooTableTitle',
				1 => '--div--',
				2 => 'status-status-icon-missing',
				3 => NULL,
			],
			1 => [
				0 => ' (input1)',
				1 => 'fooTable:aFlexField;dummy;sDEF;input1',
				2 => 'empty-empty',
				3 => NULL,
			],
		];

		$result = $this->subject->addData($input);

		$this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
	}

	/**
	 * @test
	 */
	public function addDataAddsExplicitAllowFieldsWithSpecialExplicitValues() {
		$input = [
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'special' => 'explicitValues',
						],
					],
				],
			],
		];

		$GLOBALS['TCA'] = [
			'fooTable' => [
				'ctrl' => [
					'title' => 'fooTableTitle',
				],
				'columns' => [
					'aField' => [
						'label' => 'aFieldTitle',
						'config' => [
							'type' => 'select',
							'authMode' => 'explicitAllow',
							'items' => [
								0 => [
									'anItemTitle',
									'anItemValue',
								],
							]
						],
					],
				],
			],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.allow')->shouldBeCalled()->willReturn('allowMe');
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$expectedItems = [
			0 => [
				0 => 'fooTableTitle: aFieldTitle',
				1 => '--div--',
				2 => NULL,
				3 => NULL,
			],
			1 => [
				0 => '[allowMe] anItemTitle',
				1 => 'fooTable:aField:anItemValue:ALLOW',
				2 => 'status-status-permission-granted',
				3 => NULL,
			],
		];

		$result = $this->subject->addData($input);

		$this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
	}

	/**
	 * @test
	 */
	public function addDataAddsExplicitDenyFieldsWithSpecialExplicitValues() {
		$input = [
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'special' => 'explicitValues',
						],
					],
				],
			],
		];

		$GLOBALS['TCA'] = [
			'fooTable' => [
				'ctrl' => [
					'title' => 'fooTableTitle',
				],
				'columns' => [
					'aField' => [
						'label' => 'aFieldTitle',
						'config' => [
							'type' => 'select',
							'authMode' => 'explicitDeny',
							'items' => [
								0 => [
									'anItemTitle',
									'anItemValue',
								],
							]
						],
					],
				],
			],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.deny')->shouldBeCalled()->willReturn('denyMe');
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$expectedItems = [
			0 => [
				0 => 'fooTableTitle: aFieldTitle',
				1 => '--div--',
				2 => NULL,
				3 => NULL,
			],
			1 => [
				0 => '[denyMe] anItemTitle',
				1 => 'fooTable:aField:anItemValue:DENY',
				2 => 'status-status-permission-denied',
				3 => NULL,
			],
		];

		$result = $this->subject->addData($input);

		$this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
	}

	/**
	 * @test
	 */
	public function addDataAddsExplicitIndividualAllowFieldsWithSpecialExplicitValues() {
		$input = [
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'special' => 'explicitValues',
						],
					],
				],
			],
		];

		$GLOBALS['TCA'] = [
			'fooTable' => [
				'ctrl' => [
					'title' => 'fooTableTitle',
				],
				'columns' => [
					'aField' => [
						'label' => 'aFieldTitle',
						'config' => [
							'type' => 'select',
							'authMode' => 'individual',
							'items' => [
								0 => [
									'aItemTitle',
									'aItemValue',
									NULL,
									NULL,
									'EXPL_ALLOW',
								],
								// 1 is not selectable as allow and is always allowed
								1 => [
									'bItemTitle',
									'bItemValue',
								],
								2 => [
									'cItemTitle',
									'cItemValue',
									NULL,
									NULL,
									'EXPL_ALLOW',
								],
							]
						],
					],
				],
			],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.allow')->shouldBeCalled()->willReturn('allowMe');
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$expectedItems = [
			0 => [
				0 => 'fooTableTitle: aFieldTitle',
				1 => '--div--',
				2 => NULL,
				3 => NULL,
			],
			1 => [
				0 => '[allowMe] aItemTitle',
				1 => 'fooTable:aField:aItemValue:ALLOW',
				2 => 'status-status-permission-granted',
				3 => NULL,
			],
			2 => [
				0 => '[allowMe] cItemTitle',
				1 => 'fooTable:aField:cItemValue:ALLOW',
				2 => 'status-status-permission-granted',
				3 => NULL,
			],
		];

		$result = $this->subject->addData($input);

		$this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
	}

	/**
	 * @test
	 */
	public function addDataAddsExplicitIndividualDenyFieldsWithSpecialExplicitValues() {
		$input = [
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'special' => 'explicitValues',
						],
					],
				],
			],
		];

		$GLOBALS['TCA'] = [
			'fooTable' => [
				'ctrl' => [
					'title' => 'fooTableTitle',
				],
				'columns' => [
					'aField' => [
						'label' => 'aFieldTitle',
						'config' => [
							'type' => 'select',
							'authMode' => 'individual',
							'items' => [
								0 => [
									'aItemTitle',
									'aItemValue',
									NULL,
									NULL,
									'EXPL_DENY',
								],
								// 1 is not selectable as allow and is always allowed
								1 => [
									'bItemTitle',
									'bItemValue',
								],
								2 => [
									'cItemTitle',
									'cItemValue',
									NULL,
									NULL,
									'EXPL_DENY',
								],
							]
						],
					],
				],
			],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.deny')->shouldBeCalled()->willReturn('denyMe');
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$expectedItems = [
			0 => [
				0 => 'fooTableTitle: aFieldTitle',
				1 => '--div--',
				2 => NULL,
				3 => NULL,
			],
			1 => [
				0 => '[denyMe] aItemTitle',
				1 => 'fooTable:aField:aItemValue:DENY',
				2 => 'status-status-permission-denied',
				3 => NULL,
			],
			2 => [
				0 => '[denyMe] cItemTitle',
				1 => 'fooTable:aField:cItemValue:DENY',
				2 => 'status-status-permission-denied',
				3 => NULL,
			],
		];

		$result = $this->subject->addData($input);

		$this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
	}

	/**
	 * @test
	 */
	public function addDataAddsLanguagesWithSpecialLanguages() {
		$input = [
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'special' => 'languages',
						],
					],
				],
			],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$languages = [
			0 => [
				'title' => 'aLangTitle',
				'uid' => 42,
				'flagIcon' => 'aFlag.gif',
			],
		];

		$translationProphecy = $this->prophesize(TranslationConfigurationProvider::class);
		GeneralUtility::addInstance(TranslationConfigurationProvider::class, $translationProphecy->reveal());
		$translationProphecy->getSystemLanguages()->shouldBeCalled()->willReturn($languages);

		$expectedItems = [
			0 => [
				0 => 'aLangTitle [42]',
				1 => 42,
				2 => 'aFlag.gif',
				3 => NULL,
			],
		];

		$result = $this->subject->addData($input);

		$this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
	}

	/**
	 * @test
	 */
	public function addDataAddsCustomOptionsWithSpecialCustom() {
		$input = [
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'special' => 'custom',
						],
					],
				],
			],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions'] = [
			'aKey' => [
				'header' => 'aHeader',
				'items' => [
					'anItemKey' => [
						0 => 'anItemTitle',
					],
				],
			]
		];

		$expectedItems = [
			0 => [
				0 => 'aHeader',
				1 => '--div--',
				NULL,
				NULL,
			],
			1 => [
				0 => 'anItemTitle',
				1 => 'aKey:anItemKey',
				2 => 'empty-empty',
				3 => NULL,
			],
		];

		$result = $this->subject->addData($input);

		$this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
	}

	/**
	 * @test
	 */
	public function addDataAddsGroupItemsWithSpecialModListGroup() {
		$input = [
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'special' => 'modListGroup',
						],
					],
				],
			],
		];

		$GLOBALS['TBE_MODULES'] = [];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);
		$languageService->moduleLabels = [
			'tabs_images' => [
				'aModule_tab' => PATH_site . 'aModuleTabIcon.gif',
			],
			'labels' => [
				'aModule_tablabel' => 'aModuleTabLabel',
				'aModule_tabdescr' => 'aModuleTabDescription',
			],
			'tabs' => [
				'aModule_tab' => 'aModuleLabel',
			]
		];

		/** @var ModuleLoader|ObjectProphecy $moduleLoaderProphecy */
		$moduleLoaderProphecy = $this->prophesize(ModuleLoader::class);
		GeneralUtility::addInstance(ModuleLoader::class, $moduleLoaderProphecy->reveal());
		$moduleLoaderProphecy->load([])->shouldBeCalled();
		$moduleLoaderProphecy->modListGroup = [
			'aModule',
		];

		$expectedItems = [
			0 => [
				0 => 'aModuleLabel',
				1 => 'aModule',
				2 => '../aModuleTabIcon.gif',
				3 => [
					'title' => 'aModuleTabLabel',
					'description' => 'aModuleTabDescription',
				],
			],
		];

		$result = $this->subject->addData($input);

		$this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
	}

	/**
	 * @test
	 */
	public function addDataAddsFileItemsWithConfiguredFileFolder() {
		$directory = $this->getUniqueId('typo3temp/test-') . '/';
		$input = [
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'fileFolder' => $directory,
							'fileFolder_extList' => 'gif',
							'fileFolder_recursions' => 1,
						],
					],
				],
			],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		mkdir(PATH_site . $directory);
		$this->testFilesToDelete[] = PATH_site . $directory;
		touch(PATH_site . $directory . 'anImage.gif');
		touch(PATH_site . $directory . 'aFile.txt');
		mkdir(PATH_site . $directory . '/subdir');
		touch(PATH_site . $directory . '/subdir/anotherImage.gif');

		$expectedItems = [
			0 => [
				0 => 'anImage.gif',
				1 => 'anImage.gif',
				2 => '../' . $directory . 'anImage.gif',
				3 => NULL,
			],
			1 => [
				0 => 'subdir/anotherImage.gif',
				1 => 'subdir/anotherImage.gif',
				2 => '../' . $directory . 'subdir/anotherImage.gif',
				3 => NULL,
			],
		];

		$result = $this->subject->addData($input);

		$this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
	}

	/**
	 * Data provider
	 */
	public function addDataReplacesMarkersInForeignTableClauseDataProvider() {
		return [
			'replace REC_FIELD' => [
				'AND fTable.title=\'###REC_FIELD_rowField###\'',
				'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.title=\'rowFieldValue\'',
				[],
			],
			'replace REC_FIELD fullQuote' => [
				'AND fTable.title=###REC_FIELD_rowField###',
				'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.title=\'rowFieldValue\'',
				[],
			],
			'replace REC_FIELD multiple markers' => [
				'AND fTable.title=\'###REC_FIELD_rowField###\' AND fTable.pid=###REC_FIELD_rowFieldTwo###',
				'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.title=\'rowFieldValue\' AND fTable.pid=\'rowFieldTwoValue\'',
				[],
			],
			'replace CURRENT_PID' => [
				'AND fTable.uid=###CURRENT_PID###',
				'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=43',
				[],
			],
			'replace CURRENT_PID integer cast' => [
				'AND fTable.uid=###CURRENT_PID###',
				'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=431',
				[
					'effectivePid' => '431string',
				],
			],
			'replace THIS_UID' => [
				'AND fTable.uid=###THIS_UID###',
				'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=42',
				[],
			],
			'replace THIS_UID integer cast' => [
				'AND fTable.uid=###THIS_UID###',
				'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=421',
				[
					'databaseRow' => [
						'uid' => '421string',
					],
				],
			],
			'replace SITEROOT' => [
				'AND fTable.uid=###SITEROOT###',
				'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=44',
				[],
			],
			'replace SITEROOT integer cast' => [
				'AND fTable.uid=###SITEROOT###',
				'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=441',
				[
					'rootline' => [
						1 => [
							'uid' => '441string',
						],
					],
				],
			],
			'replace PAGE_TSCONFIG_ID' => [
				'AND fTable.uid=###PAGE_TSCONFIG_ID###',
				'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=45',
				[],
			],
			'replace PAGE_TSCONFIG_ID integer cast' => [
				'AND fTable.uid=###PAGE_TSCONFIG_ID###',
				'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=451',
				[
					'pageTsConfigMerged' => [
						'TCEFORM.' => [
							'aTable.' => [
								'aField.' => [
									'PAGE_TSCONFIG_ID' => '451string'
								],
							],
						],
					],
				],
			],
			'replace PAGE_TSCONFIG_STR' => [
				'AND fTable.uid=\'###PAGE_TSCONFIG_STR###\'',
				'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=\'46\'',
				[],
			],
			'replace PAGE_TSCONFIG_IDLIST' => [
				'AND fTable.uid IN (###PAGE_TSCONFIG_IDLIST###)',
				'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid IN (47,48)',
				[],
			],
			'replace PAGE_TSCONFIG_IDLIST cleans list' => [
				'AND fTable.uid IN (###PAGE_TSCONFIG_IDLIST###)',
				'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid IN (471,481)',
				[
					'pageTsConfigMerged' => [
						'TCEFORM.' => [
							'aTable.' => [
								'aField.' => [
									'PAGE_TSCONFIG_IDLIST' => 'a, 471, b, 481, c',
								],
							],
						],
					],
				],
			],
		];
	}

	/**
	 * @test
	 * @dataProvider addDataReplacesMarkersInForeignTableClauseDataProvider
	 */
	public function addDataReplacesMarkersInForeignTableClause($foreignTableWhere, $expectedWhere, array $inputOverride) {
		$input = [
			'tableName' => 'aTable',
			'effectivePid' => 43,
			'databaseRow' => [
				'uid' => 42,
				'rowField' => 'rowFieldValue',
				'rowFieldTwo' => 'rowFieldTwoValue',
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'foreign_table' => 'fTable',
							'foreign_table_where' => $foreignTableWhere,
						],
					],
				]
			],
			'rootline' => [
				2 => [
					'uid' => 999,
					'is_siteroot' => 0,
				],
				1 => [
					'uid' => 44,
					'is_siteroot' => 1,
				],
				0 => [
					'uid' => 0,
					'is_siteroot' => NULL,
				],
			],
			'pageTsConfigMerged' => [
				'TCEFORM.' => [
					'aTable.' => [
						'aField.' => [
							'PAGE_TSCONFIG_ID' => 45,
							'PAGE_TSCONFIG_STR' => '46',
							'PAGE_TSCONFIG_IDLIST' => '47, 48',
						],
					],
				],
			],
		];
		ArrayUtility::mergeRecursiveWithOverrule($input, $inputOverride);

		$GLOBALS['TCA']['fTable'] = [];

		$expectedQueryArray = [
			'SELECT' => 'fTable.uid',
			'FROM' => 'fTable, pages',
			'WHERE' => $expectedWhere,
			'GROUPBY' => '',
			'ORDERBY' => '',
			'LIMIT' => '',
		];

		/** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
		$backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
		$GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
		$backendUserProphecy->getPagePermsClause(1)->shouldBeCalled()->willReturn(' 1=1');

		/** @var DatabaseConnection|ObjectProphecy $databaseProphecy */
		$databaseProphecy = $this->prophesize(DatabaseConnection::class);
		$GLOBALS['TYPO3_DB'] = $databaseProphecy->reveal();
		$databaseProphecy->sql_error()->shouldBeCalled()->willReturn(FALSE);
		$databaseProphecy->quoteStr(Argument::cetera())->willReturnArgument(0);
		$databaseProphecy->fullQuoteStr(Argument::cetera())->will(function ($args) {
			return '\'' . $args[0] . '\'';
		});
		$databaseProphecy->sql_fetch_assoc(Argument::cetera())->shouldBeCalled()->willReturn(FALSE);
		$databaseProphecy->sql_free_result(Argument::cetera())->shouldBeCalled()->willReturn(NULL);

		$databaseProphecy->exec_SELECT_queryArray($expectedQueryArray)->shouldBeCalled()->willReturn(FALSE);

		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataThrowsExceptionIfForeignTableIsNotDefinedInTca() {
		$input = [
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'foreign_table' => 'fTable',
						],
					],
				]
			],
		];

		$this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1439569743);

		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataForeignTableSplitsGroupOrderAndLimit() {
		$input = [
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'foreign_table' => 'fTable',
							'foreign_table_where' => 'AND ftable.uid=1 GROUP BY groupField ORDER BY orderField LIMIT 1,2',
						],
					],
				]
			],
			'rootline' => [],
		];

		$GLOBALS['TCA']['fTable'] = [];

		$expectedQueryArray = [
			'SELECT' => 'fTable.uid',
			'FROM' => 'fTable, pages',
			'WHERE' => 'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND ftable.uid=1',
			'GROUPBY' => 'groupField',
			'ORDERBY' => 'orderField',
			'LIMIT' => '1,2',
		];

		/** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
		$backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
		$GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
		$backendUserProphecy->getPagePermsClause(1)->shouldBeCalled()->willReturn(' 1=1');

		/** @var DatabaseConnection|ObjectProphecy $databaseProphecy */
		$databaseProphecy = $this->prophesize(DatabaseConnection::class);
		$GLOBALS['TYPO3_DB'] = $databaseProphecy->reveal();
		$databaseProphecy->sql_error()->shouldBeCalled()->willReturn(FALSE);
		$databaseProphecy->quoteStr(Argument::cetera())->willReturnArgument(0);
		$databaseProphecy->fullQuoteStr(Argument::cetera())->will(function ($args) {
			return '\'' . $args[0] . '\'';
		});
		$databaseProphecy->sql_fetch_assoc(Argument::cetera())->shouldBeCalled()->willReturn(FALSE);
		$databaseProphecy->sql_free_result(Argument::cetera())->shouldBeCalled()->willReturn(NULL);

		$databaseProphecy->exec_SELECT_queryArray($expectedQueryArray)->shouldBeCalled()->willReturn(FALSE);

		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataForeignTableQueuesFlashMessageOnDatabaseError() {
		$input = [
			'databaseRow' => [
				'aField' => 'aValue',
			],
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'foreign_table' => 'fTable',
							'items' => [
								0 => [
									0 => 'itemLabel',
									1 => 'itemValue',
									2 => NULL,
									3 => NULL,
								],
							],
						],
					],
				]
			],
			'rootline' => [],
		];

		$GLOBALS['TCA']['fTable'] = [];

		/** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
		$backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
		$GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
		$backendUserProphecy->getPagePermsClause(1)->shouldBeCalled()->willReturn(' 1=1');

		/** @var LanguageService|ObjectProphecy $languageServiceProphecy */
		$languageServiceProphecy = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageServiceProphecy->reveal();
		$languageServiceProphecy->sL(Argument::cetera())->willReturnArgument(0);

		/** @var DatabaseConnection|ObjectProphecy $databaseProphecy */
		$databaseProphecy = $this->prophesize(DatabaseConnection::class);
		$GLOBALS['TYPO3_DB'] = $databaseProphecy->reveal();
		$databaseProphecy->exec_SELECT_queryArray(Argument::cetera())->willReturn(FALSE);

		$databaseProphecy->sql_error()->shouldBeCalled()->willReturn('anError');
		$databaseProphecy->sql_free_result(Argument::cetera())->shouldBeCalled()->willReturn(NULL);

		/** @var FlashMessage|ObjectProphecy $flashMessage */
		$flashMessage = $this->prophesize(FlashMessage::class);
		GeneralUtility::addInstance(FlashMessage::class, $flashMessage->reveal());
		/** @var FlashMessageService|ObjectProphecy $flashMessageService */
		$flashMessageService = $this->prophesize(FlashMessageService::class);
		GeneralUtility::setSingletonInstance(FlashMessageService::class, $flashMessageService->reveal());
		/** @var FlashMessageQueue|ObjectProphecy $flashMessageQueue */
		$flashMessageQueue = $this->prophesize(FlashMessageQueue::class);
		$flashMessageService->getMessageQueueByIdentifier(Argument::cetera())->willReturn($flashMessageQueue->reveal());

		$flashMessageQueue->enqueue($flashMessage)->shouldBeCalled();

		$expected = $input;
		$expected['databaseRow']['aField'] = ['aValue'];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataForeignTableHandlesForegnTableRows() {
		$input = [
			'databaseRow' => [
				'aField' => 'aValue',
			],
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'foreign_table' => 'fTable',
							'foreign_table_prefix' => 'aPrefix',
							'items' => [],
						],
					],
				]
			],
			'rootline' => [],
		];

		$GLOBALS['TCA']['fTable'] = [];

		/** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
		$backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
		$GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
		$backendUserProphecy->getPagePermsClause(1)->shouldBeCalled()->willReturn(' 1=1');

		/** @var LanguageService|ObjectProphecy $languageServiceProphecy */
		$languageServiceProphecy = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageServiceProphecy->reveal();
		$languageServiceProphecy->sL(Argument::cetera())->willReturnArgument(0);

		/** @var DatabaseConnection|ObjectProphecy $databaseProphecy */
		$databaseProphecy = $this->prophesize(DatabaseConnection::class);
		$GLOBALS['TYPO3_DB'] = $databaseProphecy->reveal();
		$databaseProphecy->sql_error()->shouldBeCalled()->willReturn(FALSE);
		$databaseProphecy->sql_free_result(Argument::cetera())->willReturn(NULL);
		$databaseProphecy->exec_SELECT_queryArray(Argument::cetera())->willReturn(TRUE);

		$counter = 0;
		$databaseProphecy->sql_fetch_assoc(Argument::cetera())->shouldBeCalled()->will(function ($args) use (&$counter) {
			$counter++;
			if ($counter >= 3) {
				return FALSE;
			}
			return [
				'uid' => $counter,
				'aValue' => 'bar,',
			];
		});

		$expected = $input;
		$expected['processedTca']['columns']['aField']['config']['items'] = [
			0 => [
				0 => 'aPrefix[LLL:EXT:lang/locallang_core.xlf:labels.no_title]',
				1 => 1,
				2 => 'status-status-icon-missing',
				3 => NULL,
			],
			1 => [
				0 => 'aPrefix[LLL:EXT:lang/locallang_core.xlf:labels.no_title]',
				1 => 2,
				2 => 'status-status-icon-missing',
				3 => NULL,
			],
		];

		$expected['databaseRow']['aField'] = ['aValue'];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataRemovesItemsByKeepItemsPageTsConfig() {
		$input = [
			'databaseRow' => [
				'aField' => 'aValue',
			],
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'items' => [
								0 => [
									0 => 'keepMe',
									1 => 'keep',
									NULL,
									NULL,
								],
								1 => [
									0 => 'removeMe',
									1 => 'remove',
								],
							],
						],
					],
				]
			],
			'pageTsConfigMerged' => [
				'TCEFORM.' => [
					'aTable.' => [
						'aField.' => [
							'keepItems' => 'keep',
						],
					],
				],
			],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$expected = $input;
		$expected['databaseRow']['aField'] = ['aValue'];
		unset($expected['processedTca']['columns']['aField']['config']['items'][1]);

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataRemovesItemsByRemoveItemsPageTsConfig() {
		$input = [
			'databaseRow' => [
				'aField' => 'aValue'
			],
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'items' => [
								0 => [
									0 => 'keepMe',
									1 => 'keep',
									NULL,
									NULL,
								],
								1 => [
									0 => 'removeMe',
									1 => 'remove',
								],
							],
						],
					],
				]
			],
			'pageTsConfigMerged' => [
				'TCEFORM.' => [
					'aTable.' => [
						'aField.' => [
							'removeItems' => 'remove',
						],
					],
				],
			],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$expected = $input;
		$expected['databaseRow']['aField'] = ['aValue'];
		unset($expected['processedTca']['columns']['aField']['config']['items'][1]);

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataRemovesItemsByLanguageFieldUserRestriction() {
		$input = [
			'databaseRow' => [
				'aField' => 'aValue'
			],
			'tableName' => 'aTable',
			'processedTca' => [
				'ctrl' => [
					'languageField' => 'aField',
				],
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'items' => [
								0 => [
									0 => 'keepMe',
									1 => 'keep',
									NULL,
									NULL,
								],
								1 => [
									0 => 'removeMe',
									1 => 'remove',
								],
							],
						],
					],
				]
			],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		/** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
		$backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
		$GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
		$backendUserProphecy->checkLanguageAccess('keep')->shouldBeCalled()->willReturn(TRUE);
		$backendUserProphecy->checkLanguageAccess('remove')->shouldBeCalled()->willReturn(FALSE);

		$expected = $input;
		$expected['databaseRow']['aField'] = ['aValue'];
		unset($expected['processedTca']['columns']['aField']['config']['items'][1]);

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataRemovesItemsByUserAuthModeRestriction() {
		$input = [
			'databaseRow' => [
				'aField' => 'aValue'
			],
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'authMode' => 'explicitAllow',
							'items' => [
								0 => [
									0 => 'keepMe',
									1 => 'keep',
									NULL,
									NULL,
								],
								1 => [
									0 => 'removeMe',
									1 => 'remove',
								],
							],
						],
					],
				]
			],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		/** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
		$backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
		$GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
		$backendUserProphecy->checkAuthMode('aTable', 'aField', 'keep', 'explicitAllow')->shouldBeCalled()->willReturn(TRUE);
		$backendUserProphecy->checkAuthMode('aTable', 'aField', 'remove', 'explicitAllow')->shouldBeCalled()->willReturn(FALSE);

		$expected = $input;
		$expected['databaseRow']['aField'] = ['aValue'];
		unset($expected['processedTca']['columns']['aField']['config']['items'][1]);

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataKeepsAllPagesDoktypesForAdminUser() {
		$input = [
			'databaseRow' => [
				'doktype' => 'keep'
			],
			'tableName' => 'pages',
			'processedTca' => [
				'columns' => [
					'doktype' => [
						'config' => [
							'type' => 'select',
							'items' => [
								0 => [
									0 => 'keepMe',
									1 => 'keep',
									NULL,
									NULL,
								],
							],
						],
					],
				],
			],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		/** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
		$backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
		$GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
		$backendUserProphecy->isAdmin()->shouldBeCalled()->willReturn(TRUE);

		$expected = $input;
		$expected['databaseRow']['doktype'] = ['keep'];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataKeepsAllowedPageTypesForNonAdminUser() {
		$input = [
			'databaseRow' => [
				'doktype' => 'keep',
			],
			'tableName' => 'pages',
			'processedTca' => [
				'columns' => [
					'doktype' => [
						'config' => [
							'type' => 'select',
							'items' => [
								0 => [
									0 => 'keepMe',
									1 => 'keep',
									NULL,
									NULL,
								],
								1 => [
									0 => 'removeMe',
									1 => 'remove',
								],
							],
						],
					],
				],
			],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		/** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
		$backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
		$GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
		$backendUserProphecy->isAdmin()->shouldBeCalled()->willReturn(FALSE);
		$backendUserProphecy->groupData = [
			'pagetypes_select' => 'foo,keep,anotherAllowedDoktype',
		];

		$expected = $input;
		$expected['databaseRow']['doktype'] = ['keep'];
		unset($expected['processedTca']['columns']['doktype']['config']['items'][1]);

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataCallsItemsProcFunc() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => 'aValue'
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'items' => [],
							'itemsProcFunc' => function (array $parameters, $pObj) {
								$parameters['items'] = [
									0 => [
										0 => 'aLabel',
										1 => 'aValue',
										2 => NULL,
										3 => NULL,
									],
								];
							},
						],
					],
				],
			],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$expected = $input;
		$expected['databaseRow']['aField'] = ['aValue'];
		$expected['processedTca']['columns']['aField']['config'] = [
			'type' => 'select',
			'items' => [
				0 => [
					0 => 'aLabel',
					1 => 'aValue',
					2 => NULL,
					3 => NULL,
				],
			],
		];

		$this->assertSame($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function addDataItemsProcFuncReceivesParameters() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => 'aValue',
			],
			'pageTsConfigMerged' => [
				'TCEFORM.' => [
					'aTable.' => [
						'aField.' => [
							'itemsProcFunc.' => [
								'itemParamKey' => 'itemParamValue',
							],
						]
					],
				],
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'aKey' => 'aValue',
							'items' => [
								0 => [
									0 => 'aLabel',
									1 => 'aValue',
								],
							],
							'itemsProcFunc' => function (array $parameters, $pObj) {
								if ($parameters['items'] !== [ 0 => [ 'aLabel', 'aValue'] ]
									|| $parameters['config']['aKey'] !== 'aValue'
									|| $parameters['TSconfig'] !== [ 'itemParamKey' => 'itemParamValue' ]
									|| $parameters['table'] !== 'aTable'
									|| $parameters['row'] !== [ 'aField' => 'aValue' ]
									|| $parameters['field'] !== 'aField'
								) {
									throw new \UnexpectedValueException('broken', 1438604329);
								}
							},
						],
					],
				],
			],
		];

		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);
		/** @var FlashMessage|ObjectProphecy $flashMessage */
		$flashMessage = $this->prophesize(FlashMessage::class);
		GeneralUtility::addInstance(FlashMessage::class, $flashMessage->reveal());
		/** @var FlashMessageService|ObjectProphecy $flashMessageService */
		$flashMessageService = $this->prophesize(FlashMessageService::class);
		GeneralUtility::setSingletonInstance(FlashMessageService::class, $flashMessageService->reveal());
		/** @var FlashMessageQueue|ObjectProphecy $flashMessageQueue */
		$flashMessageQueue = $this->prophesize(FlashMessageQueue::class);
		$flashMessageService->getMessageQueueByIdentifier(Argument::cetera())->willReturn($flashMessageQueue->reveal());

		// itemsProcFunc must NOT have raised an exception
		$flashMessageQueue->enqueue($flashMessage)->shouldNotBeCalled();

		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataItemsProcFuncEnqueuesFlashMessageOnException() {
		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => 'aValue',
			],
			'pageTsConfigMerged' => [
				'TCEFORM.' => [
					'aTable.' => [
						'aField.' => [
							'itemsProcFunc.' => [
								'itemParamKey' => 'itemParamValue',
							],
						]
					],
				],
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'aKey' => 'aValue',
							'items' => [
								0 => [
									0 => 'aLabel',
									1 => 'aValue',
								],
							],
							'itemsProcFunc' => function (array $parameters, $pObj) {
								throw new \UnexpectedValueException('anException', 1438604329);
							},
						],
					],
				],
			],
		];

		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		/** @var FlashMessage|ObjectProphecy $flashMessage */
		$flashMessage = $this->prophesize(FlashMessage::class);
		GeneralUtility::addInstance(FlashMessage::class, $flashMessage->reveal());
		/** @var FlashMessageService|ObjectProphecy $flashMessageService */
		$flashMessageService = $this->prophesize(FlashMessageService::class);
		GeneralUtility::setSingletonInstance(FlashMessageService::class, $flashMessageService->reveal());
		/** @var FlashMessageQueue|ObjectProphecy $flashMessageQueue */
		$flashMessageQueue = $this->prophesize(FlashMessageQueue::class);
		$flashMessageService->getMessageQueueByIdentifier(Argument::cetera())->willReturn($flashMessageQueue->reveal());

		$flashMessageQueue->enqueue($flashMessage)->shouldBeCalled();

		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function addDataTranslatesItemLabelsFromPageTsConfig() {
		$input = [
			'databaseRow' => [
				'aField' => 'aValue',
			],
			'tableName' => 'aTable',
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'items' => [
								0 => [
									0 => 'aLabel',
									1 => 'aValue',
									NULL,
									NULL,
								],
							],
						],
					],
				],
			],
			'pageTsConfigMerged' => [
				'TCEFORM.' => [
					'aTable.' => [
						'aField.' => [
							'altLabels.' => [
								'aValue' => 'labelOverride',
							],
						]
					],
				],
			],
		];

		/** @var LanguageService|ObjectProphecy $languageService */
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL('aLabel')->willReturnArgument(0);

		$languageService->sL('labelOverride')->shouldBeCalled()->willReturnArgument(0);

		$expected = $input;
		$expected['databaseRow']['aField'] = ['aValue'];
		$expected['processedTca']['columns']['aField']['config']['items'][0][0] = 'labelOverride';

		$this->assertSame($expected, $this->subject->addData($input));
		$this->subject->addData($input);
	}

	/**
	 * @test
	 */
	public function processSelectFieldValueSetsMmForeignRelationValues() {
		$GLOBALS['TCA']['foreignTable'] = [];

		/** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
		$backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
		$GLOBALS['BE_USER'] = $backendUserProphecy->reveal();

		/** @var DatabaseConnection|ObjectProphecy $database */
		$database = $this->prophesize(DatabaseConnection::class);
		$GLOBALS['TYPO3_DB'] = $database->reveal();

		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'uid' => 42,
				// Two connected rows
				'aField' => 2,
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'maxitems' => 999,
							'foreign_table' => 'foreignTable',
							'MM' => 'aTable_foreignTable_mm',
							'items' => [],
						],
					],
				],
			],
		];
		$fieldConfig = $input['processedTca']['columns']['aField']['config'];
		/** @var RelationHandler|ObjectProphecy $relationHandlerProphecy */
		$relationHandlerProphecy = $this->prophesize(RelationHandler::class);
		GeneralUtility::addInstance(RelationHandler::class, $relationHandlerProphecy->reveal());

		$relationHandlerUids = [
			23,
			24
		];

		$relationHandlerProphecy->start(2, 'foreignTable', 'aTable_foreignTable_mm', 42, 'aTable', $fieldConfig)->shouldBeCalled();
		$relationHandlerProphecy->getValueArray()->shouldBeCalled()->willReturn($relationHandlerUids);

		$expected = $input;
		$expected['databaseRow']['aField'] = $relationHandlerUids;

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function processSelectFieldValueSetsForeignRelationValues() {
		$GLOBALS['TCA']['foreignTable'] = [];

		/** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
		$backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
		$GLOBALS['BE_USER'] = $backendUserProphecy->reveal();

		/** @var DatabaseConnection|ObjectProphecy $database */
		$database = $this->prophesize(DatabaseConnection::class);
		$GLOBALS['TYPO3_DB'] = $database->reveal();

		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'uid' => 42,
				// Two connected rows
				'aField' => '22,23,24,25',
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'maxitems' => 999,
							'foreign_table' => 'foreignTable',
							'items' => [],
						],
					],
				],
			],
		];
		$fieldConfig = $input['processedTca']['columns']['aField']['config'];
		/** @var RelationHandler|ObjectProphecy $relationHandlerProphecy */
		$relationHandlerProphecy = $this->prophesize(RelationHandler::class);
		GeneralUtility::addInstance(RelationHandler::class, $relationHandlerProphecy->reveal());

		$relationHandlerUids = [
			23,
			24
		];

		$relationHandlerProphecy->start('22,23,24,25', 'foreignTable', '', 42, 'aTable', $fieldConfig)->shouldBeCalled();
		$relationHandlerProphecy->getValueArray()->shouldBeCalled()->willReturn($relationHandlerUids);

		$expected = $input;
		$expected['databaseRow']['aField'] = $relationHandlerUids;

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function processSelectFieldValueRemovesInvalidDynamicValues() {
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$GLOBALS['TCA']['foreignTable'] = [];

		/** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
		$backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
		$GLOBALS['BE_USER'] = $backendUserProphecy->reveal();

		/** @var DatabaseConnection|ObjectProphecy $database */
		$database = $this->prophesize(DatabaseConnection::class);
		$GLOBALS['TYPO3_DB'] = $database->reveal();

		$relationHandlerProphecy = $this->prophesize(RelationHandler::class);
		GeneralUtility::addInstance(RelationHandler::class, $relationHandlerProphecy->reveal());
		$relationHandlerProphecy->start(Argument::cetera())->shouldBeCalled();
		$relationHandlerProphecy->getValueArray(Argument::cetera())->shouldBeCalled()->willReturn([1]);

		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => '1,2,bar,foo',
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'foreign_table' => 'foreignTable',
							'maxitems' => 999,
							'items' => [
								['foo', 'foo', NULL, NULL],
							],
						],
					],
				],
			],
		];

		$expected = $input;
		$expected['databaseRow']['aField'] = ['foo', 1];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function processSelectFieldValueKeepsValuesFromStaticItems() {
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => 'foo,bar',
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'maxitems' => 999,
							'items' => [
								['foo', 'foo', NULL, NULL],
								['bar', 'bar', NULL, NULL],
							],
						],
					],
				],
			],
		];

		$expected = $input;
		$expected['databaseRow']['aField'] = [
			'foo',
			'bar'
		];

		$this->assertEquals($expected, $this->subject->addData($input));
	}

	/**
	 * @test
	 */
	public function processSelectFieldValueDoesNotCallRelationManagerForStaticOnlyItems() {
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$relationHandlerProphecy = $this->prophesize(RelationHandler::class);
		GeneralUtility::addInstance(RelationHandler::class, $relationHandlerProphecy->reveal());
		$relationHandlerProphecy->start(Argument::cetera())->shouldNotBeCalled();
		$relationHandlerProphecy->getValueArray(Argument::cetera())->shouldNotBeCalled();

		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => '1,2,bar,foo',
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'maxitems' => 999,
							'items' => [
								['foo', 'foo', NULL, NULL],
							],
						],
					],
				],
			],
		];

		$expected = $input;
		$expected['databaseRow']['aField'] = ['foo'];

		$this->assertEquals($expected, $this->subject->addData($input));

	}

	/**
	 * @test
	 */
	public function processSelectFieldValueDoesNotTouchValueForSingleSelects() {
		$languageService = $this->prophesize(LanguageService::class);
		$GLOBALS['LANG'] = $languageService->reveal();
		$languageService->sL(Argument::cetera())->willReturnArgument(0);

		$relationHandlerProphecy = $this->prophesize(RelationHandler::class);
		GeneralUtility::addInstance(RelationHandler::class, $relationHandlerProphecy->reveal());
		$relationHandlerProphecy->start(Argument::cetera())->shouldNotBeCalled();
		$relationHandlerProphecy->getValueArray(Argument::cetera())->shouldNotBeCalled();

		$input = [
			'tableName' => 'aTable',
			'databaseRow' => [
				'aField' => '1,2,bar,foo',
			],
			'processedTca' => [
				'columns' => [
					'aField' => [
						'config' => [
							'type' => 'select',
							'maxitems' => 1,
							'items' => [
								['foo', 'foo', NULL, NULL],
							],
						],
					],
				],
			],
		];

		$expected = $input;
		$expected['databaseRow']['aField'] = ['1,2,bar,foo'];

		$this->assertEquals($expected, $this->subject->addData($input));

	}
}
