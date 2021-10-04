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

namespace TYPO3\CMS\Core\Resource;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This fixes import paths in CSS files if their location changes,
 * e.g. when inlining or compressing css
 *
 * @internal This class is not part of the TYPO3 API.
 */
class RelativeCssPathFixer
{
    /**
     * Fixes the relative paths inside of url() references in CSS files
     *
     * @param string $contents Data to process
     * @param string $newDir directory referenced from current location
     * @return string Processed data
     */
    public function fixRelativeUrlPaths(string $contents, string $newDir): string
    {
        // Replace "url()" paths
        if (stripos($contents, 'url') !== false) {
            $regex = '/url(\\(\\s*["\']?(?!\\/)([^"\']+)["\']?\\s*\\))/iU';
            $contents = $this->findAndReplaceUrlPathsByRegex($contents, $regex, $newDir, '(\'|\')');
        }
        // Replace "@import" paths
        if (stripos($contents, '@import') !== false) {
            $regex = '/@import\\s*(["\']?(?!\\/)([^"\']+)["\']?)/i';
            $contents = $this->findAndReplaceUrlPathsByRegex($contents, $regex, $newDir, '"|"');
        }
        return $contents;
    }

    /**
     * Finds and replaces all URLs by using a given regex
     *
     * @param string $contents Data to process
     * @param string $regex Regex used to find URLs in content
     * @param string $newDir Path to prepend to the original file
     * @param string $wrap Wrap around replaced values
     * @return string Processed data
     */
    protected function findAndReplaceUrlPathsByRegex(string $contents, string $regex, string $newDir, string $wrap = '|'): string
    {
        $matches = [];
        $replacements = [];
        $wrapParts = explode('|', $wrap);
        preg_match_all($regex, $contents, $matches);
        foreach ($matches[2] as $matchCount => $match) {
            // remove '," or white-spaces around
            $match = trim($match, '\'" ');
            // we must not rewrite paths containing ":" or "url(", e.g. data URIs (see RFC 2397)
            if (!str_contains($match, ':') && !preg_match('/url\\s*\\(/i', $match)) {
                $newPath = GeneralUtility::resolveBackPath($newDir . $match);
                $replacements[$matches[1][$matchCount]] = $wrapParts[0] . $newPath . $wrapParts[1];
            }
        }
        // replace URL paths in content
        if (!empty($replacements)) {
            $contents = str_replace(array_keys($replacements), array_values($replacements), $contents);
        }
        return $contents;
    }
}
