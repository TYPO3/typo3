<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'OliverHader.' . $_EXTKEY, 'Irre',
    [
        'Queue' => 'index',
        'Content' => 'list, show, new, create, edit, update, delete'
    ],
    [
        'Content' => 'create, update, delete'
    ]
);
