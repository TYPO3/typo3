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

namespace TYPO3\CMS\Core\Schema\Field;

/**
 * Defines possible behaviour scenarios based on TCA settings
 * - 'l10n_mode' = exclude
 * - 'l10n_mode' = prefixLangTitle
 * - none = field is translatable
 */
enum FieldTranslationBehaviour
{
    /**
     * A field can be translated -> any custom value can be set.
     */
    case Translatable;

    /**
     * A field can be translated. A prefix is prepended on initial localization like this:
     * `[Translate to <language name>:]`
     */
    case PrefixLanguageTitle;

    /**
     * A field is excluded from the translation editing - means, it always has the same value
     * as the default translation
     */
    case Excluded;

    public static function tryFromFieldConfiguration(array $fieldConfiguration): self
    {
        $l10nMode = $fieldConfiguration['l10n_mode'] ?? null;
        if ($l10nMode === 'exclude') {
            return self::Excluded;
        }
        if ($l10nMode === 'prefixLangTitle') {
            return self::PrefixLanguageTitle;
        }
        return self::Translatable;
    }
}
