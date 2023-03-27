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

namespace TYPO3\CMS\Core\Tests\Functional\Imaging;

use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Imaging\ImageMagickFile;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ImageMagickFileTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function framesAreConsideredDataProvider(): array
    {
        return [
            'file.pdf'    => ['file.pdf', null, '\'pdf:{directory}/file.pdf\''],
            'file.pdf[0]' => ['file.pdf', 0, '\'pdf:{directory}/file.pdf[0]\''],
        ];
    }

    /**
     * @test
     * @dataProvider framesAreConsideredDataProvider
     */
    public function framesAreConsidered(string $fileName, ?int $frame, string $expectation): void
    {
        $expectation = $this->substituteVariables($expectation);
        $filePath = sprintf('%s/%s', __DIR__ . '/Fixtures', $fileName);
        $file = ImageMagickFile::fromFilePath($filePath, $frame);
        self::assertSame($expectation, (string)$file);
    }

    public static function resultIsEscapedDataProvider(): array
    {
        // probably Windows system
        if (DIRECTORY_SEPARATOR === '\\') {
            return [
                'without frame'    => ['file.pdf', null, '"pdf:{directory}/file.pdf"'],
                'with first frame' => ['file.pdf', 0, '"pdf:{directory}/file.pdf[0]"'],
                'special literals' => ['\'`%$!".png', 0, '"png:{directory}/\'` $  .png[0]"'],
            ];
        }
        // probably Unix system
        return [
            'without frame'    => ['file.pdf', null, '\'pdf:{directory}/file.pdf\''],
            'with first frame' => ['file.pdf', 0, '\'pdf:{directory}/file.pdf[0]\''],
            'special literals' => ['\'`%$!".png', 0, '\'png:{directory}/\'\\\'\'`%$!".png[0]\''],
        ];
    }

    /**
     * @test
     * @dataProvider resultIsEscapedDataProvider
     */
    public function resultIsEscaped(string $fileName, ?int $frame, string $expectation): void
    {
        $expectation = $this->substituteVariables($expectation);
        $filePath = sprintf('%s/%s', __DIR__ . '/Fixtures', $fileName);
        $file = ImageMagickFile::fromFilePath($filePath, $frame);
        self::assertSame($expectation, (string)$file);
    }

    public static function fileStatementIsResolvedDataProvider(): array
    {
        return [
            'file.ai'       => ['file.ai', '\'pdf:{directory}/file.ai\''],
            'file.ai.jpg'   => ['file.ai.jpg', '\'pdf:{directory}/file.ai.jpg\''],
            'file.gif'      => ['file.gif', '\'gif:{directory}/file.gif\''],
            'file.gif.jpg'  => ['file.gif.jpg', '\'gif:{directory}/file.gif.jpg\''],
            'file.jpg'      => ['file.jpg', '\'jpg:{directory}/file.jpg\''],
            'file.jpg.png'  => ['file.jpg.png', '\'jpg:{directory}/file.jpg.png\''],
            'file.png'      => ['file.png', '\'png:{directory}/file.png\''],
            'file.png.jpg'  => ['file.png.jpg', '\'png:{directory}/file.png.jpg\''],
            'file.svg'      => ['file.svg', '\'svg:{directory}/file.svg\''],
            'file.svg.jpg'  => ['file.svg.jpg', '\'svg:{directory}/file.svg.jpg\''],
            'file.tif'      => ['file.tif', '\'tif:{directory}/file.tif\''],
            'file.tif.jpg'  => ['file.tif.jpg', '\'tif:{directory}/file.tif.jpg\''],
            'file.webp'     => ['file.webp', '\'webp:{directory}/file.webp\''],
            'file.webp.jpg' => ['file.webp.jpg', '\'webp:{directory}/file.webp.jpg\''],
            'file.pdf'      => ['file.pdf', '\'pdf:{directory}/file.pdf\''],
            'file.pdf.jpg'  => ['file.pdf.jpg', '\'pdf:{directory}/file.pdf.jpg\''],
            // accepted, since postscript files are converted using 'jpg:' format
            'file.ps.jpg'   => ['file.ps.jpg', '\'jpg:{directory}/file.ps.jpg\''],
            'file.eps.jpg'  => ['file.eps.jpg', '\'jpg:{directory}/file.eps.jpg\''],
        ];
    }

    /**
     * @test
     * @dataProvider fileStatementIsResolvedDataProvider
     */
    public function fileStatementIsResolved(string $fileName, string $expectation): void
    {
        $expectation = $this->substituteVariables($expectation);
        $filePath = sprintf('%s/%s', __DIR__ . '/Fixtures', $fileName);
        $file = ImageMagickFile::fromFilePath($filePath);
        self::assertSame($expectation, (string)$file);
    }

    /**
     * In case mime-types cannot be resolved (or cannot be verified), allowed extensions
     * are used as conversion format (e.g. 'file.ai.jpg' -> 'jpg:...').
     */
    public static function fileStatementIsResolvedForEnforcedMimeTypeDataProvider(): array
    {
        return [
            'file.ai.jpg'   => ['file.ai.jpg', '\'jpg:{directory}/file.ai.jpg\'', 'inode/x-empty'],
            'file.bmp.jpg'  => ['file.bmp.jpg', '\'jpg:{directory}/file.bmp.jpg\'', 'inode/x-empty'],
            'file.fax.jpg'  => ['file.fax.jpg', '\'jpg:{directory}/file.fax.jpg\'', 'inode/x-empty'],
            'file.gif.jpg'  => ['file.gif.jpg', '\'jpg:{directory}/file.gif.jpg\'', 'inode/x-empty'],
            'file.jpg'      => ['file.jpg', '\'jpg:{directory}/file.jpg\'', 'inode/x-empty'],
            'file.jpg.png'  => ['file.jpg.png', '\'png:{directory}/file.jpg.png\'', 'inode/x-empty'],
            'file.png'      => ['file.png', '\'png:{directory}/file.png\'', 'inode/x-empty'],
            'file.png.jpg'  => ['file.png.jpg', '\'jpg:{directory}/file.png.jpg\'', 'inode/x-empty'],
            'file.svg.jpg'  => ['file.svg.jpg', '\'jpg:{directory}/file.svg.jpg\'', 'inode/x-empty'],
            'file.tif'      => ['file.tif', '\'tif:{directory}/file.tif\'', 'inode/x-empty'],
            'file.tif.jpg'  => ['file.tif.jpg', '\'jpg:{directory}/file.tif.jpg\'', 'inode/x-empty'],
            'file.webp'     => ['file.webp', '\'webp:{directory}/file.webp\'', 'inode/x-empty'],
            'file.webp.jpg' => ['file.webp.jpg', '\'jpg:{directory}/file.webp.jpg\'', 'inode/x-empty'],
            'file.pdf.jpg'  => ['file.pdf.jpg', '\'jpg:{directory}/file.pdf.jpg\'', 'inode/x-empty'],
        ];
    }

    /**
     * @test
     * @dataProvider fileStatementIsResolvedForEnforcedMimeTypeDataProvider
     */
    public function fileStatementIsResolvedForEnforcedMimeType(string $fileName, string $expectation, string $mimeType): void
    {
        $this->simulateNextFileInfoInvocation($mimeType);
        $expectation = $this->substituteVariables($expectation);
        $filePath = sprintf('%s/%s', __DIR__ . '/Fixtures', $fileName);
        $file = ImageMagickFile::fromFilePath($filePath, null);
        self::assertSame($expectation, (string)$file);
    }

    public static function fileStatementIsResolvedForConfiguredMimeTypeDataProvider(): array
    {
        return [
            'file.fax'      => ['file.fax', '\'g3:{directory}/file.fax\''],
            'file.bmp'      => ['file.bmp', '\'dib:{directory}/file.bmp\''],
        ];
    }

    /**
     * @test
     * @dataProvider fileStatementIsResolvedForConfiguredMimeTypeDataProvider
     */
    public function fileStatementIsResolvedForConfiguredMimeType(string $fileName, string $expectation): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType']['g3'] = 'image/g3fax';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType']['fax'] = 'image/g3fax';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType']['dib'] = 'image/x-ms-bmp';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType']['bmp'] = 'image/x-ms-bmp';

        $expectation = $this->substituteVariables($expectation);
        $filePath = sprintf('%s/%s', __DIR__ . '/Fixtures', $fileName);
        $file = ImageMagickFile::fromFilePath($filePath, null);
        self::assertSame($expectation, (string)$file);
    }

    public static function fileStatementIsDeniedDataProvider(): array
    {
        return [
            'file.ps'     => ['file.ps'],
            'file.eps'    => ['file.eps'],
            // denied since not defined in allowed extensions
            'file.ai'     => ['file.ai', 'inode/x-empty'],
            'file.svg'    => ['file.svg', 'inode/x-empty'],
            'file.pdf'    => ['file.pdf', 'inode/x-empty'],
        ];
    }

    /**
     * @test
     * @dataProvider fileStatementIsDeniedDataProvider
     */
    public function fileStatementIsDenied(string $fileName, ?string $mimeType = null): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1550060977);

        if ($mimeType !== null) {
            $this->simulateNextFileInfoInvocation($mimeType);
        }

        $filePath = sprintf('%s/%s', __DIR__ . '/Fixtures', $fileName);
        ImageMagickFile::fromFilePath($filePath);
    }

    public static function fileStatementIsDeniedForConfiguredMimeTypeDataProvider(): array
    {
        return [
            'file.ps'     => ['file.ps'],
            'file.eps'    => ['file.eps'],
        ];
    }

    /**
     * @test
     * @dataProvider fileStatementIsDeniedForConfiguredMimeTypeDataProvider
     */
    public function fileStatementIsDeniedForConfiguredMimeType(string $fileName): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType']['ps'] = 'image/x-see-no-evil';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType']['eps'] = 'image/x-see-no-evil';

        $this->expectException(Exception::class);
        $this->expectExceptionCode(1550060977);

        $filePath = sprintf('%s/%s', __DIR__ . '/Fixtures', $fileName);
        ImageMagickFile::fromFilePath($filePath, null);
    }

    private function substituteVariables(string $value): string
    {
        return str_replace(
            ['{directory}'],
            [__DIR__ . '/Fixtures'],
            $value
        );
    }

    private function simulateNextFileInfoInvocation(string $mimeType): void
    {
        $fileInfo = $this->getAccessibleMock(
            FileInfo::class,
            ['getMimeType', 'getMimeExtensions'],
            [],
            '',
            false
        );
        $fileInfo->expects(self::atLeastOnce())->method('getMimeType')->willReturn($mimeType);
        $fileInfo->expects(self::atLeastOnce())->method('getMimeExtensions')->willReturn([]);
        GeneralUtility::addInstance(FileInfo::class, $fileInfo);
    }
}
