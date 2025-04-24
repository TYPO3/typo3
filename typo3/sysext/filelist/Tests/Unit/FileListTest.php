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

namespace TYPO3\CMS\Filelist\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Filelist\FileList;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileListTest extends UnitTestCase
{
    #[Test]
    public function sortResourcesByNameSortsCorrectly(): void
    {
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('getLocale')->willReturn(new Locale('pl'));

        $storageMock = $this->createMock(ResourceStorage::class);

        $file1 = new File(['name' => 'file1.jpg'], $storageMock);
        $file2 = new File(['name' => 'fileA.png'], $storageMock);
        // this file has an accented letter Ą, so it should be sorted between fileA and fileB
        $file3 = new File(['name' => 'fileĄ.png'], $storageMock);
        $file4 = new File(['name' => 'fileB.aif'], $storageMock);
        $folder1 = new Folder($storageMock, '/folder1', 'folder1');
        $folder2 = new Folder($storageMock, '/folder2', 'folder2');

        $resources = [$file2, $folder1, $folder2, $file1, $file3, $file4];
        $expected = [$file4, $file3, $file2, $file1, $folder2, $folder1];

        $fileList = $this->getAccessibleMock(FileList::class, ['getLanguageService'], [], '', false);
        $fileList->method('getLanguageService')->willReturn($languageServiceMock);
        self::assertEqualsCanonicalizing($expected, $fileList->_call('sortResources', $resources, 'name'));
    }

    #[Test]
    public function sortResourcesByFileextNameSortsCorrectly(): void
    {
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('getLocale')->willReturn(new Locale('pl'));

        $storageMock = $this->createMock(ResourceStorage::class);

        $file1 = new File(['name' => 'file1.jpg'], $storageMock);
        $file2 = new File(['name' => 'fileA.png'], $storageMock);
        // this file has an accented letter Ą, so it should be sorted between fileA and fileB
        $file3 = new File(['name' => 'fileĄ.png'], $storageMock);
        $file4 = new File(['name' => 'fileB.aif'], $storageMock);
        $folder1 = new Folder($storageMock, '/folder1', 'folder1');
        $folder2 = new Folder($storageMock, '/folder2', 'folder2');

        $resources = [$file2, $folder1, $folder2, $file1, $file3, $file4];
        $expected = [$file3, $file2, $file1, $file4, $folder2, $folder1];

        $fileList = $this->getAccessibleMock(FileList::class, ['getLanguageService'], [], '', false);
        $fileList->method('getLanguageService')->willReturn($languageServiceMock);
        self::assertEqualsCanonicalizing($expected, $fileList->_call('sortResources', $resources, 'fileext'));
    }
}
