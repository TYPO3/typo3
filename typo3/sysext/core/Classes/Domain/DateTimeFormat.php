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

namespace TYPO3\CMS\Core\Domain;

/**
 * @internal
 */
final readonly class DateTimeFormat
{
    // Like \DateTimeInterface::ATOM but without timezone offset,
    // e.g. 2005-08-15T15:52:01
    // Links:
    // * https://en.wikipedia.org/wiki/ISO_8601#Local_time_(unqualified)
    // * https://html.spec.whatwg.org/multipage/common-microsyntaxes.html#local-dates-and-times
    // * https://html.spec.whatwg.org/multipage/input.html#local-date-and-time-state-(type=datetime-local)
    public const ISO8601_LOCALTIME = 'Y-m-d\\TH:i:s';
}
