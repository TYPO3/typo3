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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Filter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Filter\FileNameFilter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the filename filter of the TYPO3 VFS
 */
final class FileNameFilterTest extends UnitTestCase
{
    /**
     * Return combinations of files and paths to test against.
     */
    public static function getItemsAndPathsWithoutHiddenFilesAndFolders_dataProvider(): array
    {
        return [
            ['file', '/file', true],
            ['.htaccess', '/.htaccess', -1],
            ['applypatch-msg.sample', '/.git/applypatch-msg.sample', -1],
            ['applypatch-msg.sample', '/user_upload/.git/applypatch-msg.sample', -1],
        ];
    }

    /**
     * Return combinations of files and paths to test against.
     */
    public static function getItemsAndPathsWithHiddenFilesAndFolders_dataProvider(): array
    {
        return [
            ['file', '/file', true],
            ['.htaccess', '/.htaccess', true],
            ['applypatch-msg.sample', '/.git/applypatch-msg.sample', true],
            ['applypatch-msg.sample', '/user_upload/.git/applypatch-msg.sample', true],
        ];
    }

    /**
     * @param bool|int $expected
     */
    #[DataProvider('getItemsAndPathsWithoutHiddenFilesAndFolders_dataProvider')]
    #[Test]
    public function filterHiddenFilesAndFoldersFiltersHiddenFilesAndFolders(string $itemName, string $itemIdentifier, $expected): void
    {
        FileNameFilter::setShowHiddenFilesAndFolders(false);
        $driverMock = $this->createMock(DriverInterface::class);
        self::assertSame(
            $expected,
            FileNameFilter::filterHiddenFilesAndFolders(
                $itemName,
                $itemIdentifier,
                '',
                [],
                $driverMock
            )
        );
    }

    /**
     * @param bool|int $expected
     */
    #[DataProvider('getItemsAndPathsWithHiddenFilesAndFolders_dataProvider')]
    #[Test]
    public function filterHiddenFilesAndFoldersAllowsHiddenFilesAndFolders(string $itemName, string $itemIdentifier, $expected): void
    {
        $driverMock = $this->createMock(DriverInterface::class);
        FileNameFilter::setShowHiddenFilesAndFolders(true);
        self::assertSame(
            FileNameFilter::filterHiddenFilesAndFolders(
                $itemName,
                $itemIdentifier,
                '',
                [],
                $driverMock
            ),
            $expected
        );
    }
}
