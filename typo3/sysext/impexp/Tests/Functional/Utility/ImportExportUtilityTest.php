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

namespace TYPO3\CMS\Impexp\Tests\Functional\Utility;

use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;
use TYPO3\CMS\Impexp\Utility\ImportExportUtility;

class ImportExportUtilityTest extends AbstractImportExportTestCase
{
    use ProphecyTrait;

    public function importFailsDataProvider(): array
    {
        return [
            'path to not existing file' => [
                'EXT:impexp/Tests/Functional/Fixtures/XmlImports/me_does_not_exist.xml',
            ],
            'unsupported file extension' => [
                'EXT:impexp/Tests/Functional/Fixtures/XmlImports/unsupported.json',
            ],
            'missing required extension' => [
                'EXT:impexp/Tests/Functional/Fixtures/XmlImports/sys_category_table_with_news.xml',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider importFailsDataProvider
     */
    public function importFails(string $filePath): void
    {
        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('No page records imported');

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $importUtilityMock = $this->getAccessibleMock(
            ImportExportUtility::class,
            ['dummy'],
            ['eventDispatcher' => $eventDispatcher->reveal()]
        );
        $importUtilityMock->importT3DFile($filePath, 0);
    }

    /**
     * @test
     */
    public function importSucceeds(): void
    {
        $filePath = 'EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent.xml';

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $importUtilityMock = $this->getAccessibleMock(
            ImportExportUtility::class,
            ['dummy'],
            ['eventDispatcher' => $eventDispatcher->reveal()]
        );
        $result = $importUtilityMock->importT3DFile($filePath, 0);

        self::assertEquals(1, $result);
    }
}
