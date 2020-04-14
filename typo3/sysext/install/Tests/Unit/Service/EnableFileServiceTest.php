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

namespace TYPO3\CMS\Install\Tests\Unit\Service;

use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Install\Service\EnableFileService;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class EnableFileServiceTest extends UnitTestCase
{
    /**
     * @var bool This test fiddles with Environment
     */
    protected $backupEnvironment = true;

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
        /** @var $subject EnableFileService|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $subject = $this->getAccessibleMock(EnableFileService::class, ['dummy'], [], '', false);
        Environment::initialize(
            Environment::getContext(),
            Environment::isCli(),
            Environment::isComposerMode(),
            Environment::getProjectPath(),
            'vfs://root',
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            'UNIX'
        );
        self::assertEquals([], array_diff($expected, $subject->_call('getFirstInstallFilePaths')));
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
        /** @var $subject EnableFileService|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $subject = $this->getAccessibleMock(EnableFileService::class, ['dummy'], [], '', false);
        Environment::initialize(
            Environment::getContext(),
            Environment::isCli(),
            Environment::isComposerMode(),
            Environment::getProjectPath(),
            'vfs://root',
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            'UNIX'
        );
        $subject->_call('removeFirstInstallFile');

        self::assertEquals([], array_diff($expected, scandir('vfs://root/')));
    }
}
