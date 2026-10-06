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

namespace TYPO3\CMS\Core\Localization;

/**
 * Interface for translation services.
 *
 * This interface provides a clean abstraction for translating labels in TYPO3.
 * It uses ICU MessageFormat style where pluralization and other complex formatting
 * logic is embedded in the message string itself, not in the API signature.
 *
 * Example usage:
 *
 *     // Simple translation
 *     $translator->translate('button.save', 'backend.messages');
 *
 *     // Translation with arguments (sprintf-style placeholders)
 *     $translator->translate('record.count', 'backend.messages', [5]);
 *
 *     // ICU MessageFormat style for plurals (message: "{count, plural, one {# item} other {# items}}")
 *     $translator->translate('items.count', 'backend.messages', ['count' => 5]);
 *
 * @see https://unicode-org.github.io/icu/userguide/format_parse/messages/ ICU MessageFormat
 */
interface TranslatorInterface
{
    /**
     * Translate a label by its identifier and domain.
     *
     * @param string $id The label identifier/key
     * @param string $domain The translation domain (file reference like 'EXT:core/Resources/Private/Language/locallang.xlf'
     *                       or semantic domain like 'core.messages').
     * @param array $arguments Optional arguments for placeholder replacement. For sprintf-style messages,
     *                         pass indexed values. For ICU messages, pass named values (e.g., ['count' => 5]).
     * @param string|null $default Optional default value
     * @param Locale|string|null $locale Optional locale override. If null, uses the service's configured locale.
     * @return string|\Stringable|null The translated string, or null if the label was not found
     */
    public function translate(string $id, string $domain, array $arguments = [], ?string $default = null, Locale|string|null $locale = null): string|\Stringable|null;

    /**
     * Translate a label by its full reference string.
     *
     * Resolves TYPO3 label reference strings in the formats:
     *
     *     'core.messages:labels.depth_0'
     *     'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'
     *     'EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'
     *
     * The LLL: prefix is optional and stripped before resolution.
     *
     * Example usage:
     *
     *     // Simple reference
     *     $translator->label('my_extension.messages:button.save');
     *
     *     // Simple reference (discouraged with file reference)
     *     $translator->label('LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:button.save');
     *
     *     // With arguments
     *     $translator->label('core.messages:record.count', [5]);
     *
     *     // With default value
     *     $translator->label('core.messages:missing.key', [], 'Fallback text');
     *
     * @param string $reference The label reference string (with or without LLL: prefix)
     * @param array $arguments Optional arguments for placeholder replacement
     * @param string|null $default Optional default value returned when the label is not found
     * @param Locale|string|null $locale Optional locale override. If null, uses the service's configured locale.
     * @return string|\Stringable|null The translated string, the default value, or null if the label was not found
     */
    public function label(string $reference, array $arguments = [], ?string $default = null, Locale|string|null $locale = null): string|\Stringable|null;
}
