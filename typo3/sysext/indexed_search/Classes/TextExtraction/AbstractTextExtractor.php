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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\TextExtraction\ExtractedText;
use TYPO3\CMS\Core\Resource\TextExtraction\LocalFileAccess;
use TYPO3\CMS\Core\Resource\TextExtraction\StructuredTextExtractorInterface;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorInterface;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;

/**
 * Base class for the text extractors of indexed_search, working on a local
 * copy of the resource and, depending on the file type, on external tools.
 *
 * The extractors work on any system resource. They also implement the
 * file-based TextExtractorInterface, so they serve consumers of
 * TextExtractorRegistry::getTextExtractor() as well.
 *
 * @internal
 */
abstract class AbstractTextExtractor implements StructuredTextExtractorInterface, TextExtractorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private bool $missingToolsReported = false;

    public function __construct(
        protected readonly ExternalToolConfiguration $toolConfiguration,
        protected readonly ExternalCommandExecutor $commandExecutor,
    ) {}

    /**
     * @return list<string>
     */
    abstract protected function getMimeTypes(): array;

    /**
     * @return list<'pdfinfo'|'pdftotext'|'catdoc'|'ppthtml'|'xlhtml'|'unzip'|'unrtf'>
     */
    protected function getRequiredTools(): array
    {
        return [];
    }

    abstract protected function extractFromLocalFile(SystemResourceInterface $file, string $localFilePath): ExtractedText;

    /**
     * @return list<string>
     */
    public function getSupportedMimeTypes(): array
    {
        $missingTools = $this->getMissingTools();
        if ($missingTools === []) {
            return $this->getMimeTypes();
        }
        if (!$this->missingToolsReported) {
            $this->missingToolsReported = true;
            $this->logger?->notice(
                'The text extractor {extractor} for {mimeTypes} is not available, the external tool(s) {tools} are not configured or not found',
                [
                    'extractor' => static::class,
                    'mimeTypes' => implode(', ', $this->getMimeTypes()),
                    'tools' => implode(', ', $missingTools),
                ]
            );
        }
        return [];
    }

    public function canExtractText(FileInterface $file): bool
    {
        return in_array($file->getMimeType(), $this->getSupportedMimeTypes(), true);
    }

    public function extractText(FileInterface $file): string
    {
        $resource = $file instanceof FileReference ? $file->getOriginalFile() : $file;
        if (!$resource instanceof SystemResourceInterface) {
            return '';
        }
        return $this->extractStructuredText($resource)->text;
    }

    public function extractStructuredText(SystemResourceInterface $file): ExtractedText
    {
        if (!$this->checkRequiredTools($file)) {
            return new ExtractedText('');
        }
        return $this->withLocalFile($file, fn(string $localFilePath): ExtractedText => $this->extractFromLocalFile($file, $localFilePath));
    }

    /**
     * Removes some strange char(12) characters and line breaks that tend to occur at the end of external tool output.
     */
    public static function removeEndJunk(string $string): string
    {
        return trim((string)preg_replace('/[' . LF . chr(12) . ']*$/', '', $string));
    }

    /**
     * @return list<string>
     */
    protected function getMissingTools(): array
    {
        $missingTools = [];
        foreach ($this->getRequiredTools() as $tool) {
            if ($this->toolConfiguration->getToolPath($tool) === null) {
                $missingTools[] = $tool;
            }
        }
        return $missingTools;
    }

    protected function checkRequiredTools(SystemResourceInterface $file): bool
    {
        $missingTools = $this->getMissingTools();
        if ($missingTools === []) {
            return true;
        }
        $this->logger?->notice(
            'Cannot extract text from "{file}", the external tool(s) {tools} are not configured or not found',
            ['file' => $file->getResourceIdentifier(), 'tools' => implode(', ', $missingTools)]
        );
        return false;
    }

    protected function getToolPath(string $tool): string
    {
        return (string)$this->toolConfiguration->getToolPath($tool);
    }

    /**
     * @param list<string> $output
     */
    protected function joinOutput(array $output): string
    {
        return implode(LF, $output);
    }

    protected function stripXmlTags(string $xml): string
    {
        return trim(strip_tags(str_replace('<', ' <', $xml)));
    }

    /**
     * Provides a local copy of the file to the callback, and cleans up afterwards.
     *
     * @template T
     * @param \Closure(string): T $callback
     * @return T
     */
    protected function withLocalFile(SystemResourceInterface $file, \Closure $callback): mixed
    {
        return LocalFileAccess::withLocalFile($file, $callback);
    }
}
