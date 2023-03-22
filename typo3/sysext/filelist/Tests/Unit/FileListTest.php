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

use Generator;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Filelist\FileList;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileListTest extends UnitTestCase
{
    /**
     * @dataProvider sortResourcesDataProvider
     * @test
     */
    public function sortDescendingResourcesByGivenField(array $resources, string $sortField, array $expectedResources): void
    {
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('getLocale')->willReturn(new Locale('pl'));

        $fileList = $this->getAccessibleMock(FileList::class, ['getLanguageService'], [], '', false);
        $fileList->method('getLanguageService')->willReturn($languageServiceMock);

        $sortedResources = $fileList->_call('sortResources', $resources, $sortField);
        self::assertSame($expectedResources, $sortedResources);
    }

    public function sortResourcesDataProvider(): Generator
    {
        $file1 = $this->createMock(File::class);
        $file1->method('hasProperty')->with('name')->willReturn(true);
        $file1->method('getProperty')->with('name')->willReturn('file1.jpg');
        $file1->method('getExtension')->willReturn('jpg');

        $file2 = $this->createMock(File::class);
        $file2->method('getProperty')->with('name')->willReturn('fileA.png');
        $file2->method('hasProperty')->with('name')->willReturn(true);
        $file2->method('getExtension')->willReturn('png');

        //this file has an accented letter Ą, so it should be sorted between fileA and fileB
        $file3 = $this->createMock(File::class);
        $file3->method('getProperty')->with('name')->willReturn('fileĄ.png');
        $file3->method('hasProperty')->with('name')->willReturn(true);
        $file3->method('getExtension')->willReturn('png');

        $file4 = $this->createMock(File::class);
        $file4->method('getProperty')->with('name')->willReturn('fileB.aif');
        $file4->method('hasProperty')->with('name')->willReturn(true);
        $file4->method('getExtension')->willReturn('aif');

        $folder1 = $this->createMock(Folder::class);
        $folder1->method('getName')->willReturn('folder1');

        $folder2 = $this->createMock(Folder::class);
        $folder2->method('getName')->willReturn('folder2');

        yield 'sort by name' => [[$file2, $folder1, $folder2, $file1, $file3, $file4], 'name', [$file4, $file3, $file2, $file1, $folder2, $folder1]];
        yield 'sort by extension' => [[$file2, $folder1, $folder2, $file1, $file3, $file4], 'fileext', [$file3, $file2, $file1, $file4, $folder2, $folder1]];
    }
}
