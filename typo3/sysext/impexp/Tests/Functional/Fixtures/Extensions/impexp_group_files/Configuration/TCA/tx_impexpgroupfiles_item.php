<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

return array(
	'ctrl' => array(
		'title'	=> 'LLL:EXT:impexp_group_files/Resources/Private/Language/locallang_db.xlf:tx_impexpgroupfiles_item',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'dividers2tabs' => TRUE,
		'sortby' => 'sorting',

		'versioningWS' => 2,
		'versioning_followPages' => TRUE,

		'origUid' => 't3_origuid',

		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		),
		'searchFields' => 'price,currency,symbol,',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('impexp_group_files') . 'Resources/Public/Icons/icon_tx_impexpgroupfiles_item.gif'
	),
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, title, images, image_references, flexform',
	),
	'types' => array(
		'1' => array('showitem' => 'hidden, title, images, image_references, flexform, --div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,starttime, endtime'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	),
	'columns' => array(

		't3ver_label' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'max' => 255,
			)
		),

		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
			'config' => array(
				'type' => 'check',
			),
		),
		'starttime' => array(
			'exclude' => 1,
			'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
			'config' => array(
				'type' => 'input',
				'size' => 13,
				'max' => 20,
				'eval' => 'datetime',
				'checkbox' => 0,
				'default' => 0,
				'range' => array(
					'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y'))
				),
			),
		),
		'endtime' => array(
			'exclude' => 1,
			'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => 13,
				'max' => 20,
				'eval' => 'datetime',
				'checkbox' => 0,
				'default' => 0,
				'range' => array(
					'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y'))
				),
			),
		),
		'title' => array(
			'label' => 'LLL:EXT:impexp_group_files/Resources/Private/Language/locallang_db.xml:tx_impexpgroupfiles_item_title',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'required'
			)
		),
		'images' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:impexp_group_files/Resources/Private/Language/locallang_db.xml:tx_impexpgroupfiles_item_images',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'disallowed' => 'php',
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
				'uploadfolder' => 'uploads/tx_impexpgroupfiles',
				'size' => 5,
				'maxitems' => 5,
				'show_thumbs' => 1,
			),
		),
		'image_references' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:impexp_group_files/Resources/Private/Language/locallang_db.xml:tx_impexpgroupfiles_item_image_references',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'file_reference',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'disallowed' => 'php',
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
				'size' => 5,
				'maxitems' => 5,
				'show_thumbs' => 1,
			),
		),
		'flexform' => array(
			'label' => 'LLL:EXT:impexp_group_files/Resources/Private/Language/locallang_db.xml:tx_impexpgroupfiles_item_flexform',
			'config' => array(
				'type' => 'flex',
				'ds' => array(
					'default' => '<T3DataStructure>
							<meta>
								<langDisable>1</langDisable>
							</meta>
							<sheets>
								<sDEF>
									<ROOT>
										<TCEforms>
											<sheetTitle>Default</sheetTitle>
										</TCEforms>
										<type>array</type>
										<el>
											<link>
												<TCEforms>
													<label>Link</label>
													<config>
														<type>input</type>
														<size>50</size>
														<max>256</max>
														<eval>trim</eval>
														<softref>typolink</softref>
														<wizards type="array">
															<_PADDING type="integer">2</_PADDING>
															<link type="array">
																<type>popup</type>
																<title>Link</title>
																<icon>link_popup.gif</icon>
																<module type="array">
																	<name>wizard_element_browser</name>
																	<urlParameters type="array">
																		<mode>wizard</mode>
																	</urlParameters>
																</module>
																<JSopenParams>height=300,width=500,status=0,menubar=0,scrollbars=1</JSopenParams>
															</link>
														</wizards>
													</config>
												</TCEforms>
											</link>
											<images>
												<TCEforms>
													<label>Images</label>
													<config>
														<type>group</type>
														<internal_type>file</internal_type>
														<allowed>' . $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] . '</allowed>
														<disallowed>php</disallowed>
														<max_size>' . $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'] . '</max_size>
														<uploadfolder>uploads/tx_impexpgroupfiles</uploadfolder>
														<size>5</size>
														<maxitems>5</maxitems>
														<show_thumbs>1</show_thumbs>
													</config>
												</TCEforms>
											</images>
											<image_references>
												<TCEforms>
													<label>Image References</label>
													<config>
														<type>group</type>
														<internal_type>file_reference</internal_type>
														<allowed>' . $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] . '</allowed>
														<disallowed>php</disallowed>
														<max_size>' . $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'] . '</max_size>
														<uploadfolder>uploads/tx_impexpgroupfiles</uploadfolder>
														<size>5</size>
														<maxitems>5</maxitems>
														<show_thumbs>1</show_thumbs>
													</config>
												</TCEforms>
											</image_references>
										</el>
									</ROOT>
								</sDEF>
							</sheets>
						</T3DataStructure>'
				)
			)
		),
	),
);
