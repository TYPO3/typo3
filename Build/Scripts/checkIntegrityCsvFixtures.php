#!/usr/bin/env php
<?php
declare(strict_types = 1);
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

use TYPO3\CMS\Core\Utility\MathUtility;

require __DIR__ . '/../../vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
    die('Script must be called from command line.' . chr(10));
}

/**
 * Core integrity test script:
 *
 * Find all CSV files in fixtures and make sure they have the correct column
 * count across all lines in them and fix them if --fix argument is given.
 */
class checkIntegrityCsvFixtures
{
    /**
     * @var bool True to fix broken files
     */
    private $fix = false;

    /**
     * @var bool True to drop superfluous comma on all CSV fixture files
     */
    private $fixAll = false;

    public function setFix(bool $fix)
    {
        $this->fix = $fix;
    }

    public function setFixAll(bool $fixAll)
    {
        $this->fixAll = $fixAll;
    }

    /**
     * Executes the CGL check.
     * The return value is used directly in the ext() call outside this class.
     *
     * @return int
     */
    public function execute(): int
    {
        $filesToProcess = $this->findCsvFixtures();
        $outputLines = [];
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();

        $exitStatus = 0;
        /** @var \SplFileInfo $csvFixture */
        foreach ($filesToProcess as $csvFixture) {
            $fullFilePath = $csvFixture->getRealPath();
            if ($this->fixAll) {
                $changed = $this->fixCsvFile($fullFilePath);
                if ($changed) {
                    $outputLines[] = 'Changed file "' . $this->formatOutputString($this->getRelativePath($fullFilePath)) . '"';
                }
                continue;
            }
            $singleFileScanResult = $this->validateCsvFile($fullFilePath);
            if ($singleFileScanResult !== '') {
                if ($this->fix) {
                    $this->fixCsvFile($fullFilePath);
                    $outputLines[] = 'Fixed file "' . $this->formatOutputString($this->getRelativePath($fullFilePath)) . '"';
                } else {
                    $exitStatus = 1;
                    $outputLines[] = 'File "' . $this->formatOutputString($this->getRelativePath($fullFilePath)) . '"'
                        . ' is not in valid CSV format: ' . $singleFileScanResult;
                }
            }
        }
        if (!empty($outputLines)) {
            foreach ($outputLines as $line) {
                $output->writeln($line);
            }
        }
        return $exitStatus;
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

    /**
     * Fix a single CSV file.
     *
     * @param string $csvFixture
     * @return bool True if the file has been changed
     */
    private function fixCsvFile(string $csvFixture): bool
    {
        $changeNeeded = false;
        // Load file content into array split by line
        $lines = file($csvFixture);
        $neededColumns = 0;
        $csvLines = [];
        foreach ($lines as $line) {
            // Find out how many columns are needed in this file
            $csvLine = str_getcsv($line);
            $csvLines[] = $csvLine;
            foreach ($csvLine as $columnNumber => $columnContent) {
                if (!empty($columnContent) && $columnNumber + 1 > $neededColumns) {
                    $neededColumns = $columnNumber + 1;
                }
            }
        }
        foreach ($csvLines as $csvLine) {
            // Set $changeNeeded to true if this file needs an update and line is not a comment
            if (count($csvLine) !== $neededColumns && substr($csvLine[0], 0, 2) !== '# ') {
                $changeNeeded = true;
                break;
            }
        }
        if ($changeNeeded) {
            // Update file
            $fileHandle = fopen($csvFixture, 'w');
            if (!$fileHandle) {
                throw new \Exception('Opening file "' . $csvFixture . '" for writing failed.');
            }
            foreach ($csvLines as $csvLine) {
                // Extend / reduce to needed size
                $csvLine = array_slice(array_pad($csvLine, $neededColumns, ''), 0, $neededColumns);
                $isComment = false;
                $line = array_reduce($csvLine, function ($carry, $column) use (&$isComment) {
                    if ($carry === null && substr($column, 0, 2) === '# ') {
                        $isComment = true;
                        $carry .= $column;
                    } elseif ($isComment) {
                        // comment lines are not filled up with comma
                        return $carry;
                    } elseif (empty($column) && $column !== '0') {
                        // No leading comma if first column
                        $carry .= $carry === null ? '' : ',';
                    } elseif (MathUtility::canBeInterpretedAsInteger($column)) {
                        // No leading comma if first column and integer payload
                        $carry .= ($carry === null ? '' : ',') . $column;
                    } else {
                        // No leading comma if first column and string payload and quote " to ""
                        $column = str_replace('"', '""', $column);
                        $carry .= ($carry === null ? '' : ',') . '"' . $column . '"';
                    }
                    return $carry;
                });
                fwrite($fileHandle, $line . chr(10));
            }
            fclose($fileHandle);
        }
        return $changeNeeded;
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
$args = getopt('', ['fix', 'fixAll']);
if (array_key_exists('fix', $args)) {
    $cglFixer->setFix(true);
}
if (array_key_exists('fixAll', $args)) {
    $cglFixer->setFixAll(true);
}
exit($cglFixer->execute());
