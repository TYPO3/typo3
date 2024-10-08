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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;
use TYPO3\CMS\Impexp\Utility\ImportExportUtility;

final class ImportExportUtilityTest extends AbstractImportExportTestCase
{
    public static function importFailsDataProvider(): array
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

    #[DataProvider('importFailsDataProvider')]
    #[Test]
    public function importFails(string $filePath): void
    {
        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('No page records imported');
        $subject = new ImportExportUtility(new NoopEventDispatcher());
        $subject->importT3DFile($filePath, 0);
    }

    #[Test]
    public function importSucceeds(): void
    {
        $filePath = 'EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent.xml';
        $subject = new ImportExportUtility(new NoopEventDispatcher());
        $result = $subject->importT3DFile($filePath, 0);
        self::assertEquals(1, $result);
    }
}
