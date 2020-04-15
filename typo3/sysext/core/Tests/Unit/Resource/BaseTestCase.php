<?php

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

namespace TYPO3\CMS\Core\Tests\Unit\Resource;

use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Basic test case for the \TYPO3\CMS\Core\Resource\File tests
 */
abstract class BaseTestCase extends UnitTestCase
{
    /**
     * @var string
     */
    protected $basedir = 'basedir';

    protected $mountDir;

    protected $vfsContents = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->mountDir = StringUtility::getUniqueId('mount-');
        $this->basedir = StringUtility::getUniqueId('base-');
        vfsStream::setup($this->basedir);
        // Add an entry for the mount directory to the VFS contents
        $this->vfsContents = [$this->mountDir => []];
    }

    protected function getMountRootUrl()
    {
        return $this->getUrlInMount('');
    }

    protected function mergeToVfsContents($contents)
    {
        ArrayUtility::mergeRecursiveWithOverrule($this->vfsContents, $contents);
    }

    protected function initializeVfs()
    {
        vfsStream::create($this->vfsContents);
    }

    /**
     * Adds the given directory structure to the mount folder in the VFS. Existing files will be overwritten!
     *
     * @param array $dirStructure
     */
    protected function addToMount(array $dirStructure)
    {
        $this->mergeToVfsContents([$this->mountDir => $dirStructure]);
    }

    /**
     * Returns the URL for a path inside the mount directory
     *
     * @param $path
     * @return string
     */
    protected function getUrlInMount($path)
    {
        return vfsStream::url($this->basedir . '/' . $this->mountDir . '/' . ltrim($path, '/'));
    }

    /**
     * Adds the given directory structure to the VFS. Existing files will be overwritten!
     *
     * @param array $dirStructure
     */
    protected function addToVfs(array $dirStructure)
    {
        $this->mergeToVfsContents($dirStructure);
    }

    /**
     * Returns the URL for a path inside the VFS
     *
     * @param $path
     * @return string
     */
    protected function getUrl($path)
    {
        return vfsStream::url($this->basedir . '/' . ltrim($path, '/'));
    }

    /**
     * Creates a file or folder mock. This should not be called directly, but only through getSimple{File,Folder}Mock()
     *
     * @param $type
     * @param $identifier
     * @param $mockedMethods
     * @return \TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder
     */
    protected function _createFileFolderMock($type, $identifier, $mockedMethods)
    {
        if (!empty($mockedMethods)) {
            if (!in_array('getIdentifier', $mockedMethods)) {
                $mockedMethods[] = 'getIdentifier';
            }
            if (!in_array('getName', $mockedMethods)) {
                $mockedMethods[] = 'getName';
            }
        }
        $mock = $this->getMockBuilder($type)
            ->setMethods($mockedMethods)
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects(self::any())->method('getIdentifier')->willReturn($identifier);
        $mock->expects(self::any())->method('getName')->willReturn(basename($identifier));
        return $mock;
    }

    /**
     * Returns a simple mock of a file object that just knows its identifier
     *
     * @param string $identifier
     * @param array $mockedMethods the methods to mock
     * @return \TYPO3\CMS\Core\Resource\File|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getSimpleFileMock($identifier, $mockedMethods = [])
    {
        return $this->_createFileFolderMock(File::class, $identifier, $mockedMethods);
    }

    /**
     * Returns a simple mock of a file object that just knows its identifier
     *
     * @param string $identifier
     * @param array $mockedMethods the methods to mock
     * @return \TYPO3\CMS\Core\Resource\Folder
     */
    protected function getSimpleFolderMock($identifier, $mockedMethods = [])
    {
        return $this->_createFileFolderMock(Folder::class, $identifier, $mockedMethods);
    }

    /**
     * Returns a mock of a folder object with subfolders and files.
     *
     * @param $identifier
     * @param array $mockedMethods Methods to mock, in addition to getFiles and getSubfolders
     * @param \TYPO3\CMS\Core\Resource\Folder[] $subfolders
     * @param \TYPO3\CMS\Core\Resource\File[] $files
     * @return \TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder
     */
    protected function getFolderMock($identifier, $mockedMethods = [], $subfolders = [], $files = [])
    {
        $folder = $this->_createFileFolderMock(Folder::class, $identifier, array_merge($mockedMethods, ['getFiles', 'getSubfolders']));
        $folder->expects(self::any())->method('getSubfolders')->willReturn($subfolders);
        $folder->expects(self::any())->method('getFiles')->willReturn($files);
        return $folder;
    }
}
