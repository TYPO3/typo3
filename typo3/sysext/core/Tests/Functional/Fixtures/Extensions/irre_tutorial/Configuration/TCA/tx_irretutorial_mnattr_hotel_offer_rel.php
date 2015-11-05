<?php
return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tx_irretutorial_mnattr_hotel_offer_rel',
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
        'iconfile' => 'EXT:irre_tutorial/Resources/Public/Icons/icon_tx_irretutorial_hotel_offer_rel.gif',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        // @see http://forge.typo3.org/issues/29278 which solves it implicitly in the Core
        // 'shadowColumnsForNewPlaceholders' => 'hotelid,offerid',
    ),
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,title,hotelid,offerid,hotelsort,offersort,quality,allincl'
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
                'foreign_table' => 'tx_irretutorial_mnattr_hotel_offer_rel',
                'foreign_table_where' => 'AND tx_irretutorial_mnattr_hotel_offer_rel.pid=###CURRENT_PID### AND tx_irretutorial_mnattr_hotel_offer_rel.sys_language_uid IN (-1,0)',
                'default' => 0,
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
            'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tx_irretutorial_hotel_offer_rel.hotelid',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_irretutorial_mnattr_hotel',
                'maxitems' => 1,
                'localizeReferences' => 1,
                'default' => 0,
            )
        ),
        'offerid' => array(
            'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tx_irretutorial_hotel_offer_rel.offerid',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_irretutorial_mnattr_offer',
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
        'offersort' => array(
            'config' => array(
                'type' => 'passthrough',
            )
        ),
        'quality' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tx_irretutorial_hotel_offer_rel.quality',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array('LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tx_irretutorial_hotel_offer_rel.quality.I.0', '1'),
                    array('LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tx_irretutorial_hotel_offer_rel.quality.I.1', '2'),
                    array('LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tx_irretutorial_hotel_offer_rel.quality.I.2', '3'),
                    array('LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tx_irretutorial_hotel_offer_rel.quality.I.3', '4'),
                    array('LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tx_irretutorial_hotel_offer_rel.quality.I.4', '5'),
                ),
            )
        ),
        'allincl' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tx_irretutorial_hotel_offer_rel.allincl',
            'config' => array(
                'type' => 'check',
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' =>
            '--div--;LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tabs.general, title, hotelid, offerid, hotelsort, offersort, quality, allincl,' .
            '--div--;LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tabs.visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden;;1'
        )
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    )
);
