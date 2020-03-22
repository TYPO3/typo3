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
 * Find all composer.json files in all system extensions and compare
 * their dependencies against the defined dependencies of our root
 * composer.json
 */
class checkIntegrityComposer
{
    /**
     * @var array
     */
    private $rootComposerJson = [];

    private $testResults = [];

    /**
     * Executes the composer integrity check.
     * The return value is used directly in the ext() call outside this class.
     *
     * @return int
     */
    public function execute(): int
    {
        $rootComposerJson = __DIR__ . '/../../composer.json';
        $this->rootComposerJson = json_decode(file_get_contents($rootComposerJson), true);
        $filesToProcess = $this->findExtensionComposerJson();
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();

        $resultAcrossAllFiles = 0;
        /** @var \SplFileInfo $composerJsonFile */
        foreach ($filesToProcess as $composerJsonFile) {
            $fullFilePath = $composerJsonFile->getRealPath();
            $this->validateComposerJson($fullFilePath);
        }
        if (!empty($this->testResults)) {
            $table = new \Symfony\Component\Console\Helper\Table($output);
            $table->setHeaders([
                'EXT',
                'type',
                'Dependency',
                'should be',
                'actually is'
            ]);
            foreach ($this->testResults as $extKey => $results) {
                foreach ($results as $result) {
                    $table->addRow([
                        $extKey,
                        $result['type'],
                        $result['dependency'],
                        $result['shouldBe'],
                        $result['actuallyIs']
                    ]);
                }
            }
            $table->render();
            $resultAcrossAllFiles = 1;
        }
        return $resultAcrossAllFiles;
    }

    /**
     * Finds all composer.json files in TYPO3s system extensions
     *
     * @return \Symfony\Component\Finder\Finder
     */
    private function findExtensionComposerJson(): \Symfony\Component\Finder\Finder
    {
        $finder = new Symfony\Component\Finder\Finder();
        $composerFiles = $finder
            ->files()
            ->in(__DIR__ . '/../../typo3/sysext/*')
            ->name('composer.json');
        return $composerFiles;
    }

    /**
     * Checks if the dependencies defined in $composerJsonFile are the same as
     * in TYPO3s root composer.json file.
     *
     * @param string $composerJsonFile
     */
    private function validateComposerJson(string $composerJsonFile)
    {
        $extensionKey = $this->extractExtensionKey($composerJsonFile);
        $extensionComposerJson = json_decode(file_get_contents($composerJsonFile), true);
        // Check require section
        foreach ($this->rootComposerJson['require'] as $requireKey => $requireItem) {
            if (isset($extensionComposerJson['require'][$requireKey]) && $extensionComposerJson['require'][$requireKey] !== $requireItem) {
                // log inconsistency
                $this->testResults[$extensionKey][] = [
                    'type' => 'require',
                    'dependency' => $requireKey,
                    'shouldBe' => $requireItem,
                    'actuallyIs' => $extensionComposerJson['require'][$requireKey]
                ];
            }
        }
        // Check require-dev section
        foreach ($this->rootComposerJson['require-dev'] as $requireDevKey => $requireDevItem) {
            if (isset($extensionComposerJson['require-dev'][$requireDevKey]) && $extensionComposerJson['require-dev'][$requireDevKey] !== $requireDevItem) {
                // log inconsistency
                $this->testResults[$extensionKey][] = [
                    'type' => 'require-dev',
                    'dependency' => $requireDevKey,
                    'shouldBe' => $requireDevItem,
                    'actuallyIs' => $extensionComposerJson['require-dev'][$requireDevKey]
                ];
            }
        }
    }

    /**
     * Makes the output on CLI a bit more readable
     *
     * @param string $filename
     * @return string
     */
    private function extractExtensionKey(string $filename): string
    {
        $pattern = '/typo3\/sysext\/(?<extName>[a-z].+?)\//';
        preg_match_all($pattern, $filename, $matches, PREG_SET_ORDER, 0);
        return $matches[0]['extName'];
    }
}

$composerIntegrityChecker = new checkIntegrityComposer();
exit($composerIntegrityChecker->execute());
