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

namespace TYPO3\CMS\Core\Resource\Security;

use enshrined\svgSanitize\data\XPath;
use enshrined\svgSanitize\ElementReference\Resolver;
use enshrined\svgSanitize\Sanitizer;

readonly class SvgSanitizer
{
    public function sanitizeFile(string $sourcePath, ?string $targetPath = null): void
    {
        if ($targetPath === null) {
            $targetPath = $sourcePath;
        }
        $svg = file_get_contents($sourcePath);
        if (!is_string($svg)) {
            return;
        }
        $sanitizedSvg = $this->sanitizeContent($svg);
        if ($sanitizedSvg !== $svg) {
            file_put_contents($targetPath, $sanitizedSvg);
        }
    }

    public function sanitizeContent(string $svg): string
    {
        $sanitizer = new Sanitizer();
        $sanitizer->removeRemoteReferences(true);
        return $sanitizer->sanitize($svg) ?: '';
    }

    public function sanitizeNode(
        \DOMNode $node,
    ): \DOMNode {
        $svgSanitizer = new class () extends Sanitizer {
            public function sanitizeDocument(\DOMDocument $document): void
            {
                $this->xmlDocument = $document;
                $this->setUpBefore();
                // Pre-process all identified elements
                $xPath = new XPath($this->xmlDocument);
                $this->elementReferenceResolver = new Resolver($xPath, $this->useNestingLimit);
                $this->elementReferenceResolver->collect();
                $elementsToRemove = $this->elementReferenceResolver->getElementsToRemove();
                // Start the cleaning process
                $this->startClean($this->xmlDocument->childNodes, $elementsToRemove);
                $this->resetAfter();
            }
        };

        $svg = new \DOMDocument();
        $svg->appendChild($svg->importNode($node, true));

        $svgSanitizer->removeRemoteReferences(true);
        $svgSanitizer->sanitizeDocument($svg);
        return $node->ownerDocument->importNode($svg->documentElement, true);
    }
}
