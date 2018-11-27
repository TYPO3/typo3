<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tx_a_domain_model_a',
    [
        'b' => [
            'label' => 'b',
            'config' => [
                'type' => 'input',
                'size' => 25,
                'max' => 255,
            ]
        ],
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tx_a_domain_model_a', 'b');
