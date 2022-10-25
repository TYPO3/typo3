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

namespace TYPO3\CMS\Install\UpgradeAnalysis;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Provide information about documentation files.
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
final class DocumentationFile
{
    /**
     * all files handled in this Class need to reside inside the changelog dir
     * this is a security measure to protect system files
     */
    private string $changelogPath;

    public function __construct(string $changelogDir = '')
    {
        $this->changelogPath = $changelogDir !== '' ? $changelogDir : (string)realpath(ExtensionManagementUtility::extPath('core') . 'Documentation/Changelog');
        $this->changelogPath = str_replace('\\', '/', $this->changelogPath);
    }

    /**
     * Traverse given directory, select directories
     *
     * @return string[] Version directories
     * @throws \InvalidArgumentException
     */
    public function findDocumentationDirectories(string $path): array
    {
        if (strcasecmp($path, $this->changelogPath) < 0 || !str_contains($path, $this->changelogPath)) {
            throw new \InvalidArgumentException('the given path does not belong to the changelog dir. Aborting', 1537158043);
        }

        $currentVersion = (int)explode('.', VersionNumberUtility::getNumericTypo3Version())[0];
        $versions = range($currentVersion, $currentVersion - 2);
        $pattern = '(' . implode('\.*|', $versions) . '\.*)';
        $finder = new Finder();
        $finder
            ->depth(0)
            ->sortByName(true)
            ->name($pattern)
            ->in($path);

        $directories = [];
        foreach ($finder->directories() as $directory) {
            /** @var SplFileInfo $directory */
            $directories[] = $directory->getBasename();
        }

        return $directories;
    }

    /**
     * Traverse given directory, select files
     *
     * @return array file details of affected documentation files
     * @throws \InvalidArgumentException
     */
    public function findDocumentationFiles(string $path): array
    {
        if (strcasecmp($path, $this->changelogPath) < 0 || !str_contains($path, $this->changelogPath)) {
            throw new \InvalidArgumentException('the given path does not belong to the changelog dir. Aborting', 1485425530);
        }
        return $this->getDocumentationFilesForVersion($path);
    }

    /**
     * Get main information from a .rst file
     *
     * @param string $file Absolute path to documentation file
     * @throws \InvalidArgumentException
     */
    public function getListEntry(string $file): array
    {
        $entry = [];
        if (strcasecmp($file, $this->changelogPath) < 0 || !str_contains($file, $this->changelogPath)) {
            throw new \InvalidArgumentException('the given file does not belong to the changelog dir. Aborting', 1485425531);
        }
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = is_array($lines) ? $lines : [];
        $headline = $this->extractHeadline($lines);
        $entry['version'] = PathUtility::basename(PathUtility::dirname($file));
        $entry['headline'] = $headline;
        $entry['filepath'] = $file;
        $entry['filename'] = pathinfo($file)['filename'];
        $entry['tags'] = $this->extractTags($lines);
        $entry['class'] = 'default';
        foreach ($entry['tags'] as $key => $tag) {
            if (str_starts_with($tag, 'cat:')) {
                $substr = substr($tag, 4);
                $entry['class'] = strtolower($substr);
                $entry['tags'][$key] = $substr;
            }
        }
        $entry['tagList'] = implode(',', $entry['tags']);
        $entry['tagArray'] = $entry['tags'];
        $entry['content'] = (string)file_get_contents($file);
        $entry['parsedContent'] = $this->parseContent($entry['content']);
        $entry['file_hash'] = md5($entry['content']);
        if ($entry['version'] !== '') {
            $entry['url']['documentation'] = sprintf(
                'https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/%s/%s.html',
                $entry['version'],
                $entry['filename']
            );
        }
        $issueId = $this->parseIssueId($entry['filename']);
        if ($issueId) {
            $entry['url']['issue'] = sprintf('https://forge.typo3.org/issues/%s', $issueId);
        }

        return [md5($file) => $entry];
    }

    /**
     * Add tags from file
     *
     * @param array $file file content, each line is an array item
     */
    private function extractTags(array $file): array
    {
        $tags = $this->extractTagsFromFile($file);
        // Headline starting with the category like Breaking, Important or Feature
        $tags[] = $this->extractCategoryFromHeadline($file);
        natcasesort($tags);

        return $tags;
    }

    /**
     * Files must contain an index entry, detailing any number of manual tags
     * each of these tags is extracted and added to the general tag structure for the file
     *
     * @param array $file file content, each line is an array item
     * @return array extracted tags
     */
    private function extractTagsFromFile(array $file): array
    {
        foreach ($file as $line) {
            if (str_starts_with($line, '.. index::')) {
                $tagString = substr($line, strlen('.. index:: '));
                return GeneralUtility::trimExplode(',', $tagString, true);
            }
        }

        return [];
    }

    /**
     * Files contain a headline (provided as input parameter),
     * it starts with the category string.
     * This will be used as a tag.
     */
    private function extractCategoryFromHeadline(array $lines): string
    {
        $headline = $this->extractHeadline($lines);
        if (str_contains($headline, ':')) {
            return 'cat:' . substr($headline, 0, (int)strpos($headline, ':'));
        }
        return '';
    }

    /**
     * Skip include line and markers, use the first line actually containing text.
     */
    private function extractHeadline(array $lines): string
    {
        $index = 0;
        while (str_starts_with($lines[$index], '..') || str_starts_with($lines[$index], '==')) {
            $index++;
        }
        return trim($lines[$index]);
    }

    /**
     * Handle a single directory.
     */
    private function getDocumentationFilesForVersion(string $docDirectory): array
    {
        $documentationFiles = [[]];
        $absolutePath = str_replace('\\', '/', $docDirectory);
        $finder = $this->getDocumentFinder()->in($absolutePath);
        foreach ($finder->files() as $file) {
            /** @var SplFileInfo $file */
            $documentationFiles[] = $this->getListEntry($file->getPathname());
        }
        return array_merge(...$documentationFiles);
    }

    private function parseContent(string $rstContent): string
    {
        $content = htmlspecialchars($rstContent, ENT_COMPAT | ENT_SUBSTITUTE);
        $content = (string)preg_replace('/:issue:`([\d]*)`/', '<a href="https://forge.typo3.org/issues/\\1" target="_blank" rel="noreferrer">\\1</a>', $content);
        $content = (string)preg_replace('/#([\d]*)/', '#<a href="https://forge.typo3.org/issues/\\1" target="_blank" rel="noreferrer">\\1</a>', $content);
        $content = (string)preg_replace('/(\n([=]*)\n(.*)\n([=]*)\n)/', '', $content, 1);
        $content = (string)preg_replace('/.. index::(.*)/', '', $content);
        $content = (string)preg_replace('/.. include::(.*)/', '', $content);
        return trim($content);
    }

    private function parseIssueId(string $filename): ?string
    {
        return GeneralUtility::trimExplode('-', $filename)[1] ?? null;
    }

    private function getDocumentFinder(): Finder
    {
        $finder = new Finder();
        $finder
            ->depth(0)
            ->sortByName()
            ->name('/^(Feature|Breaking|Deprecation|Important)\-\d+.+\.rst$/i');
        return $finder;
    }
}
