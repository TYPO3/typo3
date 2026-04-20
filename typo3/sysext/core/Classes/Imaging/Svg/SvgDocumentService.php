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
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;

/**
 * Stateless operations on {@see SvgDocument} instances: dimension
 * resolution, serialization and crop-scaling.
 *
 * The document to operate on is always passed in as the first argument,
 * no instance state is kept. Obtain documents via {@see SvgDocumentFactory}.
 *
 * @internal not part of TYPO3 Core API.
 */
#[Autoconfigure(public: true)]
final readonly class SvgDocumentService
{
    private const DEFAULT_DIMENSION = 64;

    /**
     * Resolve pixel dimensions of the SVG.
     *
     * Preference order:
     *   1. `viewBox` attribute (width = index 2, height = index 3)
     *   2. numeric `width` / `height` attributes (override viewBox values)
     *   3. non-numeric `width` / `height` (e.g. "100mm", "50%") only when
     *      no viewBox-derived value is present, stripped of their unit
     *   4. 64x64 fallback when nothing usable is found
     */
    public function getDimensions(SvgDocument $document): ImageDimension
    {
        $root = $document->documentElement;
        $viewBox = $root->getAttribute('viewBox');
        $widthAttr = $root->getAttribute('width');
        $heightAttr = $root->getAttribute('height');

        $width = null;
        $height = null;

        if ($viewBox !== '') {
            // SVG spec allows whitespace or comma separators.
            $parts = preg_split('/[\s,]+/', trim($viewBox)) ?: [];
            if (isset($parts[2]) && is_numeric($parts[2])) {
                $width = (int)(float)$parts[2];
            }
            if (isset($parts[3]) && is_numeric($parts[3])) {
                $height = (int)(float)$parts[3];
            }
        }

        if ($widthAttr !== '') {
            if (is_numeric($widthAttr)) {
                $width = (int)(float)$widthAttr;
            } elseif ($width === null) {
                // Unit like "mm", "cm", "%" - stripped because without an
                // output device (dpi) we cannot translate to pixels.
                $width = (int)$widthAttr;
            }
        }

        if ($heightAttr !== '') {
            if (is_numeric($heightAttr)) {
                $height = (int)(float)$heightAttr;
            } elseif ($height === null) {
                $height = (int)$heightAttr;
            }
        }

        return new ImageDimension(
            max(0, $width ?? self::DEFAULT_DIMENSION),
            max(0, $height ?? self::DEFAULT_DIMENSION),
        );
    }

    /**
     * Serialize the document as plain XML markup of its root element,
     * without an `<?xml ?>` prolog.
     */
    public function toXml(SvgDocument $document): string
    {
        return (string)$document->saveXML($document->documentElement);
    }

    /**
     * Serialize the document as inline HTML5-ready markup.
     *
     * Emits only the root `<svg>` element (no `<?xml ?>` prolog) and
     * applies the clean-ups needed when inlining SVG into an HTML
     * document:
     *
     *  - drops `xmlns="http://www.w3.org/2000/svg"` (HTML5 auto-places
     *    `<svg>` in the SVG namespace),
     *  - drops the legacy `version` attribute,
     *  - synthesizes a `viewBox` from `width`/`height` when missing so
     *    the SVG scales with CSS instead of rendering at intrinsic
     *    pixel size.
     *
     * Operates on a detached clone; the passed document is not mutated.
     */
    public function toInlineMarkup(SvgDocument $document): string
    {
        $clone = new \DOMDocument();
        $clone->appendChild($clone->importNode($document->documentElement, true));
        /** @var \DOMElement $root */
        $root = $clone->documentElement;

        $root->removeAttributeNS('http://www.w3.org/2000/svg', '');
        if ($root->hasAttribute('version')) {
            $root->removeAttribute('version');
        }
        if (!$root->hasAttribute('viewBox')
            && $root->hasAttribute('width')
            && $root->hasAttribute('height')
        ) {
            $root->setAttribute(
                'viewBox',
                sprintf('0 0 %d %d', (int)$root->getAttribute('width'), (int)$root->getAttribute('height')),
            );
        }

        return (string)$clone->saveXML($root);
    }

    /**
     * Wrap the source SVG in an outer `<svg>` that carries the crop viewBox
     * and target dimensions. The passed document is not mutated, a new
     * {@see SvgDocument} is returned.
     */
    public function cropScale(SvgDocument $document, Area $cropArea, ImageDimension $targetDimension): SvgDocument
    {
        $offsetLeft = (int)$cropArea->getOffsetLeft();
        $offsetTop = (int)$cropArea->getOffsetTop();
        // Rounding matches ImageDimension's width/height calculation.
        $newWidth = (int)round($cropArea->getWidth());
        $newHeight = (int)round($cropArea->getHeight());

        $sourceRoot = $document->documentElement;
        $intrinsic = $this->getDimensions($document);

        $wrapper = new SvgDocument('1.0');
        $wrapper->preserveWhiteSpace = true;
        $wrapper->formatOutput = true;

        // Deep-copy the source tree into the wrapper document so we never
        // mutate the passed document.
        /** @var \DOMElement $innerSvg */
        $innerSvg = $wrapper->importNode($sourceRoot, true);

        // Ensure the inner <svg> carries width/height; without them the
        // crop cannot render correctly when the file is embedded via <img>.
        if ($sourceRoot->getAttribute('width') === '') {
            $innerSvg->setAttribute('width', (string)$intrinsic->getWidth());
            $innerSvg->setAttribute('data-manipulated-width', 'true');
        }
        if ($sourceRoot->getAttribute('height') === '') {
            $innerSvg->setAttribute('height', (string)$intrinsic->getHeight());
            $innerSvg->setAttribute('data-manipulated-height', 'true');
        }

        $outerSvg = $wrapper->createElement('svg');
        $outerSvg->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        $outerSvg->setAttribute('viewBox', $offsetLeft . ' ' . $offsetTop . ' ' . $newWidth . ' ' . $newHeight);
        $outerSvg->setAttribute('width', (string)$targetDimension->getWidth());
        $outerSvg->setAttribute('height', (string)$targetDimension->getHeight());

        // Propagate preserveAspectRatio from the inner root onto the wrapper.
        $preserveAspectRatio = $sourceRoot->getAttribute('preserveAspectRatio');
        if ($preserveAspectRatio !== '') {
            $outerSvg->setAttribute('preserveAspectRatio', $preserveAspectRatio);
        }

        $outerSvg->appendChild($innerSvg);
        $wrapper->appendChild($outerSvg);

        return $wrapper;
    }
}
