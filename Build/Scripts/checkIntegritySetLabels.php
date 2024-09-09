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

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

require __DIR__ . '/../../vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
    die('Script must be called from command line.' . chr(10));
}

final readonly class CheckIntegritySetLabels
{
    public function execute(): int
    {
        $filesToProcess = $this->findSetLabels();
        $output = new ConsoleOutput();

        $resultAcrossAllFiles = 0;
        $testResults = [];
        /** @var \SplFileInfo $labelFile */
        foreach ($filesToProcess as $labelFile) {
            $fullFilePath = $labelFile->getRealPath();
            $result = $this->checkValidLabels($fullFilePath);
            if ($result !== null) {
                $testResults[] = $result;
            }
        }
        if ($testResults === []) {
            return 0;
        }

        $table = new Table($output);
        $table->setHeaders([
            'EXT',
            'Set',
            'Invalid set labels',
            'Missing set labels',
        ]);
        foreach ($testResults as $result) {
            $table->addRow([
                $result['ext'],
                $result['set'],
                implode("\n", $result['invalid']),
                implode("\n", $result['missing']),
            ]);
        }
        $table->render();
        return 1;
    }

    private function findSetLabels(): Finder
    {
        $finder = new Finder();
        $labelFiles = $finder
            ->files()
            ->in(__DIR__ . '/../../typo3/sysext/*/Configuration/Sets/*')
            ->name('labels.xlf');
        return $labelFiles;
    }

    private function checkValidLabels(string $labelFile): ?array
    {
        $extensionKey = $this->extractExtensionKey($labelFile);
        $doc = new DOMDocument();
        if (!$doc->load($labelFile)) {
            throw new \RuntimeException('Failed to load xlf file: ' . $labelFile, 1725902515);
        }

        $requiredLabels = [
            'label',
        ];
        $optionalLabels = [
            'description',
        ];

        $settingsDefinitions = Yaml::parseFile(dirname($labelFile) . '/settings.definitions.yaml');
        foreach ($settingsDefinitions['settings'] as $key => $settingsDefinition) {
            $requiredLabels[] = 'settings.' . $key;
            $optionalLabels[] = 'settings.description.' . $key;
        }

        $setName = Yaml::parseFile(dirname($labelFile) . '/config.yaml')['name'];

        $availableLabels = [];
        foreach ($doc->getElementsByTagName('trans-unit') as $tu) {
            $availableLabels[] = $tu->getAttribute('id');
        }

        $allowedLabels = [
            ...$requiredLabels,
            ...$optionalLabels,
        ];
        $missing = array_diff($requiredLabels, $availableLabels);
        $invalid = array_diff($availableLabels, $allowedLabels);

        if ($missing === [] && $invalid === []) {
            return null;
        }

        return [
            'ext' => $extensionKey,
            'set' => $setName,
            'invalid' => $invalid,
            'missing' => $missing,
        ];
    }

    private function extractExtensionKey(string $filename): string
    {
        $pattern = '/typo3\/sysext\/(?<extName>[a-z].+?)\//';
        preg_match_all($pattern, $filename, $matches, PREG_SET_ORDER, 0);
        return $matches[0]['extName'];
    }
}

exit((new CheckIntegritySetLabels())->execute());
