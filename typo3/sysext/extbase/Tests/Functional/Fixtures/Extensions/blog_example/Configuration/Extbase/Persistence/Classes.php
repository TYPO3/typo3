<?php

declare(strict_types=1);

return [
    \ExtbaseTeam\BlogExample\Domain\Model\Administrator::class => [
        'tableName' => 'fe_users',
        'recordType' => \ExtbaseTeam\BlogExample\Domain\Model\Administrator::class
    ],
    \ExtbaseTeam\BlogExample\Domain\Model\Category::class => [
        'tableName' => 'sys_category',
    ],
    \ExtbaseTeam\BlogExample\Domain\Model\TtContent::class => [
        'tableName' => 'tt_content',
        'properties' => [
            'uid' => [
                'fieldName' => 'uid'
            ],
            'pid' => [
                'fieldName' => 'pid'
            ],
            'header' => [
                'fieldName' => 'header'
            ],
        ],
    ],
    \ExtbaseTeam\BlogExample\Domain\Model\FrontendUserGroup::class => [
        'tableName' => 'fe_groups',
    ],
];
