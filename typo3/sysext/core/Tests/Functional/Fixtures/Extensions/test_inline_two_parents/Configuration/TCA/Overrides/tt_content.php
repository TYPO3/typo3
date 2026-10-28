<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    [
        'tx_testinlinetwoparents_members' => [
            'label' => 'Members',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testinlinetwoparents_member',
                'foreign_field' => 'content_uid',
            ],
        ],
    ]
);
