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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Filelist\FileList;
use TYPO3\CMS\Filelist\Type\SortDirection;
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
        $folder1WithSuffix1 = new Folder($storageMock, '/folder1_abc', 'folder1_abc');
        $folder1WithSuffix2 = new Folder($storageMock, '/folder1_def', 'folder1_def');

        $resources = [$file2, $folder1, $folder2, $file1, $folder1WithSuffix2, $folder1WithSuffix1, $file3, $file4];
        $expected = [$folder1, $folder1WithSuffix1, $folder1WithSuffix2, $folder2, $file1, $file2, $file3, $file4];

        $fileList = $this->getAccessibleMock(FileList::class, ['getLanguageService'], [], '', false);
        $fileList->sortDirection = SortDirection::ASCENDING;
        $fileList->method('getLanguageService')->willReturn($languageServiceMock);

        $sortedResources = $fileList->_call('sortResources', $resources, 'name');

        $expectedNames = array_map(
            static fn(File|Folder $item): string => $item->getName(),
            $expected
        );
        $actualNames = array_values(array_map(
            static fn(File|Folder $item): string => $item->getName(),
            $sortedResources
        ));
        self::assertSame($expectedNames, $actualNames);
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

        $sortedResources = $fileList->_call('sortResources', $resources, 'fileext');

        $expectedNames = array_map(
            static fn(File|Folder $item): string => $item->getName(),
            $expected
        );
        $actualNames = array_values(array_map(
            static fn(File|Folder $item): string => $item->getName(),
            $sortedResources
        ));
        self::assertSame($expectedNames, $actualNames);
    }

    public static function sortResourcesDataProvider(): iterable
    {
        yield 'descending order sorts files first then folders in reverse' => [
            'resourceNames' => [
                'file' => ['file1.jpg', 'fileA.png', 'fileB.aif'],
                'folder' => ['folder1', 'folder2', 'folder1_abc', 'folder1_def'],
            ],
            'sortField' => 'name',
            'sortDirection' => SortDirection::DESCENDING,
            'expectedOrder' => ['fileB.aif', 'fileA.png', 'file1.jpg', 'folder2', 'folder1_def', 'folder1_abc', 'folder1'],
        ];
        yield 'stable sort preserves original order for equal values' => [
            'resourceNames' => [
                'file' => ['alpha.png', 'beta.png', 'gamma.png', 'delta.jpg'],
                'folder' => [],
            ],
            'sortField' => 'fileext',
            'sortDirection' => SortDirection::ASCENDING,
            // jpg comes before png, then png files maintain original order
            'expectedOrder' => ['delta.jpg', 'alpha.png', 'beta.png', 'gamma.png'],
        ];
        yield 'folders with similar prefixes and numeric suffixes sort correctly' => [
            'resourceNames' => [
                'file' => [],
                'folder' => ['folder10', 'folder1_abc', 'folder2', 'folder1', 'folder11'],
            ],
            'sortField' => 'name',
            'sortDirection' => SortDirection::ASCENDING,
            // numeric collation orders numbers correctly, underscore after numbers
            'expectedOrder' => ['folder1', 'folder1_abc', 'folder2', 'folder10', 'folder11'],
        ];
    }

    #[DataProvider('sortResourcesDataProvider')]
    #[Test]
    public function sortResourcesSortsCorrectly(
        array $resourceNames,
        string $sortField,
        SortDirection $sortDirection,
        array $expectedOrder
    ): void {
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('getLocale')->willReturn(new Locale('en'));

        $storageMock = $this->createMock(ResourceStorage::class);

        $resources = [];
        foreach ($resourceNames['file'] as $fileName) {
            $resources[] = new File(['name' => $fileName], $storageMock);
        }
        foreach ($resourceNames['folder'] as $folderName) {
            $resources[] = new Folder($storageMock, '/' . $folderName, $folderName);
        }

        $fileList = $this->getAccessibleMock(FileList::class, ['getLanguageService'], [], '', false);
        $fileList->sortDirection = $sortDirection;
        $fileList->method('getLanguageService')->willReturn($languageServiceMock);

        $sortedResources = $fileList->_call('sortResources', $resources, $sortField);

        $actualNames = array_values(array_map(
            static fn(File|Folder $item): string => $item->getName(),
            $sortedResources
        ));
        self::assertSame($expectedOrder, $actualNames);
    }
}
