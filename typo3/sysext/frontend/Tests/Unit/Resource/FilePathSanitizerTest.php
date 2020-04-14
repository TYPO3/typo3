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

namespace TYPO3\CMS\Frontend\Tests\Unit\Resource;

use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Frontend\Resource\FilePathSanitizer
 */
class FilePathSanitizerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function sanitizeReturnsUrlCorrectly(): void
    {
        $subject = new FilePathSanitizer();
        self::assertSame('http://example.com', $subject->sanitize('http://example.com'));
        self::assertSame('https://example.com', $subject->sanitize('https://example.com'));
    }

    /**
     * @test
     */
    public function sanitizeReturnsFileCorrectly(): void
    {
        $subject = new FilePathSanitizer();
        self::assertSame('typo3/index.php', $subject->sanitize('typo3/index.php'));
    }

    /**
     * @test
     */
    public function sanitizeFailsIfDirectoryGiven(): void
    {
        $this->expectException(FileDoesNotExistException::class);
        $subject = new FilePathSanitizer();
        $subject->sanitize(__DIR__);
    }

    /**
     * @test
     */
    public function sanitizeThrowsExceptionWithInvalidFileName(): void
    {
        $this->expectException(InvalidFileNameException::class);
        self::assertNull((new FilePathSanitizer())->sanitize('  '));
        self::assertNull((new FilePathSanitizer())->sanitize('something/../else'));
    }
}
