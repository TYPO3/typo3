<?php
defined('TYPO3_MODE') or die();

return [
    'ctrl' => [
        'title'    => 'LLL:EXT:impexp_group_files/Resources/Private/Language/locallang_db.xlf:tx_impexpgroupfiles_item',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'sortby' => 'sorting',

        'versioningWS' => true,

        'origUid' => 't3_origuid',

        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'price,currency,symbol,',
        'iconfile' => 'EXT:impexp_group_files/Resources/Public/Icons/icon_tx_impexpgroupfiles_item.gif'
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, title, images, image_references, flexform',
    ],
    'types' => [
        '1' => ['showitem' => 'hidden, title, images, image_references, flexform, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,starttime, endtime'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
    'columns' => [

        't3ver_label' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
            ]
        ],

        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
            ],
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'size' => 13,
                'eval' => 'datetime',
                'default' => 0,
                'range' => [
                    'lower' => mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y'))
                ],
            ],
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'size' => 13,
                'eval' => 'datetime',
                'default' => 0,
                'range' => [
                    'lower' => mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y'))
                ],
            ],
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ]
        ],
        'title' => [
            'label' => 'LLL:EXT:impexp_group_files/Resources/Private/Language/locallang_db.xml:tx_impexpgroupfiles_item_title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'required'
            ]
        ],
        'images' => [
            'label' => 'LLL:EXT:impexp_group_files/Resources/Private/Language/locallang_db.xml:tx_impexpgroupfiles_item_images',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'disallowed' => 'php',
                'uploadfolder' => 'uploads/tx_impexpgroupfiles',
                'size' => 5,
                'maxitems' => 5,
            ],
        ],
        'image_references' => [
            'label' => 'LLL:EXT:impexp_group_files/Resources/Private/Language/locallang_db.xml:tx_impexpgroupfiles_item_image_references',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file_reference',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'disallowed' => 'php',
                'size' => 5,
                'maxitems' => 5,
            ],
        ],
        'flexform' => [
            'label' => 'LLL:EXT:impexp_group_files/Resources/Private/Language/locallang_db.xml:tx_impexpgroupfiles_item_flexform',
            'config' => [
                'type' => 'flex',
                'ds' => [
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
														<renderType>inputLink</renderType>
														<size>50</size>
														<max>256</max>
														<eval>trim</eval>
														<softref>typolink</softref>
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
													</config>
												</TCEforms>
											</image_references>
										</el>
									</ROOT>
								</sDEF>
							</sheets>
						</T3DataStructure>'
                ],
                'default' => ''
            ]
        ],
    ],
];
