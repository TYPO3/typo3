<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Linkvalidator\Task\ValidatorTask;

defined('TYPO3') or die();

if (isset($GLOBALS['TCA']['tx_scheduler_task'])) {
    ExtensionManagementUtility::addTCAcolumns(
        'tx_scheduler_task',
        [
            'tx_linkvalidator_configuration' => [
                'label' => 'linkvalidator.db:tasks.validate.conf',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'tx_linkvalidator_depth' => [
                'label' => 'linkvalidator.db:tasks.validate.depth',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        ['value' => '0', 'label' => 'core.db.general:depth_0'],
                        ['value' => '1', 'label' => 'core.db.general:depth_1'],
                        ['value' => '2', 'label' => 'core.db.general:depth_2'],
                        ['value' => '3', 'label' => 'core.db.general:depth_3'],
                        ['value' => '4', 'label' => 'core.db.general:depth_4'],
                        ['value' => '999', 'label' => 'core.db.general:depth_infi'],
                    ],
                ],
            ],
            'tx_linkvalidator_page' => [
                'label' => 'linkvalidator.db:tasks.validate.page',
                'config' => [
                    'type' => 'group',
                    'allowed' => 'pages',
                    'minitems' => 1,
                    'maxitems' => 1,
                    'size' => 1,
                ],
            ],
            'tx_linkvalidator_languages' => [
                'label' => 'linkvalidator.db:tasks.validate.languages',
                'config' => [
                    'type' => 'input',
                ],
            ],
            'tx_linkvalidator_email' => [
                'label' => 'linkvalidator.db:tasks.validate.email',
                'config' => [
                    'type' => 'text',
                ],
            ],
            'tx_linkvalidator_email_on_broken_link_only' => [
                'label' => 'linkvalidator.db:tasks.validate.emailOnBrokenLinkOnly',
                'config' => [
                    'type' => 'check',
                    'renderType' => 'checkboxToggle',
                    'default' => 1,
                ],
            ],
            'tx_linkvalidator_email_template_name' => [
                'label' => 'linkvalidator.db:tasks.validate.emailTemplateName',
                'config' => [
                    'type' => 'input',
                ],
            ],
        ]
    );

    ExtensionManagementUtility::addRecordType(
        [
            'label' => 'linkvalidator.db:tasks.validate.name',
            'description' => 'linkvalidator.db:tasks.validate.description',
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
        --div--;scheduler.messages:scheduler.form.palettes.timing,
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
