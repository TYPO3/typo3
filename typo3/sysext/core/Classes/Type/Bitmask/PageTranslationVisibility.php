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

namespace TYPO3\CMS\Core\Type\Bitmask;

use TYPO3\CMS\Core\Type\BitSet;

/**
 * A class providing constants for bitwise operations on page translation handling
 * from $GLOBALS[TYPO3_CONF_VARS][FE][hidePagesIfNotTranslatedByDefault] and pages.l18n_cfg
 *
 * Side note: The DB field pages.l18n_cfg (bitmask) has l10n_mode=exclude meaning that it
 * can only be set on the default language and is automatically mirrored to all translated pages.
 */
final class PageTranslationVisibility extends BitSet
{
    private const HIDE_DEFAULT_LANGUAGE = 1;
    private const HIDE_TRANSLATION_IF_NO_TRANSLATED_RECORD_EXISTS = 2;

    /**
     * Due to the nature of the pages table, where you always have to have a page in the default
     * language, sometimes a page should be hidden in the default language (e.g. english), but
     * only visible in the created and available language=6 (e.g. polish).
     * This can be configured via pages.l18n_cfg=1 on a per-page basis.
     *
     * @return bool whether the page has the flag set
     */
    public function shouldBeHiddenInDefaultLanguage(): bool
    {
        return $this->get(self::HIDE_DEFAULT_LANGUAGE);
    }

    /**
     * Response on input location setting value whether the
     * page should be hidden if no translation exists.
     *
     * Imagine this:
     * - You link to page 23 in language=5 (e.g. italian)
     * - The page was never translated to language=5 (no pages record with sys_language_uid=5 created)
     * => Should the fallback kick in or not?
     *
     * The answer depends on your use-case (e.g. fallback of italian to english etc) and can
     * be tuned via the global configuration option and the pages.l18n_cfg=2 flag.
     *
     * @return bool true if the page should be hidden
     */
    public function shouldHideTranslationIfNoTranslatedRecordExists(): bool
    {
        return $GLOBALS['TYPO3_CONF_VARS']['FE']['hidePagesIfNotTranslatedByDefault'] xor ($this->get(self::HIDE_TRANSLATION_IF_NO_TRANSLATED_RECORD_EXISTS));
    }
}
