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

namespace TYPO3\CMS\IndexedSearch\Service;

use TYPO3\CMS\Core\Localization\TranslatorInterface;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry;
use TYPO3\CMS\IndexedSearch\TextExtraction\ExternalToolConfiguration;

/**
 * Describes the file types which can be indexed, based on the
 * registered text extractors: the extensions, the item type they are
 * stored as, and the label and icon which are shown in the frontend
 * plugin and the backend module.
 *
 * @internal
 */
final readonly class ExternalMediaTypes
{
    private const ITEM_TYPES = [
        'htm' => 'html',
        'jpg' => 'jpeg',
    ];

    private const LABEL_KEYS = [
        'pdf' => 'extension.PDF',
        'doc' => 'extension.DOC',
        'docx' => 'extension.DOC',
        'dotx' => 'extension.DOC',
        'pps' => 'extension.PP',
        'ppt' => 'extension.PP',
        'pptx' => 'extension.PP',
        'ppsx' => 'extension.PP',
        'potx' => 'extension.PP',
        'xls' => 'extension.XLS',
        'xlsx' => 'extension.XLS',
        'xltx' => 'extension.XLS',
        'sxc' => 'extension.SXC',
        'sxi' => 'extension.SXI',
        'sxw' => 'extension.SXW',
        'ods' => 'extension.ODS',
        'odp' => 'extension.ODP',
        'odt' => 'extension.ODT',
        'rtf' => 'extension.RTF',
        'jpeg' => 'extension.images',
        'jpg' => 'extension.images',
        'tif' => 'extension.images',
        'html' => 'extension.HTML',
        'htm' => 'extension.HTML',
        'txt' => 'extension.TXT',
        'csv' => 'extension.CSV',
        'xml' => 'extension.XML',
    ];

    public function __construct(
        private TextExtractorRegistry $textExtractorRegistry,
        private ExternalToolConfiguration $externalToolConfiguration,
    ) {}

    /**
     * File extensions for which text extractors exist, without the ones
     * excluded from indexing in the extension configuration.
     *
     * @return list<string>
     */
    public function getSupportedExtensions(): array
    {
        return array_values(array_filter(
            $this->textExtractorRegistry->getSupportedFileExtensions(),
            fn(string $extension): bool => !$this->externalToolConfiguration->isIgnored($extension)
        ));
    }

    /**
     * The value stored in the "item_type" field of the index, which unifies
     * different file extensions of the same kind of document.
     */
    public function getItemType(string $extension): string
    {
        $extension = strtolower($extension);
        return self::ITEM_TYPES[$extension] ?? $extension;
    }

    /**
     * Label of the file extension in the media type selector, an empty
     * string if there is none.
     */
    public function getLabel(string $extension, TranslatorInterface $translator): string
    {
        $extension = strtolower($extension);
        if (!isset(self::LABEL_KEYS[$extension])) {
            return '';
        }
        return (string)($translator->translate(self::LABEL_KEYS[$extension], 'indexed_search.main', [$extension]) ?? '');
    }

    public function getIcon(string $extension): string
    {
        $extension = strtolower($extension);
        if ($extension === 'htm') {
            $extension = 'html';
        } elseif ($extension === 'jpeg') {
            $extension = 'jpg';
        }
        // File types without an icon of their own, such as "dot" or "xlt", show none
        if (!is_file(dirname(__DIR__, 2) . '/Resources/Public/Icons/FileTypes/' . $extension . '.gif')) {
            return '';
        }
        return 'EXT:indexed_search/Resources/Public/Icons/FileTypes/' . $extension . '.gif';
    }

    /**
     * Whether documents of an item type are indexed in several parts, which
     * are the page ranges of a PDF file.
     */
    public function isMultiPageItemType(string $itemType): bool
    {
        return $itemType === 'pdf';
    }
}
