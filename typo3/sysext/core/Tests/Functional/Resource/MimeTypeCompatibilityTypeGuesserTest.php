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

namespace TYPO3\CMS\Core\Tests\Functional\Resource;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\MimeTypeCompatibilityTypeGuesser;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MimeTypeCompatibilityTypeGuesserTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function containerMimeTypeDataProvider(): \Generator
    {
        yield 'Word document detected as ZIP archive' => ['application/zip', 'report.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        yield 'Excel workbook detected as ZIP archive' => ['application/zip', 'report.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        yield 'PowerPoint presentation detected as ZIP archive' => ['application/zip', 'report.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'];
        yield 'PowerPoint slide detected as ZIP archive' => ['application/zip', 'report.sldx', 'application/vnd.openxmlformats-officedocument.presentationml.slide'];
        yield 'macro-enabled Word document detected as ZIP archive' => ['application/zip', 'report.docm', 'application/vnd.ms-word.document.macroenabled.12'];
        yield 'macro-enabled Excel workbook detected as ZIP archive' => ['application/zip', 'report.xlsm', 'application/vnd.ms-excel.sheet.macroenabled.12'];
        yield 'binary Excel workbook detected as ZIP archive' => ['application/zip', 'report.xlsb', 'application/vnd.ms-excel.sheet.binary.macroenabled.12'];
        yield 'macro-enabled PowerPoint presentation detected as ZIP archive' => ['application/zip', 'report.pptm', 'application/vnd.ms-powerpoint.presentation.macroenabled.12'];
        yield 'OpenDocument text detected as ZIP archive' => ['application/zip', 'report.odt', 'application/vnd.oasis.opendocument.text'];
        yield 'OpenDocument drawing stays a ZIP archive' => ['application/zip', 'report.odg', 'application/zip'];
        yield 'OpenOffice.org text stays a ZIP archive' => ['application/zip', 'report.sxw', 'application/zip'];
        yield 'ZIP archive stays a ZIP archive' => ['application/zip', 'report.zip', 'application/zip'];
        yield 'legacy Word document detected as OLE storage' => ['application/x-ole-storage', 'report.doc', 'application/msword'];
        yield 'legacy Excel workbook detected as CDFV2' => ['application/CDFV2', 'report.xls', 'application/vnd.ms-excel'];
        yield 'legacy PowerPoint presentation detected as generic Office type' => ['application/vnd.ms-office', 'report.ppt', 'application/vnd.ms-powerpoint'];
    }

    #[DataProvider('containerMimeTypeDataProvider')]
    #[Test]
    public function containerMimeTypeIsMappedByFileExtension(string $detectedMimeType, string $fileName, string $expectedMimeType): void
    {
        $parameters = ['mimeType' => $detectedMimeType, 'targetFileName' => $fileName];

        new MimeTypeCompatibilityTypeGuesser()->guessMimeType($parameters, new \SplFileInfo($fileName));

        self::assertSame($expectedMimeType, $parameters['mimeType']);
    }
}
