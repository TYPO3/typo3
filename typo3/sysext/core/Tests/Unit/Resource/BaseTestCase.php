<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource;

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

use org\bovigo\vfs\vfsStream;

/**
 * Basic test case for the \TYPO3\CMS\Core\Resource\File tests
 */
abstract class BaseTestCase extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var string
     */
    protected $basedir = 'basedir';

    protected $mountDir;

    protected $vfsContents = [];

    protected function setUp()
    {
        $this->mountDir = $this->getUniqueId('mount-');
        $this->basedir = $this->getUniqueId('base-');
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
        \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($this->vfsContents, $contents);
    }

    protected function initializeVfs()
    {
        if (is_callable('org\\bovigo\\vfs\\vfsStream::create') === false) {
            $this->markTestSkipped('vfsStream::create() does not exist');
        }
        vfsStream::create($this->vfsContents);
    }

    /**
     * Adds the given directory structure to the mount folder in the VFS. Existing files will be overwritten!
     *
     * @param array $dirStructure
     * @return void
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
     * @return void
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
        $mock = $this->getMock($type, $mockedMethods, [], '', false);
        $mock->expects($this->any())->method('getIdentifier')->will($this->returnValue($identifier));
        $mock->expects($this->any())->method('getName')->will($this->returnValue(basename($identifier)));
        return $mock;
    }

    /**
     * Returns a simple mock of a file object that just knows its identifier
     *
     * @param string $identifier
     * @param array $mockedMethods the methods to mock
     * @return \TYPO3\CMS\Core\Resource\File|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSimpleFileMock($identifier, $mockedMethods = [])
    {
        return $this->_createFileFolderMock(\TYPO3\CMS\Core\Resource\File::class, $identifier, $mockedMethods);
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
        return $this->_createFileFolderMock(\TYPO3\CMS\Core\Resource\Folder::class, $identifier, $mockedMethods);
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
        $folder = $this->_createFileFolderMock(\TYPO3\CMS\Core\Resource\Folder::class, $identifier, array_merge($mockedMethods, ['getFiles', 'getSubfolders']));
        $folder->expects($this->any())->method('getSubfolders')->will($this->returnValue($subfolders));
        $folder->expects($this->any())->method('getFiles')->will($this->returnValue($files));
        return $folder;
    }
}
