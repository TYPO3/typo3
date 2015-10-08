<?php
defined('TYPO3_MODE') or die();

$TCA['tx_blogexample_domain_model_person'] = array(
    'ctrl' => $TCA['tx_blogexample_domain_model_person']['ctrl'],
    'interface' => array(
        'showRecordFieldList' => 'firstname, lastname, email, avatar'
    ),
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => array(
                'type' => 'check'
            )
        ),
        'firstname' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_person.firstname',
            'config' => array(
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim,required',
                'max' => 256
            )
        ),
        'lastname' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_person.lastname',
            'config' => array(
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim,required',
                'max' => 256
            )
        ),
        'email' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_person.email',
            'config' => array(
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim, required',
                'max' => 256
            )
        ),
        'tags' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_person.tags',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_blogexample_domain_model_tag',
                'MM' => 'tx_blogexample_domain_model_tag_mm',
                'foreign_table_field' => 'tablenames',
                'foreign_match_fields' => array(
                    'fieldname' => 'tags'
                ),
                'maxitems' => 9999,
                'appearance' => array(
                    'useCombination' => 1,
                    'useSortable' => 1,
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                )
            )
        ),
        'tags_special' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_person.tags_special',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_blogexample_domain_model_tag',
                'MM' => 'tx_blogexample_domain_model_tag_mm',
                'foreign_table_field' => 'tablenames',
                'foreign_match_fields' => array(
                    'fieldname' => 'tags_special'
                ),
                'maxitems' => 9999,
                'appearance' => array(
                    'useCombination' => 1,
                    'useSortable' => 1,
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                )
            )
        ),
    ),
    'types' => array(
        '1' => array('showitem' => 'firstname, lastname, email, avatar, tags, tags_special')
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    )
);
