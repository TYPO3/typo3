<?php

declare(strict_types=1);

return [
    \TYPO3Tests\BlogExample\Domain\Model\Administrator::class => [
        'tableName' => 'fe_users',
        'recordType' => \TYPO3Tests\BlogExample\Domain\Model\Administrator::class,
    ],
    \TYPO3Tests\BlogExample\Domain\Model\Vehicle::class => [
        'tableName' => 'tx_blogexample_domain_model_vehicle',
        'subclasses' => [\TYPO3Tests\BlogExample\Domain\Model\Car::class],
    ],
    \TYPO3Tests\BlogExample\Domain\Model\Car::class => [
        'tableName' => 'tx_blogexample_domain_model_vehicle',
        'recordType' => 'car',
    ],
    \TYPO3Tests\BlogExample\Domain\Model\Machine::class => [
        'tableName' => 'tx_blogexample_domain_model_machine',
        'recordType' => 'machine',
        'subclasses' => [
            \TYPO3Tests\BlogExample\Domain\Model\Robot::class,
            \TYPO3Tests\BlogExample\Domain\Model\Drone::class,
        ],
    ],
    \TYPO3Tests\BlogExample\Domain\Model\Robot::class => [
        'tableName' => 'tx_blogexample_domain_model_machine',
        'recordType' => 'robot',
    ],
    \TYPO3Tests\BlogExample\Domain\Model\Drone::class => [
        'tableName' => 'tx_blogexample_domain_model_machine',
        'recordType' => 'drone',
    ],
    \TYPO3Tests\BlogExample\Domain\Model\Category::class => [
        'tableName' => 'sys_category',
    ],
    \TYPO3Tests\BlogExample\Domain\Model\TtContent::class => [
        'tableName' => 'tt_content',
        'properties' => [
            'uid' => [
                'fieldName' => 'uid',
            ],
            'pid' => [
                'fieldName' => 'pid',
            ],
            'header' => [
                'fieldName' => 'header',
            ],
        ],
    ],
    \TYPO3Tests\BlogExample\Domain\Model\TtContentWithCType::class => [
        'tableName' => 'tt_content',
        'properties' => [
            'uid' => [
                'fieldName' => 'uid',
            ],
            'pid' => [
                'fieldName' => 'pid',
            ],
            'header' => [
                'fieldName' => 'header',
            ],
        ],
    ],
    \TYPO3Tests\BlogExample\Domain\Model\FrontendUserGroup::class => [
        'tableName' => 'fe_groups',
    ],
];
