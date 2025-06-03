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

namespace TYPO3\CMS\Core\DataHandling\SoftReference;

/**
 * Finding URLs in content
 */
class UrlSoftReferenceParser extends AbstractSoftReferenceParser
{
    /**
     * We do not use a-z for letters, so we can also allow unicode characters. For this purpose we use \p{L}, for example.
     * And we must use the modifier "u" in the regex.
     * Domains may contain umlauts (ä,ö,ü).
     *
     * PHP regex with unicode character properties:
     * \p{L}: letter
     * \p{Ll}: lower case letter
     * @see https://www.php.net/manual/en/regexp.reference.unicode.php
     */
    protected const REGEXP = '/([^[:alnum:]\'"=]+|\\s+)((https?|ftp):\\/\\/[!#$&-;=?-\[\]_\\p{L}~](?:[!#$&-;=?-\[\]_\\p{L}~]+|%[0-9\\p{L}]{2})*)/u';

    public function parse(string $table, string $field, int $uid, string $content, string $structurePath = ''): SoftReferenceParserResult
    {
        $elements = [];
        $modifiedContent = ' ' . $content . ' ';

        // Find all URLs using preg_match_all
        $matches = [];
        if (preg_match_all(self::REGEXP, $modifiedContent, $matches, PREG_SET_ORDER)) {
            // Process each match
            foreach ($matches as $idx => $match) {
                $prefix = $match[1];
                $url = $match[2];

                $tokenID = $this->makeTokenID((string)$idx);
                $elements[$idx] = [];
                $elements[$idx]['matchString'] = $url;

                if (in_array('subst', $this->parameters, true)) {
                    // Replace the URL with a token in the content
                    $modifiedContent = str_replace($prefix . $url, $prefix . '{softref:' . $tokenID . '}', $modifiedContent);
                    $elements[$idx]['subst'] = [
                        'type' => 'string',
                        'tokenID' => $tokenID,
                        'tokenValue' => $url,
                    ];
                }
            }
        }

        return SoftReferenceParserResult::create(
            substr($modifiedContent, 1, -1),
            $elements
        );
    }
}
