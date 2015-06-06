<?php
namespace TYPO3\CMS\Core\Tests\Unit\Migrations;

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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Migrations\TcaMigration;

/**
 * Test case
 */
class TcaMigrationTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function migrateReturnsGivenArrayUnchangedIfNoMigrationNeeded() {
		$input = $expected = array(
			'aTable' => array(
				'ctrl' => array(
					'aKey' => 'aValue',
				),
				'columns' => array(
					'aField' => array(
						'label' => 'foo',
						'config' => array(
							'type' => 'aType',
							'lolli' => 'did this',
						)
					),
				),
				'types' => array(
					0 => array(
						'showitem' => 'this,should;stay;this,too',
					),
				),
			),
		);
		$subject = new TcaMigration();
		$this->assertEquals($expected, $subject->migrate($input));
	}

	/**
	 * @test
	 */
	public function migrateChangesT3editorWizardToT3editorRenderTypeIfNotEnabledByTypeConfig() {
		$input = array(
			'aTable' => array(
				'columns' => array(
					'bodytext' => array(
						'exclude' => 1,
						'label' => 'aLabel',
						'config' => array(
							'type' => 'text',
							'rows' => 42,
							'wizards' => array(
								't3editor' => array(
									'type' => 'userFunc',
									'userFunc' => 'TYPO3\CMS\T3editor\FormWizard->main',
									'title' => 't3editor',
									'icon' => 'wizard_table.gif',
									'module' => array(
										'name' => 'wizard_table'
									),
									'params' => array(
										'format' => 'html',
										'style' => 'width:98%; height: 60%;'
									),
								),
							),
						),
					),
				),
			),
		);
		$expected = array(
			'aTable' => array(
				'columns' => array(
					'bodytext' => array(
						'exclude' => 1,
						'label' => 'aLabel',
						'config' => array(
							'type' => 'text',
							'renderType' => 't3editor',
							'format' => 'html',
							'rows' => 42,
						),
					),
				),
			),
		);
		$subject = new TcaMigration();
		$this->assertEquals($expected, $subject->migrate($input));
	}

	/**
	 * @test
	 */
	public function migrateDropsStylePointerFromShowItem() {
		$input = array(
			'aTable' => array(
				'types' => array(
					0 => array(
						'showitem' => 'aField,anotherField;with;palette;;and-style-pointer,thirdField',
					),
					1 => array(
						'showitem' => 'aField,;;;;only-a-style-pointer,anotherField',
					),
				),
			),
		);
		$expected = array(
			'aTable' => array(
				'types' => array(
					0 => array(
						'showitem' => 'aField,anotherField;with;palette,thirdField',
					),
					1 => array(
						'showitem' => 'aField,anotherField',
					),
				),
			),
		);
		$subject = new TcaMigration();
		$this->assertEquals($expected, $subject->migrate($input));
	}

	/**
	 * @test
	 */
	public function migrateMovesSpecialConfigurationToColumnsOverridesDefaultExtras() {
		$input = array(
			'aTable' => array(
				'types' => array(
					0 => array(
						'showitem' => 'aField,anotherField;with;palette;special:configuration,thirdField',
					),
				),
			),
		);
		$expected = array(
			'aTable' => array(
				'types' => array(
					0 => array(
						'showitem' => 'aField,anotherField;with;palette,thirdField',
						'columnsOverrides' => array(
							'anotherField' => array(
								'defaultExtras' => 'special:configuration',
							),
						),
					),
				),
			),
		);
		$subject = new TcaMigration();
		$this->assertEquals($expected, $subject->migrate($input));
	}

	/**
	 * @test
	 */
	public function migrateMovesSpecialConfigurationToColumnsOverridesDefaultExtrasAndMergesExistingDefaultExtras() {
		$input = array(
			'aTable' => array(
				'columns' => array(
					'anotherField' => array(
						'defaultExtras' => 'some:values',
					),
				),
				'types' => array(
					0 => array(
						'showitem' => 'aField,anotherField;with;palette;special:configuration,thirdField',
					),
				),
			),
		);
		$expected = array(
			'aTable' => array(
				'columns' => array(
					'anotherField' => array(
						'defaultExtras' => 'some:values',
					),
				),
				'types' => array(
					0 => array(
						'showitem' => 'aField,anotherField;with;palette,thirdField',
						'columnsOverrides' => array(
							'anotherField' => array(
								'defaultExtras' => 'some:values:special:configuration',
							),
						),
					),
				),
			),
		);
		$subject = new TcaMigration();
		$this->assertEquals($expected, $subject->migrate($input));
	}

	/**
	 * @test
	 */
	public function migrateChangesT3editorWizardThatIsEnabledByTypeConfigToRenderTypeInColmnnsOverrides() {
		$input = array(
			'aTable' => array(
				'columns' => array(
					'bodytext' => array(
						'exclude' => 1,
						'label' => 'aLabel',
						'config' => array(
							'type' => 'text',
							'rows' => 42,
							'wizards' => array(
								't3editorHtml' => array(
									'type' => 'userFunc',
									'userFunc' => 'TYPO3\CMS\T3editor\FormWizard->main',
									'enableByTypeConfig' => 1,
									'title' => 't3editor',
									'icon' => 'wizard_table.gif',
									'module' => array(
										'name' => 'wizard_table'
									),
									'params' => array(
										'format' => 'html',
										'style' => 'width:98%; height: 60%;'
									),
								),
								't3editorTypoScript' => array(
									'type' => 'userFunc',
									'userFunc' => 'TYPO3\CMS\T3editor\FormWizard->main',
									'enableByTypeConfig' => 1,
									'title' => 't3editor',
									'icon' => 'wizard_table.gif',
									'module' => array(
										'name' => 'wizard_table'
									),
									'params' => array(
										'format' => 'typoscript',
										'style' => 'width:98%; height: 60%;'
									),
								),
							),
						),
					),
				),
				'types' => array(
					'firstType' => array(
						'showitem' => 'foo,bodytext;;;wizards[t3editorTypoScript|someOtherWizard],bar',
					),
					'secondType' => array(
						'showitem' => 'foo,bodytext;;;nowrap:wizards[t3editorHtml], bar',
					),
				),
			),
		);
		$expected = array(
			'aTable' => array(
				'columns' => array(
					'bodytext' => array(
						'exclude' => 1,
						'label' => 'aLabel',
						'config' => array(
							'type' => 'text',
							'rows' => 42,
						),
					),
				),
				'types' => array(
					'firstType' => array(
						'showitem' => 'foo,bodytext,bar',
						'columnsOverrides' => array(
							'bodytext' => array(
								'config' => array(
									'format' => 'typoscript',
									'renderType' => 't3editor',
								),
								'defaultExtras' => 'wizards[someOtherWizard]',
							),
						),
					),
					'secondType' => array(
						'showitem' => 'foo,bodytext,bar',
						'columnsOverrides' => array(
							'bodytext' => array(
								'config' => array(
									'format' => 'html',
									'renderType' => 't3editor',
								),
								'defaultExtras' => 'nowrap',
							),
						),
					),
				),
			),
		);
		$subject = new TcaMigration();
		$this->assertEquals($expected, $subject->migrate($input));
	}

	/**
	 * @test
	 */
	public function migrateRemovesAnUnusedT3edtiorDefinitionIfEnabledByTypeConfig() {
		$input = array(
			'aTable' => array(
				'columns' => array(
					'bodytext' => array(
						'exclude' => 1,
						'label' => 'aLabel',
						'config' => array(
							'type' => 'text',
							'rows' => 42,
							'wizards' => array(
								't3editorHtml' => array(
									'type' => 'userFunc',
									'userFunc' => 'TYPO3\CMS\T3editor\FormWizard->main',
									'enableByTypeConfig' => 1,
									'title' => 't3editor',
									'icon' => 'wizard_table.gif',
									'module' => array(
										'name' => 'wizard_table'
									),
									'params' => array(
										'format' => 'html',
										'style' => 'width:98%; height: 60%;'
									),
								),
							),
						),
					),
				),
			),
		);
		$expected = array(
			'aTable' => array(
				'columns' => array(
					'bodytext' => array(
						'exclude' => 1,
						'label' => 'aLabel',
						'config' => array(
							'type' => 'text',
							'rows' => 42,
						),
					),
				),
			),
		);
		$subject = new TcaMigration();
		$this->assertEquals($expected, $subject->migrate($input));
	}

	/**
	 * @test
	 */
	public function migrateSpecialConfigurationAndRemoveShowItemStylePointerConfigDoesNotAddMessageIfOnlySyntaxChanged() {
		$input = array(
			'aTable' => array(
				'columns' => array(
					'anotherField' => array(
					),
				),
				'types' => array(
					0 => array(
						'showitem' => 'aField;;;',
					),
				),
			),
		);
		$subject = new TcaMigration();
		$subject->migrate($input);
		$this->assertEmpty($subject->getMessages());
	}
}
