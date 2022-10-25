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

namespace TYPO3\CMS\Install\Tests\Functional\Service;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Install\Service\EnableFileService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class EnableFileServiceTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public function tearDown(): void
    {
        $publicPath = Environment::getPublicPath();
        @rmdir($publicPath . '/FIRST_INSTALL2Folder');
        @unlink($publicPath . '/FIRST_INSTALL');
        @unlink($publicPath . '/FIRST_INStall');
        @unlink($publicPath . '/FIRST_INSTALL.txt');
        @unlink($publicPath . '/foo');
        @unlink($publicPath . '/bar');
        @unlink($publicPath . '/ddd.txt');
        @unlink($publicPath . '/somethingelse');
        @unlink($publicPath . '/dadadaFIRST_INStall');
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getFirstInstallFilePathsFindsValidFiles(): void
    {
        $publicPath = Environment::getPublicPath();
        mkdir($publicPath . '/FIRST_INSTALL2Folder');
        file_put_contents($publicPath . '/FIRST_INSTALL', '');
        file_put_contents($publicPath . '/FIRST_INStall', '');
        file_put_contents($publicPath . '/FIRST_INSTALL.txt', 'with content');
        file_put_contents($publicPath . '/somethingelse', '');
        file_put_contents($publicPath . '/dadadaFIRST_INStall', '');
        $expected = [
            'FIRST_INSTALL',
            'FIRST_INStall',
            'FIRST_INSTALL.txt',
        ];
        $subject = $this->getAccessibleMock(EnableFileService::class, null);
        self::assertEquals([], array_diff($expected, $subject->_call('getFirstInstallFilePaths')));
    }

    /**
     * @test
     */
    public function getFirstInstallFilePathsReturnsEmptyArrayWithOnlyInvalidFiles(): void
    {
        $publicPath = Environment::getPublicPath();
        mkdir($publicPath . '/FIRST_INSTALL2Folder');
        file_put_contents($publicPath . '/foo', '');
        file_put_contents($publicPath . '/bar', '');
        file_put_contents($publicPath . '/ddd.txt', 'with content');
        file_put_contents($publicPath . '/somethingelse', '');
        file_put_contents($publicPath . '/dadadaFIRST_INStall', '');
        $subject = $this->getAccessibleMock(EnableFileService::class, null);
        self::assertEquals([], array_diff([], $subject->_call('getFirstInstallFilePaths')));
    }

    /**
     * @test
     */
    public function removeFirstInstallFileRemovesValidFiles(): void
    {
        $publicPath = Environment::getPublicPath();
        mkdir($publicPath . '/FIRST_INSTALL2Folder');
        file_put_contents($publicPath . '/FIRST_INSTALL', '');
        file_put_contents($publicPath . '/FIRST_INStall', '');
        file_put_contents($publicPath . '/FIRST_INSTALL.txt', 'with content');
        file_put_contents($publicPath . '/somethingelse', '');
        file_put_contents($publicPath . '/dadadaFIRST_INStall', '');
        $expected = scandir($publicPath);
        unset($expected[2], $expected[3], $expected[5]);
        $subject = $this->getAccessibleMock(EnableFileService::class, null);
        $subject->_call('removeFirstInstallFile');
        self::assertEquals(array_values($expected), array_values(scandir($publicPath)));
    }

    /**
     * @test
     */
    public function removeFirstInstallFileRemovesNoFileIfThereAreNoValidFiles(): void
    {
        $publicPath = Environment::getPublicPath();
        mkdir($publicPath . '/FIRST_INSTALL2Folder');
        file_put_contents($publicPath . '/foo', '');
        file_put_contents($publicPath . '/bar', '');
        file_put_contents($publicPath . '/ddd.txt', 'with content');
        file_put_contents($publicPath . '/somethingelse', '');
        file_put_contents($publicPath . '/dadadaFIRST_INStall', '');
        $expected = scandir($publicPath);
        $subject = $this->getAccessibleMock(EnableFileService::class, null);
        $subject->_call('removeFirstInstallFile');
        self::assertEquals(array_values($expected), array_values(scandir($publicPath)));
    }
}
