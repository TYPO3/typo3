<?php

declare(strict_types=1);

return [
    \TYPO3\CMS\Extbase\Domain\Model\FileReference::class => [
        'tableName' => 'sys_file_reference',
    ],
    \TYPO3\CMS\Extbase\Domain\Model\File::class => [
        'tableName' => 'sys_file',
    ],
    \TYPO3\CMS\Extbase\Domain\Model\Category::class => [
        'tableName' => 'sys_category',
    ],
];
