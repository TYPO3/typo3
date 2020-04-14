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

namespace TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php\Matcher\Fixtures;

/**
 * Fixture file
 */
class PropertyExistsStaticMatcherFixture1
{
    protected static $iAmAMatch = 42;

    protected static $iAmNotAMatch;

    public static $iAmNotAMatchEither;

    private static $iAmNoMatchToo;
}

class PropertyExistsStaticMatcherFixture2
{
    public static $iAmAMatch;
}

class PropertyExistsStaticMatcherFixture3
{
    // Not a match: private
    private static $iAmAMatch;
}

class PropertyExistsStaticMatcherFixture4
{
    // Not a match: suppressed
    // @extensionScannerIgnoreLine
    public static $iAmAMatch;
}

class PropertyExistsStaticMatcherFixture5
{
    /**
     * Not a match: suppressed
     *
     * @extensionScannerIgnoreLine
     * @var string|null
     */
    public static $iAmAMatch;
}

class PropertyExistsStaticMatcherFixture6
{
    // Not a match: Not static
    public $iAmAMatch;
}
