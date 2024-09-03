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
 * Unlike other fixtures, not the executions/calls of something are evaluated,
 * but the definitions themselves. This fixture specifies a fixture class from an
 * abstract that defines an interface.
 * Also, another class is added that will not extend from that abstract.
 *
 * Fixture file
 * Placeholder (replaced in test):
 * §extensionScannerIgnoreFile
 */
class AbstractMethodImplementationMatcherFixture extends AbstractClassFixture
{
    // Matches
    public function aNormalMethod(): void {}

    // Matches
    public static function aStaticMethod(): void {}

    // No match
    public function aNormalUnmatchedMethod(): void {}

    // No match
    public static function aStaticUnmatchedMethod(): void {}
}

class ExtendedAbstractMethodImplementationMatcherFixture extends AbstractMethodImplementationMatcherFixture
{
    // Matches
    public function aNormalMethod(): void {}

    // Matches
    public static function aStaticMethod(): void {}

    // No match
    public function aNormalUnmatchedMethod(): void {}

    // No match
    public static function aStaticUnmatchedMethod(): void {}
}

class NonMatchingAbstractMethodImplementationMatcherFixture extends AbstractOtherClassFixture
{
    // No match
    public function aNormalMethod(): void {}

    // No match
    public static function aStaticMethod(): void {}

    // No match
    public function aNormalUnmatchedMethod(): void {}

    // No match
    public static function aStaticUnmatchedMethod(): void {}
}

class NonMatchingNonExtendedAbstractMethodImplementationMatcherFixture
{
    // No match
    public function aNormalMethod(): void {}

    // No match
    public static function aStaticMethod(): void {}

    // No match
    public function aNormalUnmatchedMethod(): void {}

    // No match
    public static function aStaticUnmatchedMethod(): void {}
}

interface InterfaceFixture
{
    public function irrelevantMethod(): void;
    public static function irrelevantStaticMethod(): void;
}

abstract class AbstractClassFixture implements InterfaceFixture
{
    public function irrelevantMethod(): void {}

    public static function irrelevantStaticMethod(): void {}
}
