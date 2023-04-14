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

use enshrined\svgSanitize\Sanitizer;

class SvgSanitizer
{
    /**
     * @param string|null $targetPath
     * @throws \BadFunctionCallException
     */
    public function sanitizeFile(string $sourcePath, string $targetPath = null): void
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

    /**
     * @throws \BadFunctionCallException
     */
    public function sanitizeContent(string $svg): string
    {
        // @todo: Simplify again when https://github.com/darylldoyle/svg-sanitizer/pull/90 is merged and released.
        $previousXmlErrorHandling = libxml_use_internal_errors(true);
        $sanitizer = new Sanitizer();
        $sanitizer->removeRemoteReferences(true);
        $sanitizedString = $sanitizer->sanitize($svg) ?: '';
        libxml_clear_errors();
        libxml_use_internal_errors($previousXmlErrorHandling);
        return $sanitizedString;
    }
}
