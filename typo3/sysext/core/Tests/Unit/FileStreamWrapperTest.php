<?php
namespace TYPO3\CMS\Core\Tests\Unit;

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
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;
use TYPO3\CMS\Core\Tests\FileStreamWrapper;

/**
 * Test case for \TYPO3\CMS\Core\Tests\Unit\FileStreamWrapper
 */
class FileStreamWrapperTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function pathsAreOverlaidAndFinalDirectoryStructureCanBeQueried()
    {
        $root = vfsStream::setup('root');
        $subfolder = vfsStream::newDirectory('fileadmin');
        $root->addChild($subfolder);
        // Load fixture files and folders from disk
        vfsStream::copyFromFileSystem(__DIR__ . '/TypoScript/Fixtures', $subfolder, 1024*1024);
        FileStreamWrapper::init(PATH_site);
        FileStreamWrapper::registerOverlayPath('fileadmin', 'vfs://root/fileadmin', false);

        // Use file functions as normal
        mkdir(PATH_site . 'fileadmin/test/');
        $file = PATH_site . 'fileadmin/test/Foo.bar';
        file_put_contents($file, 'Baz');
        $content = file_get_contents($file);
        $this->assertSame('Baz', $content);

        $expectedFileSystem = [
            'root' => [
                'fileadmin' => [
                    'ext_typoscript_setup.txt' => 'test.Core.TypoScript = 1',
                    'test' => ['Foo.bar' => 'Baz'],
                ],
            ],
        ];
        $this->assertEquals($expectedFileSystem, vfsStream::inspect(new vfsStreamStructureVisitor())->getStructure());
        FileStreamWrapper::destroy();
    }

    /**
     * @test
     */
    public function windowsPathsCanBeProcessed()
    {
        $cRoot = 'C:\\Windows\\Root\\Path\\';
        $root = vfsStream::setup('root');
        FileStreamWrapper::init($cRoot);
        FileStreamWrapper::registerOverlayPath('fileadmin', 'vfs://root/fileadmin');

        touch($cRoot . 'fileadmin\\someFile.txt');
        $expectedFileStructure = [
            'root' => [
                'fileadmin' => ['someFile.txt' => null],
            ],
        ];

        $this->assertEquals($expectedFileStructure, vfsStream::inspect(new vfsStreamStructureVisitor())->getStructure());
        FileStreamWrapper::destroy();
    }

    /**
     * @test
     */
    public function symlinksCanBeCreated()
    {
        $this->markTestSkipped('symlink() is not routed through the stream wrapper as of PHP 5.5, therefore we cannot test it');
        /*
         * symlink() is not routed through the stream wrapper as of PHP 5.5,
         *  therefore we cannot test it.
         */
        vfsStream::setup('root');
        FileStreamWrapper::init(PATH_site);
        FileStreamWrapper::registerOverlayPath('fileadmin', 'vfs://root/fileadmin');

        $path = PATH_site . 'fileadmin/';
        touch($path . 'file1.txt');
        symlink($path . 'file1.txt', $path . 'file2.txt');

        $this->assertTrue(is_link($path . 'file2.txt'));
        FileStreamWrapper::destroy();
    }
}
