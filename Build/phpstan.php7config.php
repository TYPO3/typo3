<?php

declare(strict_types=1);

$config = [];

if (PHP_MAJOR_VERSION === 7) {
    $config['parameters']['ignoreErrors'] = [
        '#Class GdImage not found.#',
        [
            'message' => '#^Parameter \\#[1-4]{1} \\$[a-z]* of function [a-z_]* expects resource, resource\\|XmlParser given\\.$#',
            'path' => '../typo3/sysext/extensionmanager/Classes/Utility/Parser/ExtensionXmlPushParser.php',
            'count' => 9,
        ],
        [
            'message' => '#^Parameter \\#1 \\$sem_identifier of function sem_release expects resource, resource\\|SysvSemaphore given\\.$#',
            'path' => '../typo3/sysext/core/Classes/Locking/SemaphoreLockStrategy.php',
            'count' => 1,
        ],
        [
            'message' => '#^Parameter \\#1 \\$sem_identifier of function sem_remove expects resource, resource\\|SysvSemaphore given\\.$#',
            'path' => '../typo3/sysext/core/Classes/Locking/SemaphoreLockStrategy.php',
            'count' => 1,
        ]
    ];
}

return $config;
