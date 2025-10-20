<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Linkvalidator\Task\ValidatorTask;

defined('TYPO3') or die();

if (isset($GLOBALS['TCA']['tx_scheduler_task'])) {
    ExtensionManagementUtility::addTCAcolumns(
        'tx_scheduler_task',
        [
            'tx_linkvalidator_configuration' => [
                'label' => 'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.conf',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'tx_linkvalidator_depth' => [
                'label' => 'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.depth',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        ['value' => '0', 'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'],
                        ['value' => '1', 'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1'],
                        ['value' => '2', 'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2'],
                        ['value' => '3', 'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3'],
                        ['value' => '4', 'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4'],
                        ['value' => '999', 'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'],
                    ],
                ],
            ],
            'tx_linkvalidator_page' => [
                'label' => 'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.page',
                'config' => [
                    'type' => 'group',
                    'allowed' => 'pages',
                    'minitems' => 1,
                    'maxitems' => 1,
                    'size' => 1,
                ],
            ],
            'tx_linkvalidator_languages' => [
                'label' => 'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.languages',
                'config' => [
                    'type' => 'input',
                ],
            ],
            'tx_linkvalidator_email' => [
                'label' => 'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.email',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'tx_linkvalidator_email_on_broken_link_only' => [
                'label' => 'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.emailOnBrokenLinkOnly',
                'config' => [
                    'type' => 'check',
                    'renderType' => 'checkboxToggle',
                    'default' => 1,
                ],
            ],
            'tx_linkvalidator_email_template_name' => [
                'label' => 'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.emailTemplateName',
                'config' => [
                    'type' => 'input',
                ],
            ],
        ]
    );

    ExtensionManagementUtility::addRecordType(
        [
            'label' => 'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.name',
            'description' => 'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.description',
            'value' => ValidatorTask::class,
            'icon' => 'mimetypes-x-tx_scheduler_task_group',
            'group' => 'linkvalidator',
        ],
        '
        --div--;core.form.tabs:general,
            tasktype,
            task_group,
            description,
            tx_linkvalidator_page,
            tx_linkvalidator_depth,
            tx_linkvalidator_languages,
            tx_linkvalidator_configuration,
            tx_linkvalidator_email,
            tx_linkvalidator_email_on_broken_link_only,
            tx_linkvalidator_email_template_name,
        --div--;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:scheduler.form.palettes.timing,
            execution_details,
            nextexecution,
            --palette--;;lastexecution,
        --div--;core.form.tabs:access,
            disable,
        --div--;core.form.tabs:extended,',
        [],
        '',
        'tx_scheduler_task'
    );
}
