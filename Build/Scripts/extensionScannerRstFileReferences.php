#!/usr/bin/env php
<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Core integrity test script:
 *
 * Find all ReST files configured in EXT:install/Configuration/ExtensionScanner/Php
 * and verify they exist in EXT:core/Documentation/Changelog
 */
call_user_func(function () {
    if (php_sapi_name() !== 'cli') {
        die('Script must be called from command line.' . chr(10));
    }
    require __DIR__ . '/../../vendor/autoload.php';

    $finder = new Symfony\Component\Finder\Finder();
    $matcherConfigurationFiles = $finder->files()
        ->in(__DIR__ . '/../../typo3/sysext/install/Configuration/ExtensionScanner/Php');

    $invalidRestFiles = [];
    $validRestFiles = [];
    foreach ($matcherConfigurationFiles as $matcherConfigurationFile) {
        /** @var SplFileInfo $matcherConfigurationFile */
        $matcherConfigurations = require $matcherConfigurationFile->getPathname();
        foreach ($matcherConfigurations as $matcherConfiguration) {
            foreach ($matcherConfiguration['restFiles'] as $restFile) {
                if (in_array($restFile, $validRestFiles, true)) {
                    // Local cache as guard to not check same file over and over again
                    continue;
                }
                $restFinder = new \Symfony\Component\Finder\Finder();
                $restFileLocation = $restFinder->files()
                    ->in(__DIR__ . '/../../typo3/sysext/core/Documentation/Changelog')
                    ->name($restFile);
                if ($restFileLocation->count() !== 1) {
                    $invalidRestFiles[] = [
                        'configurationFile' => $matcherConfigurationFile->getPathname(),
                        'invalidFile' => $restFile,
                    ];
                } else {
                    $validRestFiles[] = $restFile;
                }
            }
        }
    }

    if (empty($invalidRestFiles)) {
        exit(0);
    }

    foreach ($invalidRestFiles as $invalid) {
        echo 'Referenced ReST file ' . $invalid['invalidFile']
            . ' in extension scanner configuration file ' . $invalid['configurationFile']
            . ' not found' . chr(10) . chr(10);
    }
    exit(1);
});
