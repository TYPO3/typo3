<?php
return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:irre_tutorial/locallang_db.xml:tx_irretutorial_mnsym_hotel_rel',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'iconfile' => 'EXT:irre_tutorial/Resources/Public/Icons/icon_tx_irretutorial_hotel_rel.gif',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        // @see http://forge.typo3.org/issues/29278 which solves it implicitly in the Core
        // 'shadowColumnsForNewPlaceholders' => 'hotelid',
    ),
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,title,hotelid,offerid,hotelsort,offersort'
    ),
    'columns' => array(
        'sys_language_uid' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => array(
                    array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
                    array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
                ),
                'default' => 0
            )
        ),
        'l18n_parent' => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array('', 0),
                ),
                'foreign_table' => 'tx_irretutorial_mnsym_hotel_rel',
                'foreign_table_where' => 'AND tx_irretutorial_mnsym_hotel_rel.pid=###CURRENT_PID### AND tx_irretutorial_mnsym_hotel_rel.sys_language_uid IN (-1,0)',
                'default' => 0
            )
        ),
        'l18n_diffsource' => array(
            'config' => array(
                'type' => 'passthrough',
                'default' => ''
            )
        ),
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'hotelid' => array(
            'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tx_irretutorial_hotel_rel.hotelid',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_irretutorial_mnsym_hotel',
                'maxitems' => 1,
                'localizeReferences' => 1,
                'default' => 0,
            )
        ),
        'branchid' => array(
            'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tx_irretutorial_hotel_rel.branchid',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_irretutorial_mnsym_hotel',
                'maxitems' => 1,
                'localizeReferences' => 1,
                'default' => 0,
            )
        ),
        'hotelsort' => array(
            'config' => array(
                'type' => 'passthrough',
            )
        ),
        'branchsort' => array(
            'config' => array(
                'type' => 'passthrough',
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' =>
            '--div--;LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tabs.general, title, hotelid, branchid,' .
            '--div--;LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tabs.visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden;;1, hotelsort, branchsort'
        )
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    )
);
