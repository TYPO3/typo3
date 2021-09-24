<?php

// Register icons not being part of TYPO3.Icons repository
return [
    'status-edit-read-only' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        'source' => 'EXT:backend/Resources/Public/Icons/status-edit-read-only.png',
    ],
    'warning-in-use' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        'source' => 'EXT:backend/Resources/Public/Icons/warning-in-use.png',
    ],
    'warning-lock' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        'source' => 'EXT:backend/Resources/Public/Icons/warning-lock.png',
    ],
];
