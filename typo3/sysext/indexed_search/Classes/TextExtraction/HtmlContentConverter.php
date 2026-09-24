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

use TYPO3\CMS\Core\Resource\TextExtraction\ExtractedText;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Converts HTML documents, as produced by external tools or read from
 * HTML files, to UTF-8 and splits them into title, meta data and body text.
 *
 * @internal
 */
final readonly class HtmlContentConverter
{
    /**
     * Extracts the charset value from the "Content-Type" meta tag.
     */
    public function getCharset(string $content): string
    {
        if (preg_match('/<meta[[:space:]]+[^>]*http-equiv[[:space:]]*=[[:space:]]*["\']CONTENT-TYPE["\'][^>]*>/i', $content, $reg)
            && preg_match('/charset[[:space:]]*=[[:space:]]*([[:alnum:]-]+)/i', $reg[0], $reg2)
        ) {
            return $reg2[1];
        }
        return '';
    }

    /**
     * Converts a document to UTF-8 and decodes HTML entities. If no charset is
     * given, it is detected from the "Content-Type" meta tag.
     */
    public function toUtf8(string $content, string $charset = ''): string
    {
        $charset = strtolower(trim($charset ?: $this->getCharset($content)));
        if ($charset !== '' && $charset !== 'utf-8') {
            try {
                $content = mb_convert_encoding($content, 'utf-8', $charset);
            } catch (\ValueError) {
                // unknown charset, the content is used as it is
            }
        }
        return html_entity_decode($content);
    }

    /**
     * Splits an UTF-8 HTML document into title, keywords, description and body text.
     * The title may be prefixed with a ":" separated label, which is dropped.
     */
    public function split(string $content): ExtractedText
    {
        $body = stristr($content, '<body');
        if ($body === false) {
            $body = $this->removeElements($content, 'head');
            $head = $content;
        } else {
            $head = substr($content, 0, -strlen($body));
        }

        $title = '';
        if (preg_match('/<title(?:\s[^>]*)?>(.*?)<\/title>/is', $head, $titleMatch)) {
            $titleParts = explode(':', $titleMatch[1], 2);
            $title = trim($titleParts[1] ?? $titleParts[0]);
        }

        $keywords = [];
        $descriptions = [];
        if (preg_match_all('/<meta\b[^>]*>/i', $head, $metaTags)) {
            foreach ($metaTags[0] as $metaTag) {
                $attributes = GeneralUtility::get_tag_attributes(substr($metaTag, 5, -1), true);
                $name = (string)($attributes['name'] ?? '');
                $metaContent = (string)($attributes['content'] ?? '');
                if (stripos($name, 'keywords') !== false) {
                    $keywords[] = implode(', ', GeneralUtility::trimExplode(',', $metaContent));
                }
                if (stripos($name, 'description') !== false) {
                    $descriptions[] = $metaContent;
                }
            }
        }

        $body = $this->keepSearchSections($body);
        foreach (['script', 'style'] as $tag) {
            $body = $this->removeElements($body, $tag);
        }
        $body = trim(strip_tags(str_replace('<', ' <', $body)));

        return new ExtractedText(
            $body,
            $title,
            trim(implode(',', $descriptions)),
            trim(implode(',', $keywords)),
        );
    }

    /**
     * Reduces the body to the sections marked with <!--TYPO3SEARCH_begin-->
     * and <!--TYPO3SEARCH_end-->, as done for pages. A section is kept if it
     * follows a "begin" or precedes an "end" marker.
     */
    private function keepSearchSections(string $body): string
    {
        $parts = preg_split('/<!--\s?TYPO3SEARCH_(begin|end)\s*-->/', $body, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false || count($parts) === 1) {
            return $body;
        }
        $sections = [];
        for ($i = 0; $i < count($parts); $i += 2) {
            if (($parts[$i - 1] ?? '') === 'begin' || ($parts[$i + 1] ?? '') === 'end') {
                $sections[] = $parts[$i];
            }
        }
        return implode(' ', $sections);
    }

    private function removeElements(string $html, string $tagName): string
    {
        return (string)preg_replace('/<' . $tagName . '(?:\s[^>]*)?>.*?<\/' . $tagName . '\s*>/is', '', $html);
    }
}
