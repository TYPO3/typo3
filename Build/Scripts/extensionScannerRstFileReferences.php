#!/usr/bin/env php
<?php
declare(strict_types=1);
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

require __DIR__ . '/../../vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
    die('Script must be called from command line.' . chr(10));
}

/**
 * Core integrity test script:
 *
 * Find all ReST files configured in EXT:install/Configuration/ExtensionScanner/Php
 * and verify they exist in EXT:core/Documentation/Changelog
 */
class ExtensionScannerRstFileReferencesChecker
{
    /**
     * @var array<string, string>
     */
    private $invalidRestFiles = [];

    /**
     * @var array<string, string>
     */
    private $validRestFiles = [];

    /**
     * @var array<string, int>
     */
    private $existingRestFiles = [];

    public function check(): int
    {
        $this->existingRestFiles = $this->fetchExistingRstFiles();

        $finder = new Symfony\Component\Finder\Finder();
        $matcherConfigurationFiles = $finder->files()
            ->in(__DIR__ . '/../../typo3/sysext/install/Configuration/ExtensionScanner/Php');

        foreach ($matcherConfigurationFiles as $matcherConfigurationFile) {
            /** @var SplFileInfo $matcherConfigurationFile */
            $matcherConfigurations = require $matcherConfigurationFile->getPathname();
            foreach ($matcherConfigurations as $matcherConfiguration) {
                if (!isset($matcherConfiguration['restFiles'])) {
                    // `ConstructorArgumentsMatcher` is using an additional level which has to be checked
                    foreach ($matcherConfiguration as $nestedMatcherConfiguration) {
                        $this->checkRstFiles($nestedMatcherConfiguration, $matcherConfigurationFile);
                    }
                } else {
                    $this->checkRstFiles($matcherConfiguration, $matcherConfigurationFile);
                }
            }
        }

        if (empty($this->invalidRestFiles)) {
            return 0; // we are done, nothing found. Script will exit with 0
        }
        echo "ReST files references in extension scanner configuration that could not be found:\n";
        foreach ($this->invalidRestFiles as $invalid) {
            printf(
                " - '%s' in file '%s'\n",
                $invalid['invalidFile'],
                $invalid['configurationFile']
            );
        }
        echo "\n";
        return 1; // we got findings, script will exit with 1
    }

    private function fetchExistingRstFiles(): array
    {
        $finder = new Symfony\Component\Finder\Finder();
        $finder->files()
            ->name('*.rst')
            ->notName(['Changelog-*.rst', 'Index.rst', 'Master.rst'])
            ->in(__DIR__ . '/../../typo3/sysext/core/Documentation/Changelog');
        $fileNames = array_map(
            static function (SplFileInfo $file) {
                return $file->getFilename();
            },
            iterator_to_array($finder)
        );
        // remove array keys containing the full file path again
        $fileNames = array_values($fileNames);
        // e.g. `['SomeFileA.rst' => 1, 'SomeOtherFile.rst' => 4]` counting occurrences
        return array_count_values($fileNames);
    }

    private function checkRstFiles($matcherConfiguration, $matcherConfigurationFile): void
    {
        if (!is_array($matcherConfiguration['restFiles'] ?? null)) {
            throw new \InvalidArgumentException(sprintf(
                'Configuration has no `restFiles` section defined in %s',
                $matcherConfigurationFile
            ));
        }
        foreach ($matcherConfiguration['restFiles'] as $restFile) {
            if (in_array($restFile, $this->validRestFiles, true)) {
                // Local cache as guard to not check same file over and over again
                continue;
            }
            if (($this->existingRestFiles[$restFile] ?? 0) === 1) {
                $this->validRestFiles[] = $restFile;
            } else {
                $this->invalidRestFiles[] = [
                    'configurationFile' => $matcherConfigurationFile->getPathname(),
                    'invalidFile' => $restFile,
                ];
            }
        }
    }
}

$checker = new ExtensionScannerRstFileReferencesChecker();
exit($checker->check());
