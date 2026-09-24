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
 * Extracts text and meta data of OpenDocument and OpenOffice.org
 * documents with "unzip".
 *
 * @internal
 */
#[AsTextExtractor]
final class OpenDocumentTextExtractor extends AbstractTextExtractor
{
    protected function getMimeTypes(): array
    {
        return [
            'application/vnd.sun.xml.calc',
            'application/vnd.sun.xml.impress',
            'application/vnd.sun.xml.writer',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/vnd.oasis.opendocument.presentation',
            'application/vnd.oasis.opendocument.text',
        ];
    }

    protected function getRequiredTools(): array
    {
        return ['unzip'];
    }

    protected function extractFromLocalFile(SystemResourceInterface $file, string $localFilePath): ExtractedText
    {
        $text = $this->stripXmlTags($this->readArchiveFile($localFilePath, 'content.xml'));
        return $this->addMetaProperties(
            new ExtractedText($text, $file->getName()),
            $this->readArchiveFile($localFilePath, 'meta.xml')
        );
    }

    /**
     * Merges the title, subject, description and keywords of the "meta.xml" into the extracted text.
     */
    public function addMetaProperties(ExtractedText $extractedText, string $metaXml): ExtractedText
    {
        $metaContent = GeneralUtility::xml2tree($metaXml);
        $properties = is_array($metaContent) ? ($metaContent['office:document-meta'][0]['ch']['office:meta'][0]['ch'] ?? null) : null;
        if (!is_array($properties)) {
            return $extractedText;
        }
        $keywords = '';
        foreach ($properties['meta:keywords'][0]['ch']['meta:keyword'] ?? [] as $keyword) {
            $keywords .= ($keyword['values'][0] ?? '') . ' ';
        }
        return new ExtractedText(
            $extractedText->text,
            ($properties['dc:title'][0]['values'][0] ?? '') ?: $extractedText->title,
            trim(($properties['dc:subject'][0]['values'][0] ?? '') . ' ' . ($properties['dc:description'][0]['values'][0] ?? '')),
            trim($keywords),
        );
    }

    private function readArchiveFile(string $localFilePath, string $archivePath): string
    {
        $command = $this->getToolPath('unzip') . ' -p ' . CommandUtility::escapeShellArgument($localFilePath) . ' ' . CommandUtility::escapeShellArgument($archivePath);
        return $this->joinOutput($this->commandExecutor->execute($command));
    }
}
