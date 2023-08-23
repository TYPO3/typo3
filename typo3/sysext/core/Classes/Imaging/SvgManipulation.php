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

namespace TYPO3\CMS\Core\Imaging;

use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;

/**
 * Performs SVG cropping by applying a wrapper SVG as view
 *
 *  A simple SVG with an input like this:
 *
 *  <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0" y="0"
 *    viewBox="0 168.4 940.7 724" width="941" height="724">
 *    <path id="path" d="M490.1 655.5c-9.4 1.2-16.9
 *  </svg>
 *
 *  is wrapped with crop dimensions (i.e. "50 50 640 480") to something like this:
 *
 *  <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="50 50 640 480" width="640" height="480">
 *    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0" y="0"
 *      viewBox="0 168.4 940.7 724" width="941" height="724">
 *      <path id="path" d="M490.1 655.5c-9.4 1.2-16.9
 *    </svg>
 *  </svg>
 *
 * @internal not part of TYPO3 Core API.
 */
class SvgManipulation
{
    private int $defaultSvgDimension = 64;

    /**
     * @throws \DOMException
     */
    public function cropScaleSvgString(string $svgString, Area $cropArea, ImageDimension $imageDimension): \DOMDocument
    {
        $offsetLeft = (int)$cropArea->getOffsetLeft();
        $offsetTop = (int)$cropArea->getOffsetTop();
        // Rounding is applied to preserve the same width/height that imageDimension calculates
        $newWidth = (int)round($cropArea->getWidth());
        $newHeight = (int)round($cropArea->getHeight());

        // Load original SVG
        $originalSvg = new \DOMDocument();
        $originalSvg->loadXML($svgString);

        // Create a fresh wrapping <svg> tag
        $processedSvg = new \DOMDocument('1.0');
        $processedSvg->preserveWhiteSpace = true;
        $processedSvg->formatOutput = true;
        $outerSvgElement = $processedSvg->createElement('svg');
        $outerSvgElement->setAttribute('xmlns', 'http://www.w3.org/2000/svg');

        // Determine the SVG dimensions of the source SVG file contents
        $dimensions = $this->determineSvgDimensions($originalSvg);

        // Adjust the width/height attributes of the outer SVG proxy element, if they were empty before.
        $this->adjustSvgDimensions($originalSvg, $dimensions);

        // Set several attributes on the outer SVG proxy element (the "wrapper" of the real SVG)
        $outerSvgElement->setAttribute('viewBox', $offsetLeft . ' ' . $offsetTop . ' ' . $newWidth . ' ' . $newHeight);
        $outerSvgElement->setAttribute('width', (string)$imageDimension->getWidth());
        $outerSvgElement->setAttribute('height', (string)$imageDimension->getHeight());

        // Possibly prevent some attributes on the "inner svg" (original input) and transport them
        // to the new root (outerSvgElement). Currently only 'preserveAspectRatio'.
        if ($originalSvg->documentElement->getAttribute('preserveAspectRatio') != '') {
            $outerSvgElement->setAttribute('preserveAspectRatio', $originalSvg->documentElement->getAttribute('preserveAspectRatio'));
        }

        // To enable some debugging for embeddding the original determined dimensions into the SVG, use:
        // $outerSvgElement->setAttribute('data-inherit-width', (string)$dimensions['determined']['width']);
        // $outerSvgElement->setAttribute('data-inherit-height', (string)$dimensions['determined']['height']);

        // Attach the main source SVG element into our proxy SVG element.
        $innerSvgElement = $processedSvg->importNode($originalSvg->documentElement, true);

        // Stitch together the wrapper plus the old root element plus children,
        // so that $processedSvg contains the full XML tree
        $outerSvgElement->appendChild($innerSvgElement);
        $processedSvg->appendChild($outerSvgElement);

        return $processedSvg;
    }

    /**
     * Ensure that the determined width and height settings are attributes on the original <svg>.
     * If those were missing, cropping could not successfully be applied when getting
     * embedded and adjusted within a <img> element.
     *
     * Returns true, if the determined width/height has been injected into the main <svg>
     */
    protected function adjustSvgDimensions(\DOMDocument $originalSvg, array $determinedDimensions): bool
    {
        $isAltered = false;

        if ($determinedDimensions['original']['width'] === '') {
            $originalSvg->documentElement->setAttribute('width', $determinedDimensions['determined']['width']);
            $originalSvg->documentElement->setAttribute('data-manipulated-width', 'true');
            $isAltered = true;
        }

        if ($determinedDimensions['original']['height'] === '') {
            $originalSvg->documentElement->setAttribute('height', $determinedDimensions['determined']['height']);
            $originalSvg->documentElement->setAttribute('data-manipulated-height', 'true');
            $isAltered = true;
        }

        return $isAltered;
    }

    /**
     * Check an input SVG element for its dimensions through
     * width/height/viewBox attributes.
     *
     * Returns an array with the determined width/height.
     */
    protected function determineSvgDimensions(\DOMDocument $originalSvg): array
    {
        // A default used when SVG neither uses width, height nor viewBox
        // Files falling back to this are probably broken.
        $width = $height = null;

        $originalSvgViewBox = $originalSvg->documentElement->getAttribute('viewBox');
        $originalSvgWidth = $originalSvg->documentElement->getAttribute('width');
        $originalSvgHeight = $originalSvg->documentElement->getAttribute('height');

        // width/height can easily be used if they are numeric. Else, viewBox attribute dimensions
        // are evaluated. These are used as better fallback here, overridden if width/height exist.
        if ($originalSvgViewBox !== '') {
            $viewBoxParts = explode(' ', $originalSvgViewBox);
            if (isset($viewBoxParts[2]) && is_numeric($viewBoxParts[2])) {
                $width = $viewBoxParts[2];
            }

            if (isset($viewBoxParts[3]) && is_numeric($viewBoxParts[3])) {
                $height = $viewBoxParts[3];
            }
        }

        // width/height may contain percentages or units like "mm", "cm"
        // When non-numeric, we only use the width/height when no viewBox
        // exists (because the size of a viewBox would be preferred
        // to a non-numeric value), and then unify the unit as "1".
        if ($originalSvgWidth !== '') {
            if (is_numeric($originalSvgWidth)) {
                $width = $originalSvgWidth;
            } elseif ($width === null) {
                // contains a unit like "cm", "mm", "%", ...
                // Currently just stripped because without knowing the output
                // device, no pixel size can be calculated (missing dpi).
                // So we regard the unit to be "1" - this is how TYPO3
                // already did it when SVG file metadata was evaluated (before
                // cropping).
                $width = (int)$originalSvgWidth;
            }
        }

        if ($originalSvgHeight !== '') {
            if (is_numeric($originalSvgHeight)) {
                $height = $originalSvgHeight;
            } elseif ($height === null) {
                $height = (int)$originalSvgHeight;
            }
        }

        return [
            // The "proper" image dimensions (with viewBox preference)
            'determined' => [
                'width' => $width ?? $this->defaultSvgDimension,
                'height' => $height ?? $this->defaultSvgDimension,
            ],

            // Possible original "width/height" attributes (may not correlate with the viewBox, could be empty)
            'original' => [
                'width' => $originalSvgWidth,
                'height' => $originalSvgHeight,
            ],
        ];
    }
}
