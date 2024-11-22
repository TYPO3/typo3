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

namespace TYPO3\CMS\Core\Slug;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Charset\CharsetConverter;

/**
 * Provides the ability to normalize a value to be used as url-part segment.
 *
 * @internal This class is still work in progress and subject of frequent change. Only use with caution.
 */
#[Autoconfigure(public: true)]
final readonly class SlugNormalizer
{
    public function __construct(
        private CharsetConverter $charsetConverter,
    ) {}

    /**
     * Normalizes a value to be used directly as path segment of a URL.
     */
    public function normalize(string $value, ?string $fallbackCharacter = '-'): string
    {
        $fallbackCharacter ??= '-';
        // Convert to lowercase + remove tags
        $value = mb_strtolower($value, 'utf-8');
        $value = strip_tags($value);

        // Convert some special tokens (space, "_" and "-") to the space character
        $value = (string)preg_replace('/[ \t\x{00A0}\-+_]+/u', $fallbackCharacter, $value);

        if (!\Normalizer::isNormalized($value)) {
            $value = \Normalizer::normalize($value) ?: $value;
        }

        // Convert extended letters to ascii equivalents, for example "€" to "EUR"
        $value = $this->charsetConverter->utf8_char_mapping($value);

        // Get rid of all invalid characters, but allow slashes
        $value = (string)preg_replace('/[^\p{L}\p{M}0-9\/' . preg_quote($fallbackCharacter) . ']/u', '', $value);

        // Convert multiple fallback characters to a single one
        if ($fallbackCharacter !== '') {
            $value = (string)preg_replace('/' . preg_quote($fallbackCharacter) . '{2,}/', $fallbackCharacter, $value);
        }

        // Ensure slug is lower cased after all replacement was done
        $value = mb_strtolower($value, 'utf-8');
        // Extract slug, thus it does not have wrapping fallback and slash characters
        $extractedSlug = trim($value, $fallbackCharacter . '/');

        // Remove trailing and beginning slashes, except if the trailing slash was added, then we'll re-add it
        $appendTrailingSlash = $extractedSlug !== '' && substr($value, -1) === '/';
        $value = $extractedSlug . ($appendTrailingSlash ? '/' : '');

        return $value;
    }
}
