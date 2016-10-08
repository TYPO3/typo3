<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\UpgradeAnalysis;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Provide information about documentation files
 */
class DocumentationFile
{

    /**
     * @var array Unified array of used tags
     */
    protected $tagsTotal = [];

    /**
     * Traverse given directory, select files
     *
     * @param string $path
     * @return array file details of affected documentation files
     */
    public function findDocumentationFiles(string $path): array
    {
        $documentationFiles = [];
        $versionDirectories = scandir($path);

        $fileInfo = pathinfo($path);
        $absolutePath = $fileInfo['dirname'] . DIRECTORY_SEPARATOR . $fileInfo['basename'];
        foreach ($versionDirectories as $version) {
            $directory = $absolutePath . DIRECTORY_SEPARATOR . $version;
            $documentationFiles += $this->getDocumentationFilesForVersion($directory, $version);
        }
        $this->tagsTotal = $this->collectTagTotal($documentationFiles);

        return $documentationFiles;
    }

    /**
     * True if file should be considered
     *
     * @param array $fileInfo
     * @return bool
     */
    protected function isRelevantFile(array $fileInfo): bool
    {
        return $fileInfo['extension'] === 'rst' && $fileInfo['filename'] !== 'Index';
    }

    /**
     * Add tags from file
     *
     * @param array $file file content, each line is an array item
     * @return array
     */
    protected function extractTags(array $file): array
    {
        $tags = $this->extractTagsFromFile($file);
        // Headline starting with the category like Breaking, Important or Feature
        $tags[] = $this->extractCategoryFromHeadline($file);

        return $tags;
    }

    /**
     * Files must contain an index entry, detailing any number of manual tags
     * each of these tags is extracted and added to the general tag structure for the file
     *
     * @param array $file file content, each line is an array item
     * @return array extracted tags
     */
    protected function extractTagsFromFile(array $file): array
    {
        foreach ($file as $line) {
            if (StringUtility::beginsWith($line, '.. index::')) {
                $tagString = substr($line, strlen('.. index:: '));
                return GeneralUtility::trimExplode(',', $tagString, true);
            }
        }

        return [];
    }

    /**
     * Files contain a headline (provided as input parameter,
     * it starts with the category string.
     * This will used as a tag
     *
     * @param array $lines
     * @return string
     */
    protected function extractCategoryFromHeadline(array $lines): string
    {
        $headline = $this->extractHeadline($lines);
        if (strpos($headline, ':') !== false) {
            return 'cat:' . substr($headline, 0, strpos($headline, ':'));
        }

        return '';
    }

    /**
     * First line is headline mark, skip it
     * second line is headline
     *
     * @param array $lines
     * @return string
     */
    protected function extractHeadline(array $lines): string
    {
        $index = 0;
        while (StringUtility::beginsWith($lines[$index], '..') || StringUtility::beginsWith($lines[$index], '==')) {
            $index++;
        }
        return trim($lines[$index]);
    }

    /**
     * Get issue number from headline
     *
     * @param string $headline
     * @return int
     */
    protected function extractIssueNumber(string $headline): int
    {
        return (int)substr($headline, strpos($headline, '#') + 1, 5);
    }

    /**
     * Get main information from a .rst file
     *
     * @param string $file
     * @return array
     */
    protected function getListEntry(string $file): array
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $headline = $this->extractHeadline($lines);
        $entry['headline'] = $headline;
        $entry['filepath'] = $file;
        $entry['tags'] = $this->extractTags($lines);
        $entry['tagList'] = implode(',', $entry['tags']);
        $entry['content'] = file_get_contents($file);
        $issueNumber = $this->extractIssueNumber($headline);

        return [$issueNumber => $entry];
    }

    /**
     * True if files within directory should be considered
     *
     * @param string $versionDirectory
     * @param string $version
     * @return bool
     */
    protected function isRelevantDirectory(string $versionDirectory, string $version): bool
    {
        return is_dir($versionDirectory) && $version !== '.' && $version !== '..';
    }

    /**
     * Handle a single directory
     *
     * @param string $docDirectory
     * @param string $version
     * @return array
     */
    protected function getDocumentationFilesForVersion(
        string $docDirectory,
        string $version
    ): array {
        $documentationFiles = [];
        if ($this->isRelevantDirectory($docDirectory, $version)) {
            $documentationFiles[$version] = [];
            $absolutePath = dirname($docDirectory) . DIRECTORY_SEPARATOR . $version;
            $rstFiles = scandir($docDirectory);
            foreach ($rstFiles as $file) {
                $fileInfo = pathinfo($file);
                if ($this->isRelevantFile($fileInfo)) {
                    $filePath = $absolutePath . DIRECTORY_SEPARATOR . $fileInfo['basename'];
                    $documentationFiles[$version] += $this->getListEntry($filePath);
                }
            }
        }

        return $documentationFiles;
    }

    /**
     * Merge tag list
     *
     * @param $documentationFiles
     * @return array
     */
    protected function collectTagTotal($documentationFiles): array
    {
        $tags = [];
        foreach ($documentationFiles as $versionArray) {
            foreach ($versionArray as $fileArray) {
                $tags = array_merge(array_unique($tags), $fileArray['tags']);
            }
        }

        return array_unique($tags);
    }

    /**
     * Return full tag list
     *
     * @return array
     */
    public function getTagsTotal(): array
    {
        return $this->tagsTotal;
    }
}
