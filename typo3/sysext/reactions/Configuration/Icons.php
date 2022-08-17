<?php

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'mimetypes-x-sys_reaction' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:reactions/Resources/Public/Icons/mimetypes-x-sys_reaction.svg',
    ],
    'module-reactions' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:reactions/Resources/Public/Icons/Extension.svg',
    ],
];
