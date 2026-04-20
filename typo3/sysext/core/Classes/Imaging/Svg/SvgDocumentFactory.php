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

namespace TYPO3\CMS\Core\Imaging\Svg;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Imaging\Exception\InvalidSvgException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Security\SvgSanitizer;

/**
 * Creates {@see SvgDocument} instances from strings or files.
 *
 * Inject this service wherever an SVG needs to be loaded, do not
 * construct SvgDocument directly. Transformations and serialization of a
 * loaded document are handled by {@see SvgDocumentService}.
 *
 * @internal not part of TYPO3 Core API.
 */
#[Autoconfigure(public: true)]
final readonly class SvgDocumentFactory
{
    public function __construct(
        private SvgSanitizer $svgSanitizer,
    ) {}

    public function fromString(string $svg): SvgDocument
    {
        if (trim($svg) === '') {
            throw new InvalidSvgException('SVG content is empty.', 1744620001);
        }

        $document = new SvgDocument();
        $previousUseErrors = libxml_use_internal_errors(true);
        try {
            $loaded = $document->loadXML($svg, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseErrors);
        }

        if ($loaded === false || $document->documentElement === null) {
            throw new InvalidSvgException('SVG content could not be parsed as XML.', 1744620002);
        }

        return $document;
    }

    /**
     * Parse and fully sanitize an SVG string in one step.
     *
     * @param bool $removeLinks additionally drop `<a>` elements, for
     *             contexts where the rendered SVG must not contain
     *             clickable areas.
     */
    public function fromStringAndSanitize(string $svg, bool $removeLinks = false): SvgDocument
    {
        $document = $this->fromString($svg);
        // Minify so the reparsed document carries no whitespace text nodes
        // from the sanitizer's pretty-printer.
        $sanitizedXml = $this->svgSanitizer->sanitizeContent(
            (string)$document->saveXML($document->documentElement),
            true,
            $removeLinks,
        );
        return $this->fromString($sanitizedXml);
    }

    public function fromFile(FileInterface|string $file): SvgDocument
    {
        if ($file instanceof FileInterface) {
            $content = $file->getContents();
        } else {
            $content = @file_get_contents($file);
        }
        if ($content === false) {
            $name = $file instanceof FileInterface ? $file->getIdentifier() : $file;
            throw new InvalidSvgException(
                sprintf('SVG file "%s" could not be read.', $name),
                1744620003,
            );
        }
        return $this->fromString($content);
    }
}
