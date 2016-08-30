<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Filter;

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
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Filter\FileNameFilter;

/**
 * Testcase for the filename filter of the TYPO3 VFS
 */
class FileNameFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Return combinations of files and paths to test against.
     *
     * @return array
     */
    public function getItemsAndPathsWithoutHiddenFilesAndFolders_dataProvider()
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
     *
     * @return array
     */
    public function getItemsAndPathsWithHiddenFilesAndFolders_dataProvider()
    {
        return [
            ['file', '/file', true],
            ['.htaccess', '/.htaccess', true],
            ['applypatch-msg.sample', '/.git/applypatch-msg.sample', true],
            ['applypatch-msg.sample', '/user_upload/.git/applypatch-msg.sample', true],
        ];
    }

    /**
     * @test
     * @dataProvider getItemsAndPathsWithoutHiddenFilesAndFolders_dataProvider
     * @param string $itemName
     * @param string $itemIdentifier
     * @param bool|int $expected
     */
    public function filterHiddenFilesAndFoldersFiltersHiddenFilesAndFolders($itemName, $itemIdentifier, $expected)
    {
        /** @var DriverInterface|\PHPUnit_Framework_MockObject_MockObject $driverMock */
        $driverMock = $this->getMock(DriverInterface::class);
        $this->assertSame(
            $expected,
            FileNameFilter::filterHiddenFilesAndFolders(
                $itemName, $itemIdentifier, '', [], $driverMock
            )
        );
    }

    /**
     * @test
     * @dataProvider getItemsAndPathsWithHiddenFilesAndFolders_dataProvider
     * @param string $itemName
     * @param string $itemIdentifier
     * @param bool|int $expected
     */
    public function filterHiddenFilesAndFoldersAllowsHiddenFilesAndFolders($itemName, $itemIdentifier, $expected)
    {
        /** @var DriverInterface|\PHPUnit_Framework_MockObject_MockObject $driverMock */
        $driverMock = $this->getMock(DriverInterface::class);
        FileNameFilter::setShowHiddenFilesAndFolders(true);
        $this->assertSame(
            FileNameFilter::filterHiddenFilesAndFolders(
                $itemName, $itemIdentifier, '', [], $driverMock
            ),
            $expected
        );
    }
}
