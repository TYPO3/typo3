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

use TYPO3\CMS\Core\Resource\TextExtraction\StructuredTextExtractorInterface;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;

/**
 * A text extractor for documents which are indexed in several parts, such as
 * the page ranges of a PDF. Every part becomes an index entry of its own.
 *
 * Only PDF files are grouped by their parts in the search result, so this is
 * no extension point for other file types.
 *
 * @internal
 */
interface PartitionedTextExtractorInterface extends StructuredTextExtractorInterface
{
    /**
     * Provides the document divided into parts to the callback. The file is
     * read once for all parts which the callback extracts.
     *
     * @template T
     * @param \Closure(PartitionedDocument): T $callback
     * @return T
     */
    public function withPartitionedDocument(SystemResourceInterface $file, \Closure $callback): mixed;
}
