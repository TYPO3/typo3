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

namespace TYPO3\CMS\Core\Schema;

/**
 * Introduce reserved language values that do not point to a configured site
 * language.
 *
 * Every other language value is the ID of a site language, so this is
 * deliberately a set of markers and not an enum: language values stay
 * integers, and these constants only name the values that carry a special
 * meaning.
 *
 * @internal not part of TYPO3 Core API.
 */
final class LanguageMarker
{
    /**
     * A record whose language field (usually "sys_language_uid") holds this
     * value is valid in every language of a site. It is never translated, and
     * it is never overlaid. Backend logic, selectors and filters use the same
     * value to express "all languages", even where it is not read from a
     * record.
     *
     * Other uses of -1, for example in calculations, are unrelated to this
     * marker.
     */
    public const int ALL_LANGUAGES = -1;
}
