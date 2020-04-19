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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Fixture file
 */
class ConstructorArgumentMatcherFixture extends Subject
{
    public function __construct(string $value)
    {
        parent::__construct($value);
    }

    public function invocations(): void
    {
        $a = new Subject('a', 'b', 'c');
        $b = new \TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php\Matcher\Fixtures\Subject('a', 'b', 'c');
        $c = GeneralUtility::makeInstance(Subject::class, 'a', 'b', 'c');
        $d = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php\Matcher\Fixtures\Subject::class, 'a', 'b', 'c');
        $className = Subject::class;
        $e = new $className('a', 'b', 'c');
    }

    public function unused(): void
    {
        $a = new Subject('a', null, 'c');
        $c = GeneralUtility::makeInstance(Subject::class, 'a', null, 'c');
    }
}
