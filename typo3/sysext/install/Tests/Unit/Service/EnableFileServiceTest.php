<?php
namespace TYPO3\CMS\Install\Tests\Unit\Service;

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
 * Test case
 */
class EnableFileServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Data provider
     *
     * @return array
     */
    public function getFirstInstallFilePathsDataProvider()
    {
        return [
            'first-install-file-present' => [
                [
                    'FIRST_INSTALL2Folder' => [],
                    'FIRST_INSTALL' => '',
                    'FIRST_INStall' => '',
                    'FIRST_INSTALL.txt' => 'with content',
                    'somethingelse' => '',
                    'dadadaFIRST_INStall' => '',
                ],
                [
                    'FIRST_INSTALL',
                    'FIRST_INStall',
                    'FIRST_INSTALL.txt',
                ],
            ],
            'no-first-install-file' => [
                [
                    'FIRST_INSTALL2Folder' => [],
                    'foo' => '',
                    'bar' => '',
                    'ddd.txt' => 'with content',
                    'somethingelse' => '',
                    'dadadaFIRST_INStall' => '',
                ],
                [],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getFirstInstallFilePathsDataProvider
     */
    public function getFirstInstallFilePaths($structure, $expected)
    {
        $vfs = vfsStream::setup('root');
        vfsStream::create($structure, $vfs);
        /** @var $instance \TYPO3\CMS\Install\Service\EnableFileService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(\TYPO3\CMS\Install\Service\EnableFileService::class, ['dummy'], [], '', false);
        $instance->_setStatic('sitePath', 'vfs://root/');
        $this->assertEquals([], array_diff($expected, $instance->_call('getFirstInstallFilePaths')));
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function removeFirstInstallFileDataProvider()
    {
        return [
            'first-install-file-present' => [
                [
                    'FIRST_INSTALL2Folder' => [],
                    'FIRST_INSTALL' => '',
                    'FIRST_INStall' => '',
                    'FIRST_INSTALL.txt' => 'with content',
                    'somethingelse' => '',
                    'dadadaFIRST_INStall' => '',
                ],
                [
                    '.',
                    '..',
                    'FIRST_INSTALL2Folder',
                    'somethingelse',
                    'dadadaFIRST_INStall',
                ],
            ],
            'no-first-install-file' => [
                [
                    'FIRST_INSTALL2Folder' => [],
                    'foo' => '',
                    'bar' => '',
                    'ddd.txt' => 'with content',
                    'somethingelse' => '',
                    'dadadaFIRST_INStall' => '',
                ],
                [
                    '.',
                    '..',
                    'FIRST_INSTALL2Folder',
                    'foo',
                    'bar',
                    'ddd.txt',
                    'somethingelse',
                    'dadadaFIRST_INStall',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider removeFirstInstallFileDataProvider
     */
    public function removeFirstInstallFile($structure, $expected)
    {
        $vfs = vfsStream::setup('root');
        vfsStream::create($structure, $vfs);
        /** @var $instance \TYPO3\CMS\Install\Service\EnableFileService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(\TYPO3\CMS\Install\Service\EnableFileService::class, ['dummy'], [], '', false);
        $instance->_setStatic('sitePath', 'vfs://root/');
        $instance->_call('removeFirstInstallFile');

        $this->assertEquals([], array_diff($expected, scandir('vfs://root/')));
    }
}
