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

namespace TYPO3\CMS\Core\Resource\TextExtraction;

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\MimeTypeCompatibilityTypeGuesser;
use TYPO3\CMS\Core\Resource\MimeTypeDetector;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;

/**
 * Registry for text extractors, which are registered as tagged services
 * via the #[AsTextExtractor] attribute or the 'fal.text_extractor'
 * service tag. Extractors are ordered by their tag priority, an
 * extractor with a higher priority is asked first whether it can
 * extract text from a file.
 */
class TextExtractorRegistry
{
    /**
     * Instance cache for text extractor classes
     *
     * @var list<TextExtractorInterface|StructuredTextExtractorInterface>|null
     */
    protected ?array $instances = null;

    /**
     * @param iterable<TextExtractorInterface|StructuredTextExtractorInterface> $textExtractors
     */
    public function __construct(
        protected readonly iterable $textExtractors = [],
        protected readonly MimeTypeDetector $mimeTypeDetector = new MimeTypeDetector(),
        protected readonly MimeTypeCompatibilityTypeGuesser $mimeTypeCompatibilityTypeGuesser = new MimeTypeCompatibilityTypeGuesser(),
    ) {}

    /**
     * @deprecated since TYPO3 v15.0, this method is a no-op and will be removed in TYPO3 v16.0. Register the text extractor as a tagged service using the #[AsTextExtractor] attribute instead.
     */
    public function registerTextExtractor(string $className): void {}

    /**
     * Get all registered text extractor instances
     *
     * @return list<TextExtractorInterface|StructuredTextExtractorInterface>
     */
    protected function getTextExtractorInstances(): array
    {
        if ($this->instances === null) {
            $this->instances = [];
            foreach ($this->textExtractors as $textExtractor) {
                $this->instances[] = $textExtractor;
            }
        }
        return $this->instances;
    }

    /**
     * Checks whether any registered text extractor can deal with a given file
     * and returns it.
     */
    public function getTextExtractor(FileInterface $file): ?TextExtractorInterface
    {
        foreach ($this->getTextExtractorInstances() as $textExtractor) {
            if ($textExtractor instanceof TextExtractorInterface && $textExtractor->canExtractText($file)) {
                return $textExtractor;
            }
        }
        return null;
    }

    /**
     * Returns the text extractor responsible for the resource, asking all
     * extractors in the order of their priority: a structured text extractor
     * if it supports the MIME type of the resource, any other text extractor
     * if it can extract the text of the FAL file. A file reference is resolved
     * to its original file.
     *
     * A generic MIME type, such as "application/zip" for a Word document, may
     * have been stored before "mimeTypeCompatibility" knew it. Only if no
     * extractor is found, the MIME type mapped by the file extension is used.
     */
    public function findTextExtractor(FileInterface|SystemResourceInterface $resource): TextExtractorInterface|StructuredTextExtractorInterface|null
    {
        return $this->selectTextExtractor($resource)[0];
    }

    /**
     * Extracts the text of a FAL file or of any other system resource, such as
     * a file of a package, with the extractor returned by findTextExtractor().
     * Other text extractors than structured ones only deal with FAL files and
     * only provide the text.
     */
    public function extract(FileInterface|SystemResourceInterface $resource): ?ExtractedText
    {
        [$textExtractor, $systemResource] = $this->selectTextExtractor($resource);
        if ($textExtractor instanceof StructuredTextExtractorInterface && $systemResource !== null) {
            return $textExtractor->extractStructuredText($systemResource);
        }
        if ($textExtractor instanceof TextExtractorInterface && $resource instanceof FileInterface) {
            return new ExtractedText($textExtractor->extractText($resource));
        }
        return null;
    }

    /**
     * @return array{0: TextExtractorInterface|StructuredTextExtractorInterface|null, 1: ?SystemResourceInterface}
     *         The extractor, and the system resource if it is to be handed to a structured text extractor
     */
    private function selectTextExtractor(FileInterface|SystemResourceInterface $resource): array
    {
        $systemResource = $this->resolveSystemResource($resource);
        $mimeType = $systemResource?->getMimeType();
        foreach ($this->getTextExtractorInstances() as $textExtractor) {
            if ($mimeType !== null
                && $textExtractor instanceof StructuredTextExtractorInterface
                && in_array($mimeType, $textExtractor->getSupportedMimeTypes(), true)
            ) {
                return [$textExtractor, $systemResource];
            }
            if ($resource instanceof FileInterface
                && $textExtractor instanceof TextExtractorInterface
                && $textExtractor->canExtractText($resource)
            ) {
                return [$textExtractor, null];
            }
        }
        if ($systemResource === null) {
            return [null, null];
        }
        $compatibleMimeType = $this->mimeTypeCompatibilityTypeGuesser->getMimeTypeCompatibilityList()[$mimeType][strtolower($systemResource->getExtension())] ?? null;
        if (is_string($compatibleMimeType)) {
            foreach ($this->getTextExtractorInstances() as $textExtractor) {
                if ($textExtractor instanceof StructuredTextExtractorInterface
                    && in_array($compatibleMimeType, $textExtractor->getSupportedMimeTypes(), true)
                ) {
                    return [$textExtractor, $systemResource];
                }
            }
        }
        return [null, null];
    }

    /**
     * The MIME types the registered extractors declare to handle. Only
     * extractors implementing StructuredTextExtractorInterface take part.
     *
     * @return list<string>
     */
    protected function getSupportedMimeTypes(): array
    {
        $mimeTypes = [];
        foreach ($this->getTextExtractorInstances() as $textExtractor) {
            if ($textExtractor instanceof StructuredTextExtractorInterface) {
                foreach ($textExtractor->getSupportedMimeTypes() as $mimeType) {
                    $mimeTypes[$mimeType] = true;
                }
            }
        }
        $mimeTypes = array_map(strval(...), array_keys($mimeTypes));
        sort($mimeTypes);
        return $mimeTypes;
    }

    /**
     * The file extensions known for the supported MIME types, for consumers
     * which offer file types for selection, such as a search form.
     *
     * @return list<string>
     */
    public function getSupportedFileExtensions(): array
    {
        $fileExtensions = [];
        foreach ($this->getSupportedMimeTypes() as $mimeType) {
            foreach ($this->mimeTypeDetector->getFileExtensionsForMimeType($mimeType) as $fileExtension) {
                $fileExtensions[$fileExtension] = true;
            }
        }
        $fileExtensions = array_map(strval(...), array_keys($fileExtensions));
        sort($fileExtensions);
        return $fileExtensions;
    }

    private function resolveSystemResource(FileInterface|SystemResourceInterface $resource): ?SystemResourceInterface
    {
        if ($resource instanceof SystemResourceInterface) {
            return $resource;
        }
        if ($resource instanceof FileReference) {
            return $resource->getOriginalFile();
        }
        return null;
    }
}
