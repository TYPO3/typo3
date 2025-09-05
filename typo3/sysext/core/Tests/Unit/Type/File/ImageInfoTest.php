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

namespace TYPO3\CMS\Core\Tests\Unit\Type\File;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ImageInfoTest extends UnitTestCase
{
    #[Test]
    public function doesNotBreakOnFileWithInvalidEnding(): void
    {
        $testFile = __DIR__ . '/../Fixture/html_file_with_pdf_ending.pdf';

        $exceptionIsLogged = function (array $context) {
            self::assertEquals(
                'Unsupported file html_file_with_pdf_ending.pdf (text/html)',
                $context['exception']->getMessage()
            );
            return true;
        };

        $loggerMock = $this->createMock(Logger::class);
        $loggerMock->expects($this->once())->method('error')->with(self::isString(), self::callback($exceptionIsLogged));
        $loggerMock->expects($this->once())->method('warning')
            ->with('I could not retrieve the image size for file {file}', ['file' => $testFile]);

        $subject = new ImageInfo($testFile);
        $subject->setLogger($loggerMock);
        self::assertEquals(0, $subject->getHeight());
        self::assertEquals(0, $subject->getWidth());
    }

    public static function doesNotBreakOnImageInfoWithInvalidSvgDataProvider(): array
    {
        return [
            ['Invalid XML.', 0, 0],
            [
                '<?xml version="1.0" encoding="utf-8"?>
                <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.0//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd" [
                    <!ENTITY ns_a "http://ns.typo3.com/test/a/1.0/">
                    <!ENTITY ns_b "http://ns.typo3.com/test/b/1.0/">
                ]>
                <svg version="1.0"
                    xmlns:x="&ns_a;"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink"
                    xml:space="preserve"
                    x="0px" y="0px" viewBox="0 0 436 177">
                    <metadata>
                        <sfw xmlns="&ns_b;">
                            <slices></slices>
                        </sfw>
                    </metadata>
                </svg>',
                436,
                177,
            ],
        ];
    }

    #[DataProvider('doesNotBreakOnImageInfoWithInvalidSvgDataProvider')]
    #[Test]
    public function doesNotBreakOnImageInfoWithInvalidSvg(string $svg, int $width, int $height): void
    {
        $testDirectory = Environment::getVarPath() . '/ImageTest';
        $this->testFilesToDelete[] = $testDirectory;
        $testFile = $testDirectory . '/test.svg';
        if (!is_dir($testDirectory)) {
            mkdir($testDirectory);
        }
        touch($testFile);
        file_put_contents($testFile, $svg);

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType'] = [
            'svg' => 'image/svg+xml',
            'youtube' => 'video/youtube',
            'vimeo' => 'video/vimeo',
        ];

        $graphicalFunctionsMock = $this->createMock(GraphicalFunctions::class);
        $graphicalFunctionsMock->method('imageMagickIdentify')->with($testFile)->willReturn(null);
        GeneralUtility::addInstance(GraphicalFunctions::class, $graphicalFunctionsMock);

        $imageInfo = new ImageInfo($testFile);
        $imageInfo->setLogger(new NullLogger());

        self::assertSame($width, $imageInfo->getWidth());
        self::assertSame($height, $imageInfo->getHeight());

        GeneralUtility::makeInstance(GraphicalFunctions::class);
    }

    public static function canDetectImageSizesDataProvider(): array
    {
        return [
            'svg' => ['test.svg', 80, 80],
            'jpg' => ['test.jpg', 600, 388],
            'png' => ['test.png', 600, 388],
        ];
    }

    #[DataProvider('canDetectImageSizesDataProvider')]
    #[Test]
    public function canDetectImageSizes(string $file, int $width, int $height): void
    {
        $imageInfo = new ImageInfo(__DIR__ . '/../Fixture/' . $file);
        $imageInfo->setLogger(new NullLogger());

        self::assertSame($width, $imageInfo->getWidth());
        self::assertSame($height, $imageInfo->getHeight());
    }
}
