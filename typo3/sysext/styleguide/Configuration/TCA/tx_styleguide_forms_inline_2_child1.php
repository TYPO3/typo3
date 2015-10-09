<?php
return array(
    'ctrl' => array(
        'title' => 'Form engine tests - inline_2 child 1',
        'label' => 'input_1',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms_staticdata.svg',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'default_sortby' => 'ORDER BY crdate',

        'dividers2tabs' => 1,
    ),
    'columns' => array(
        'sys_language_uid' => array(
            'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => array(
                    array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
                    array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
                )
            )
        ),
        'l18n_parent' => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('', 0),
                ),
                'foreign_table' => 'tx_styleguide_forms_inline_2_child1',
                'foreign_table_where' => 'AND tx_styleguide_forms_inline_2_child1.pid=###CURRENT_PID### AND tx_styleguide_forms_inline_2_child1.sys_language_uid IN (-1,0)',
            )
        ),
        'l18n_diffsource' => array(
            'config' => array(
                'type' => 'passthrough'
            )
        ),
        'hidden' => array(
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'parentid' => array(
            'config' => array(
                'type' => 'passthrough',
            )
        ),
        'parenttable' => array(
            'config' => array(
                'type' => 'passthrough',
            )
        ),
        'input_1' => array(
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'input_1',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            ),
        ),
        'inline_1' => array(
            'label' => '1 1:n foreign field to table without sheets',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_forms_inline_2_child2',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'maxitems' => 10,
                'appearance' => array(
                    'showSynchronizationLink' => true,
                    'showAllLocalizationLink' => true,
                    'showPossibleLocalizationRecords' => true,
                    'showRemovedLocalizationRecords' => true,
                ),
                'behaviour' => array(
                    'localizationMode' => 'select',
                    'localizeChildrenAtParentLocalization' => true,
                ),
            ),
        ),
        'inline_2' => array(
            'label' => 'IRRE: 2 media FAL field',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('inline_2', array(
                'appearance' => array(
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference'
                ),
                // custom configuration for displaying fields in the overlay/reference table
                // to use the imageoverlayPalette instead of the basicoverlayPalette
                'foreign_types' => array(
                    '0' => array(
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
							--palette--;;filePalette'
                    ),
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => array(
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
							--palette--;;filePalette'
                    ),
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => array(
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
							--palette--;;filePalette'
                    ),
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => array(
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.audioOverlayPalette;audioOverlayPalette,
							--palette--;;filePalette'
                    ),
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => array(
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.videoOverlayPalette;videoOverlayPalette,
							--palette--;;filePalette'
                    ),
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => array(
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
							--palette--;;filePalette'
                    )
                )
            ), $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'])
        ),
    ),
    'interface' => array(
        'showRecordFieldList' => '
			sys_language_uid, l18n_parent, l18n_diffsource, hidden, parentid, parenttable,
			input_1, inline_1, inline_2,
		',
    ),
    'types' => array(
        '0' => array(
            'showitem' => '
				--div--;General, input_1, inline_1, inline_2,
				--div--;Visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden, parentid, parenttable
			',
        ),
    ),
);
