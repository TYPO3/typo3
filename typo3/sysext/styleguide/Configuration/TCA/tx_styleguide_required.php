<?php
return array(
	'ctrl' => array (
		'title' => 'Form engine tests - Required fields',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'sortby' => 'sorting',
		'default_sortby' => 'ORDER BY crdate',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('styleguide') . 'Resources/Public/Icons/tx_styleguide_required.png',

		'versioningWS' => TRUE,
		'origUid' => 't3_origuid',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l10n_parent',
		'transOrigDiffSourceField' => 'l10n_diffsource',

		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		),
	),

	'columns' => array(
		'hidden' => array (
			'exclude' => 1,
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'Disable'
					),
				),
			),
		),
		'starttime' => array(
			'exclude' => 1,
			'label' => 'Publish Date',
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0'
			),
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly'
		),
		'endtime' => array(
			'exclude' => 1,
			'label' => 'Expiration Date',
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0',
				'range' => array(
					'upper' => mktime(0, 0, 0, 12, 31, 2020)
				)
			),
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly'
		),


		'notrequired_1' => array(
			'exclude' => 1,
			'label' => 'NOT REQUIRED 1: simple input',
			'config' => array(
				'type' => 'input',
			),
		),


		'input_1' => array(
			'exclude' => 1,
			'label' => 'INPUT 1: eval required',
			'config' => array(
				'type' => 'input',
				'eval' => 'required',
			),
		),
		'input_2' => array(
			'exclude' => 1,
			'label' => 'INPUT 2: eval required',
			'config' => array(
				'type' => 'input',
				'eval' => 'required',
			),
		),
		'input_3' => array(
			'exclude' => 1,
			'label' => 'INPUT 3: eval required',
			'config' => array(
				'type' => 'input',
				'eval' => 'required',
			),
		),
		'input_4' => array(
			'exclude' => 1,
			'label' => 'INPUT 4: eval required,trim,date',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim,required,date',
			),
		),
		'input_5' => array(
			'exclude' => 1,
			'label' => 'INPUT 5: eval required, link wizard',
			'config' => array(
				'type' => 'input',
				'size' => 60,
				'eval' => 'trim,required',
				'wizards' => array(
					'link' => array(
						'type' => 'popup',
						'title' => 'a title',
						'icon' => 'link_popup.gif',
						'module' => array(
							'name' => 'wizard_element_browser',
							'urlParameters' => array(
								'mode' => 'wizard',
								'act' => 'file|url',
							),
						),
						'params' => array(
							'blindLinkOptions' => 'page,folder,mail,spec',
							'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
						),
					),
				),
			),
		),


		'text_1' => array(
			'exclude' => 1,
			'label' => 'TEXT 1: eval required',
			'config' => array(
				'type' => 'text',
				'eval' => 'required',
			),
		),


		'select_1' => array(
			'exclude' => 1,
			'label' => 'SELECT 1: multiple, maxitems=5, minitems=2',
			'config' => array(
				'type' => 'select',
				'size' => 3,
				'maxitems' => 5,
				'minitems' => 2,
				'items' => array(
					array('foo1', 1),
					array('foo2', 2),
					array('foo3', 3),
					array('foo4', 4),
					array('foo5', 5),
					array('foo6', 6),
				),
			),
		),


		'group_1' => array(
			'exclude' => 1,
			'label' => 'GROUP 1: minitems = 1, maxitems=3',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_styleguide_forms_staticdata',
				'minitems' => 1,
				'maxitems' => 3,
			),
		),
		'group_2' => array(
			'exclude' => 1,
			'label' => 'GROUP 2: minitems = 1, maxitems=1, size=1',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_styleguide_forms_staticdata',
				'show_thumbs' => TRUE,
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
			),
		),


		'rte_1' => array(
			'exclude' => 1,
			'label' => 'RTE 1: required',
			'config' => array(
				'type' => 'text',
				'eval' => 'required',
			),
			'defaultExtras' => 'richtext[*]:rte_transform[mode=ts_css]',
		),
		'rte_2' => array(
			'exclude' => 1,
			'label' => 'RTE 2: Required in inline',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_styleguide_required_rte_2_inline_1_child1',
				'foreign_field' => 'parentid',
				'foreign_table_field' => 'parenttable',
			),
		),


		'inline_1' => array(
			'exclude' => 1,
			'label' => 'INLINE 1: minitems 1, maxitems 3',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_styleguide_required_inline_1_child1',
				'foreign_field' => 'parentid',
				'foreign_table_field' => 'parenttable',
				'minitems' => 1,
				'maxitems' => 3,
			),
		),
		'inline_2' => array(
			'exclude' => 1,
			'label' => 'INLINE 2: single required field',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_styleguide_required_inline_2_child1',
				'foreign_field' => 'parentid',
				'foreign_table_field' => 'parenttable',
			),
		),
		'inline_3' => array(
			'exclude' => 1,
			'label' => 'INLINE 3: minitems 1, maxitems 3, elements with single required field',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_styleguide_required_inline_3_child1',
				'foreign_field' => 'parentid',
				'foreign_table_field' => 'parenttable',
				'minitems' => 1,
				'maxitems' => 3,
			),
		),


		'flex_1' => array(
			'exclude' => 1,
			'label' => 'FLEX 1: input required',
			'config' => array(
				'type' => 'flex',
				'ds' => array(
					'default' => '
						<T3DataStructure>
							<ROOT>
								<type>array</type>
								<el>
									<select_1>
										<TCEforms>
											<label>input required</label>
											<config>
												<type>text</type>
												<eval>required</eval>
											</config>
										</TCEforms>
									</select_1>
								</el>
							</ROOT>
						</T3DataStructure>
					',
				),
			),
		),
		'flex_2' => array(
			'exclude' => 1,
			'label' => 'FLEX 2: tabs, section container, inline',
			'config' => array(
				'type' => 'flex',
				'ds' => array(
					'default' => '
						<T3DataStructure>
							<sheets>
								<sGeneral>
									<ROOT>
										<TCEforms>
											<sheetTitle>Single required element</sheetTitle>
										</TCEforms>
										<type>array</type>
										<el>
											<input_1>
												<TCEforms>
													<label>input, required</label>
													<config>
														<type>input</type>
														<eval>required</eval>
													</config>
												</TCEforms>
											</input_1>
										</el>
									</ROOT>
								</sGeneral>
								<sSections>
									<ROOT>
										<TCEforms>
											<sheetTitle>Section</sheetTitle>
										</TCEforms>
										<type>array</type>
										<el>
											<section_1>
												<title>section_1</title>
												<type>array</type>
												<section>1</section>
												<el>
													<container_1>
														<type>array</type>
														<title>1 required field</title>
														<el>
															<input_1>
																<TCEforms>
																	<label>input, required</label>
																	<config>
																		<type>input</type>
																		<eval>required</eval>
																	</config>
																</TCEforms>
															</input_1>
														</el>
													</container_1>
												</el>
											</section_1>
										</el>
									</ROOT>
								</sSections>
								<sInline>
									<ROOT>
										<TCEforms>
											<sheetTitle>Inline</sheetTitle>
										</TCEforms>
										<type>array</type>
										<el>
											<inline_1>
												<TCEforms>
													<label>inline_1 to one required field</label>
													<config>
														<type>inline</type>
														<foreign_table>tx_styleguide_required_flex_2_inline_1_child1</foreign_table>
														<foreign_field>parentid</foreign_field>
														<foreign_table_field>parenttable</foreign_table_field>
													</config>
												</TCEforms>
											</inline_1>
										</el>
									</ROOT>
								</sInline>
							</sheets>
						</T3DataStructure>
					',
				),
			),
		),
	),


	'interface' => array(
		'showRecordFieldList' => '
			hidden,starttime,endtime,
			notrequired_1,
			input_1, input_2, input_3, input_4, input_5
			text_1,
			select_1,
			group_1, group_2,
			rte_1, rte_2,
			inline_1, inline_2, inline_3,
			flex_1, flex_2,
		',
	),

	'types' => array(
		'0' => array(
			'showitem' => '
				--div--;Not required,
					notrequired_1,
				--div--;Input,
					input_1, input_4, input_5, --palette--;Required in palette;input_palette,
				--div--;Text,
					text_1,
				--div--;Select,
					select_1,
				--div--;Group,
					group_1, group_2,
				--div--;Rte,
					rte_1, rte_2,
				--div--;Inline,
					inline_1, inline_2, inline_3,
				--div--;Flex,
					flex_1, flex_2,
			',
		),
	),

	'palettes' => array(
		'input_palette' => array(
			'showitem' => 'input_2, input_3',
		),
	),

);
