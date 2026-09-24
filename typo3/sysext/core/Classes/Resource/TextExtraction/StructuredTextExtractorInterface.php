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

use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;

/**
 * A text extractor that is bound to MIME types and returns, next to the
 * text, the metadata it found in the resource, such as a title or keywords.
 *
 * It works on any system resource: files of a FAL storage, which implement
 * SystemResourceInterface, as well as resources of packages or public files
 * outside of any storage. The TextExtractorRegistry selects the extractor
 * by the MIME type of the resource.
 *
 * Extractors implementing this interface are registered like any other text
 * extractor via #[AsTextExtractor]. They may additionally implement
 * TextExtractorInterface to also serve consumers of
 * TextExtractorRegistry::getTextExtractor().
 */
interface StructuredTextExtractorInterface
{
    /**
     * Lowercase MIME types which this extractor can currently handle. Generic
     * detections, such as "application/zip" for a Word document, are resolved
     * to the concrete MIME type by the file extension via
     * $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['mimeTypeCompatibility'],
     * so an extractor does not need to declare them.
     * An extractor depending on an external tool or on a PHP extension returns
     * an empty list as long as that dependency is not available, so that
     * consumers can list only the file types that are actually supported
     * without having a resource at hand.
     *
     * @return list<string>
     */
    public function getSupportedMimeTypes(): array;

    /**
     * The actual text extraction, returning the text and the metadata of the resource
     */
    public function extractStructuredText(SystemResourceInterface $resource): ExtractedText;
}
