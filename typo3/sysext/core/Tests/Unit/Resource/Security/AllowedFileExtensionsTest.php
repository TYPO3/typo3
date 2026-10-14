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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\Security\AllowedFileExtensions;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AllowedFileExtensionsTest extends UnitTestCase
{
    public static function fromListNormalizesFileExtensionsDataProvider(): array
    {
        return [
            'empty string' => ['', ''],
            'comma-separated string' => [' JPG, png ,,pdf ', 'jpg,png,pdf'],
            'list' => [['JPG', ' png', '', 'jpg'], 'jpg,png'],
        ];
    }

    #[DataProvider('fromListNormalizesFileExtensionsDataProvider')]
    #[Test]
    public function fromListNormalizesFileExtensions(string|array $fileExtensions, string $expected): void
    {
        self::assertSame($expected, AllowedFileExtensions::fromList($fileExtensions)->toString());
    }

    public static function allowsFileNameDataProvider(): array
    {
        return [
            'unrestricted allows any file extension' => ['', 'file.php', true],
            'unrestricted allows no file extension' => ['', 'README', true],
            'allowed file extension' => ['jpg,pdf', 'file.pdf', true],
            'allowed file extension in other case' => ['jpg,pdf', 'file.PDF', true],
            'other file extension' => ['jpg,pdf', 'file.png', false],
            'only last file extension counts' => ['jpg,pdf', 'file.pdf.png', false],
            'no file extension' => ['jpg,pdf', 'README', false],
        ];
    }

    #[DataProvider('allowsFileNameDataProvider')]
    #[Test]
    public function allowsFileName(string $fileExtensions, string $fileName, bool $expected): void
    {
        self::assertSame($expected, AllowedFileExtensions::fromList($fileExtensions)->allowsFileName($fileName));
    }

    public static function mergeAddsUpFileExtensionsDataProvider(): array
    {
        return [
            'both restricted' => ['jpg,png', 'png,pdf', 'jpg,png,pdf'],
            'first unrestricted' => ['', 'png,pdf', ''],
            'second unrestricted' => ['jpg,png', '', ''],
            'both unrestricted' => ['', '', ''],
        ];
    }

    #[DataProvider('mergeAddsUpFileExtensionsDataProvider')]
    #[Test]
    public function mergeAddsUpFileExtensions(string $first, string $second, string $expected): void
    {
        self::assertSame(
            $expected,
            AllowedFileExtensions::fromList($first)->merge(AllowedFileExtensions::fromList($second))->toString()
        );
    }

    public static function intersectNarrowsFileExtensionsDataProvider(): array
    {
        return [
            'both restricted' => ['jpg,png', 'png,pdf', ['png'], true],
            'first unrestricted' => ['', 'png,pdf', ['png', 'pdf'], true],
            'second unrestricted' => ['jpg,png', '', ['jpg', 'png'], true],
            'both unrestricted' => ['', '', [], false],
            'nothing in common' => ['jpg', 'pdf', [], true],
        ];
    }

    #[DataProvider('intersectNarrowsFileExtensionsDataProvider')]
    #[Test]
    public function intersectNarrowsFileExtensions(string $first, string $second, array $expected, bool $expectedRestricted): void
    {
        $subject = AllowedFileExtensions::fromList($first)->intersect(AllowedFileExtensions::fromList($second));

        self::assertSame($expected, $subject->toList());
        self::assertSame($expectedRestricted, $subject->isRestricted());
    }

    #[Test]
    public function intersectWithNothingInCommonAllowsNoFile(): void
    {
        $subject = AllowedFileExtensions::fromList('jpg')->intersect(AllowedFileExtensions::fromList('pdf'));

        self::assertFalse($subject->allowsFileName('image.jpg'));
        self::assertFalse($subject->allowsFileName('document.pdf'));
    }

    public static function allowsFileExtensionDataProvider(): array
    {
        return [
            'unrestricted' => ['', 'php', true],
            'allowed file extension' => ['jpg,pdf', 'pdf', true],
            'allowed file extension in other case' => ['jpg,pdf', 'PDF', true],
            'other file extension' => ['jpg,pdf', 'png', false],
        ];
    }

    #[DataProvider('allowsFileExtensionDataProvider')]
    #[Test]
    public function allowsFileExtension(string $fileExtensions, string $fileExtension, bool $expected): void
    {
        self::assertSame($expected, AllowedFileExtensions::fromList($fileExtensions)->allowsFileExtension($fileExtension));
    }

    #[Test]
    public function unrestrictedIsNotRestricted(): void
    {
        self::assertFalse(AllowedFileExtensions::unrestricted()->isRestricted());
        self::assertTrue(AllowedFileExtensions::fromList('jpg')->isRestricted());
    }
}
