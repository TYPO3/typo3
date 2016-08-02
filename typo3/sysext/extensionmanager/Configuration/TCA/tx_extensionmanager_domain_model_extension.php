<?php
return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_db.xlf:tx_extensionmanager_domain_model_extension',
        'label' => 'uid',
        'default_sortby' => '',
        'hideTable' => true,
        'rootLevel' => true,
        'adminOnly' => true,
        'typeicon_classes' => array(
            'default' => 'empty-icon'
        )
    ),
    'interface' => array(
        'showRecordFieldList' => 'extension_key,version,integer_version,title,description,state,category,last_updated,update_comment,author_name,author_email,md5hash,serialized_dependencies'
    ),
    'columns' => array(
        'extension_key' => array(
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.extensionkey',
            'config' => array(
                'type' => 'input',
                'size' => 30
            )
        ),
        'version' => array(
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.version',
            'config' => array(
                'type' => 'input',
                'size' => 30
            )
        ),
        'alldownloadcounter' => array(
            'config' => array(
                'type' => 'passthrough'
            )
        ),
        'integer_version' => array(
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.integerversion',
            'config' => array(
                'type' => 'input',
                'size' => 30
            )
        ),
        'title' => array(
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.title',
            'config' => array(
                'type' => 'input',
                'size' => 30
            )
        ),
        'description' => array(
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.description',
            'config' => array(
                'type' => 'text',
                'cols' => 30,
                'rows' => 5
            )
        ),
        'state' => array(
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.state',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'range' => array('lower' => 0, 'upper' => 1000),
                'eval' => 'int'
            )
        ),
        'category' => array(
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.category',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'range' => array('lower' => 0, 'upper' => 1000),
                'eval' => 'int'
            )
        ),
        'last_updated' => array(
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.lastupdated',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'datetime'
            )
        ),
        'update_comment' => array(
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.updatecomment',
            'config' => array(
                'type' => 'text',
                'cols' => 30,
                'rows' => 5
            )
        ),
        'author_name' => array(
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.authorname',
            'config' => array(
                'type' => 'input',
                'size' => 30
            )
        ),
        'author_email' => array(
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.authoremail',
            'config' => array(
                'type' => 'input',
                'size' => 30
            )
        ),
        'current_version' => array(
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.currentversion',
            'config' => array(
                'type' => 'check',
                'size' => 1
            )
        ),
        'review_state' => array(
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.reviewstate',
            'config' => array(
                'type' => 'check',
                'size' => 1
            )
        ),
        'md5hash' => array(
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.md5hash',
            'config' => array(
                'type' => 'input',
                'size' => 1,
            ),
        ),
        'serialized_dependencies' => array(
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.serializedDependencies',
            'config' => array(
                'type' => 'input',
                'size' => 30,
            ),
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'extensionkey, version, integer_version, title, description, state, category, last_updated, update_comment, author_name, author_email, review_state, md5hash, serialized_dependencies')
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    )
);
