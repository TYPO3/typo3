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

namespace TYPO3\CMS\IndexedSearch\TextExtraction;

use TYPO3\CMS\Core\Attribute\AsTextExtractor;
use TYPO3\CMS\Core\Resource\TextExtraction\ExtractedText;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extracts text and the core properties of Word, PowerPoint and Excel
 * documents in the Office Open XML format with "unzip".
 *
 * @internal
 */
#[AsTextExtractor]
final class OfficeOpenXmlTextExtractor extends AbstractTextExtractor
{
    protected function getMimeTypes(): array
    {
        return [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'application/vnd.openxmlformats-officedocument.presentationml.template',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
        ];
    }

    protected function getRequiredTools(): array
    {
        return ['unzip'];
    }

    protected function extractFromLocalFile(SystemResourceInterface $file, string $localFilePath): ExtractedText
    {
        // The document kind is taken from the archive, the stored MIME type may be the generic "application/zip"
        $archivePaths = $this->listArchivePaths($localFilePath);
        $slidePaths = preg_grep('#^ppt/slides/slide\d+\.xml$#', $archivePaths) ?: [];
        $text = match (true) {
            in_array('word/document.xml', $archivePaths, true) => $this->stripXmlTags($this->readArchiveFile($localFilePath, 'word/document.xml')),
            $slidePaths !== [] => $this->extractSlides($localFilePath, $slidePaths),
            default => $this->stripXmlTags($this->readArchiveFile($localFilePath, 'xl/sharedStrings.xml')),
        };
        return $this->addCoreProperties(
            new ExtractedText($text, $file->getName()),
            $this->readArchiveFile($localFilePath, 'docProps/core.xml')
        );
    }

    /**
     * Merges the title, subject, description and keywords of the "docProps/core.xml" into the extracted text.
     */
    public function addCoreProperties(ExtractedText $extractedText, string $coreXml): ExtractedText
    {
        $metaContent = GeneralUtility::xml2tree($coreXml);
        if (!is_array($metaContent)) {
            return $extractedText;
        }
        $properties = $metaContent['cp:coreProperties'][0]['ch'] ?? [];
        return new ExtractedText(
            $extractedText->text,
            trim($extractedText->title . ' ' . ($properties['dc:title'][0]['values'][0] ?? '')),
            trim(($properties['dc:subject'][0]['values'][0] ?? '') . ' ' . ($properties['dc:description'][0]['values'][0] ?? '')),
            (string)($properties['cp:keywords'][0]['values'][0] ?? ''),
        );
    }

    /**
     * @param array<string> $slidePaths
     */
    private function extractSlides(string $localFilePath, array $slidePaths): string
    {
        $buffer = [];
        foreach ($slidePaths as $slidePath) {
            $buffer[] = $this->stripXmlTags($this->readArchiveFile($localFilePath, $slidePath));
        }
        return trim(implode(LF, $buffer));
    }

    /**
     * @return list<string>
     */
    private function listArchivePaths(string $localFilePath): array
    {
        $paths = [];
        foreach ($this->commandExecutor->execute($this->getToolPath('unzip') . ' -l ' . CommandUtility::escapeShellArgument($localFilePath)) as $line) {
            if (preg_match('#\s+(\S+\.xml)$#', $line, $matches)) {
                $paths[] = $matches[1];
            }
        }
        return $paths;
    }

    private function readArchiveFile(string $localFilePath, string $archivePath): string
    {
        $command = $this->getToolPath('unzip') . ' -p ' . CommandUtility::escapeShellArgument($localFilePath) . ' ' . CommandUtility::escapeShellArgument($archivePath);
        return $this->joinOutput($this->commandExecutor->execute($command));
    }
}
