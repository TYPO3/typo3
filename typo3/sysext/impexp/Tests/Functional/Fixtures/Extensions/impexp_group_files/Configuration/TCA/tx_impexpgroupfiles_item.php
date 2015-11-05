<?php
defined('TYPO3_MODE') or die();

return array(
    'ctrl' => array(
        'title'    => 'LLL:EXT:impexp_group_files/Resources/Private/Language/locallang_db.xlf:tx_impexpgroupfiles_item',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'sortby' => 'sorting',

        'versioningWS' => true,
        'versioning_followPages' => true,

        'origUid' => 't3_origuid',

        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ),
        'searchFields' => 'price,currency,symbol,',
        'iconfile' => 'EXT:impexp_group_files/Resources/Public/Icons/icon_tx_impexpgroupfiles_item.gif'
    ),
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, title, images, image_references, flexform',
    ),
    'types' => array(
        '1' => array('showitem' => 'hidden, title, images, image_references, flexform, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,starttime, endtime'),
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
                'eval' => 'datetime',
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
                'eval' => 'datetime',
                'default' => 0,
                'range' => array(
                    'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y'))
                ),
            ),
        ),
        'l18n_diffsource' => array(
            'config' => array(
                'type' => 'passthrough',
                'default' => ''
            )
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
															<link type="array">
																<type>popup</type>
																<title>Link</title>
																<icon>EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif</icon>
																<module type="array">
																	<name>wizard_link</name>
																</module>
																<JSopenParams>width=800,height=600,status=0,menubar=0,scrollbars=1</JSopenParams>
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
                ),
                'default' => ''
            )
        ),
    ),
);
