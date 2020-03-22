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
 * Find all CSV files in fixtures and make sure they have the correct column
 * count across all lines in them
 */
class checkIntegrityCsvFixtures
{
    /**
     * Executes the CGL check.
     * The return value is used directly in the ext() call outside this class.
     *
     * @return int
     */
    public function execute(): int
    {
        $filesToProcess = $this->findCsvFixtures();
        $scanResult = [];
        $failureCount = 0;
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();

        $resultAcrossAllFiles = 0;
        /** @var \SplFileInfo $csvFixture */
        foreach ($filesToProcess as $csvFixture) {
            $fullFilePath = $csvFixture->getRealPath();
            $singleFileScanResult = $this->validateCsvFile($fullFilePath);
            if ($singleFileScanResult !== '') {
                $resultAcrossAllFiles = 1;
                $failureCount++;
                $scanResult[$this->getRelativePath($fullFilePath)] = $singleFileScanResult;
            }
        }
        if (!empty($scanResult)) {
            foreach ($scanResult as $key => $reason) {
                $output->writeln('The file "' . $this->formatOutputString($key) . '" is not in valid CSV format: ' . $reason);
            }
        }
        return $resultAcrossAllFiles;
    }

    /**
     * Finds all CSV fixtures in TYPO3s core
     *
     * @return \Symfony\Component\Finder\Finder
     */
    private function findCsvFixtures(): \Symfony\Component\Finder\Finder
    {
        $finder = new Symfony\Component\Finder\Finder();
        $csvFixtures = $finder
            ->files()
            ->in(__DIR__ . '/../../typo3/sysext/*/Tests/Functional/**')
            ->name('*.csv');
        return $csvFixtures;
    }

    /**
     * Checks if a CSV uses the same amount of columns across all
     * lines in that file
     *
     * @param string $csvFixture
     * @return string
     */
    private function validateCsvFile(string $csvFixture): string
    {
        // Load file content into array split by line
        $lines = file($csvFixture);
        $columnCount = 0;
        foreach ($lines as $index => $line) {
            // count columns in file
            $columns = str_getcsv($line);
            if ($columnCount === 0) {
                $columnCount = count($columns);
            } else {
                if (count($columns) !== $columnCount) {
                    // Skip CSV lines with starting with comments
                    if (count($columns) === 1 && strpos($columns[0], '#') === 0) {
                        continue;
                    }
                    return 'Line ' . ($index + 1) . '; Expected column count: ' . $columnCount . '; Actual: ' . count($columns);
                }
            }
        }
        return '';
    }

    private function getRelativePath(string $fullPath): string
    {
        $pathSegment = str_replace('Build/Scripts', '', __DIR__);
        return str_replace($pathSegment, '', $fullPath);
    }

    /**
     * Makes the output on CLI a bit more readable
     *
     * @param string $filename
     * @return string
     */
    private function formatOutputString(string $filename): string
    {
        $pattern = '#typo3[\\\\/]sysext[\\\\/](?<extName>[a-z].+?)[\\\\/]Tests[\\\\/]Functional[\\\\/](?<file>.*)#';
        preg_match_all($pattern, $filename, $matches, PREG_SET_ORDER, 0);
        if (isset($matches[0])) {
            return 'EXT:' . $matches[0]['extName'] . ' > ' . $matches[0]['file'];
        }
        return $filename;
    }
}

$cglFixer = new checkIntegrityCsvFixtures();
exit($cglFixer->execute());
