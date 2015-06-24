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


		'required_1' => array(
			'exclude' => 1,
			'label' => 'REQUIRED: 1 type input, eval required',
			'config' => array(
				'type' => 'input',
				'eval' => 'required',
			),
		),
		'required_2' => array(
			'exclude' => 1,
			'label' => 'REQUIRED: 2 type input, eval required',
			'config' => array(
				'type' => 'input',
				'eval' => 'required',
			),
		),
		'required_3' => array(
			'exclude' => 1,
			'label' => 'REQUIRED: 3 type input, eval required',
			'config' => array(
				'type' => 'input',
				'eval' => 'required',
			),
		),
		'required_4' => array(
			'exclude' => 1,
			'label' => 'REQUIRED: 4 type text, eval required',
			'config' => array(
				'type' => 'text',
				'eval' => 'required',
			),
		),
		'required_5' => array(
			'exclude' => 1,
			'label' => 'REQUIRED: 5 type select, multiple, maxitems=5, minitems=2',
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
		'required_6' => array(
			'exclude' => 1,
			'label' => 'REQUIRED: 6 type flex, group minitems 1',
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
											<label>minitems 1</label>
											<config>
												<type>select</type>
												<foreign_table>tx_styleguide_forms_staticdata</foreign_table>
												<rootLevel>1</rootLevel>
												<size>5</size>
												<minitems>1</minitems>
												<maxitems>999</maxitems>
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
		'required_7' => array(
			'exclude' => 1,
			'label' => 'REQUIRED: 7 type flex, tab, section container, inline',
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
														<foreign_table>tx_styleguide_required_required_7_flex_inline_1_child1</foreign_table>
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
		'required_8' => array(
			'exclude' => 1,
			'label' => 'REQUIRED: 8: Inline, minitems 1, maxitems 3',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_styleguide_required_required_8_child1',
				'foreign_field' => 'parentid',
				'foreign_table_field' => 'parenttable',
				'minitems' => 1,
				'maxitems' => 3,
			),
		),
		'required_9' => array(
			'exclude' => 1,
			'label' => 'REQUIRED: 9: RTE',
			'config' => array(
				'type' => 'text',
			),
			'defaultExtras' => 'richtext[*]:rte_transform[mode=ts_css]',
		),
		'required_10' => array(
			'exclude' => 1,
			'label' => 'REQUIRED 10: Input, date',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim,required,date',
			),
		),
		'required_11' => array(
			'exclude' => 1,
			'label' => 'REQUIRED 11: Input, link wizard',
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


	),


	'interface' => array(
		'showRecordFieldList' => '
			hidden,starttime,endtime,
			required_1, required_2, required_3, required_4, required_5,
			required_6, required_7, required_8, required_9,
			required_10, required_11,
		',
	),

	'types' => array(
		'0' => array(
			'showitem' => '
				--div--;Required input fields,
					required_1, required_10, required_11, --palette--;Required in palette;required_2_palette,
				--div--;Required other fields,
					required_4, required_5, required_6, required_8, required_7, required_9,
			',
		),
	),

	'palettes' => array(
		'required_2_palette' => array(
			'showitem' => 'required_2, required_3',
		),
	),

);
