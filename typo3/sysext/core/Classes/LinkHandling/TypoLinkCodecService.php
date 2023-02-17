<?php

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

namespace TYPO3\CMS\Core\LinkHandling;

/**
 * This class provides basic functionality to encode and decode typolink strings
 */
class TypoLinkCodecService
{
    /**
     * Delimiter for TypoLink string parts
     *
     * @var string
     */
    protected static $partDelimiter = ' ';

    /**
     * Symbol for TypoLink parts not specified
     *
     * @var string
     */
    protected static $emptyValueSymbol = '-';

    /**
     * Encode TypoLink parts to a single string
     *
     * @param array{url?: string, target?: string, class?: string, title?: string, additionalParams?: string} $typoLinkParts
     * @return string Returns a correctly encoded TypoLink string
     */
    public function encode(array $typoLinkParts)
    {
        if (empty($typoLinkParts) || !isset($typoLinkParts['url'])) {
            return '';
        }

        // Get empty structure
        $reverseSortedParameters = array_reverse($this->decode(''), true);
        $aValueWasSet = false;
        foreach ($reverseSortedParameters as $key => &$value) {
            $value = $typoLinkParts[$key] ?? '';
            // escape special character \ and "
            $value = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
            // enclose with quotes if a string contains the delimiter
            if (str_contains($value, static::$partDelimiter)) {
                $value = '"' . $value . '"';
            }
            // fill with - if another values has already been set
            if ($value === '' && $aValueWasSet) {
                $value = static::$emptyValueSymbol;
            }
            if ($value !== '') {
                $aValueWasSet = true;
            }
        }

        return trim(implode(static::$partDelimiter, array_reverse($reverseSortedParameters, true)));
    }

    /**
     * Decodes a TypoLink string into its parts
     *
     * @param string|null $typoLink The properly encoded TypoLink string
     * @return array{url: string, target: string, class: string, title: string, additionalParams: string}
     * @todo Remove the |null and make the signature strict typed in v13 as string
     */
    public function decode($typoLink)
    {
        $typoLink = trim((string)$typoLink);
        if ($typoLink !== '') {
            $parts = str_replace(['\\\\', '\\"'], ['\\', '"'], str_getcsv($typoLink, static::$partDelimiter));
        } else {
            $parts = [];
        }

        // The order of the entries is crucial!!
        $typoLinkParts = [
            'url' => isset($parts[0]) ? trim($parts[0]) : '',
            'target' => isset($parts[1]) && $parts[1] !== static::$emptyValueSymbol ? trim($parts[1]) : '',
            'class' => isset($parts[2]) && $parts[2] !== static::$emptyValueSymbol ? trim($parts[2]) : '',
            'title' => isset($parts[3]) && $parts[3] !== static::$emptyValueSymbol ? trim($parts[3]) : '',
            'additionalParams' => isset($parts[4]) && $parts[4] !== static::$emptyValueSymbol ? trim($parts[4]) : '',
        ];

        return $typoLinkParts;
    }
}
