<?php

declare(strict_types=1);

return [
    \TYPO3Tests\BlogExample\Domain\Model\Administrator::class => [
        'tableName' => 'fe_users',
        'recordType' => \TYPO3Tests\BlogExample\Domain\Model\Administrator::class,
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
