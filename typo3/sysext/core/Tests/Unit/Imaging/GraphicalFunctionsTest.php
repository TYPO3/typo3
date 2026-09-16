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

namespace TYPO3\CMS\Core\Tests\Unit\Imaging;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class GraphicalFunctionsTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function resizeWritesToATemporaryFileAndRenamesItIntoPlace(): void
    {
        $sourceFile = GeneralUtility::tempnam('graphical-functions-resize-test-source-') . '.png';
        $gdImage = imagecreatetruecolor(10, 10);
        imagepng($gdImage, $sourceFile);

        $subject = $this->getMockBuilder(GraphicalFunctions::class)
            ->onlyMethods(['imageMagickExec'])
            ->getMock();

        // Stands in for the real ImageMagick/GraphicsMagick binary: whatever path resize()
        // asks it to write to, it creates that exact file. If resize() ever passes its own
        // public, final output path here directly (instead of a private temporary one),
        // that path becomes readable - "exists" - before this callback returns, exactly
        // the window a concurrent request could observe a still-incomplete file in.
        $capturedOutputArgument = null;
        $subject->expects($this->once())->method('imageMagickExec')->willReturnCallback(
            function (string $input, string $output, string $params, int $frame = 0) use (&$capturedOutputArgument, $sourceFile): string {
                $capturedOutputArgument = $output;
                copy($sourceFile, $output);
                return '';
            }
        );

        $result = $subject->resize($sourceFile, 'png', '5', '5');

        self::assertNotNull($result);
        self::assertFileExists($result->getRealPath());
        self::assertNotSame(
            $capturedOutputArgument,
            $result->getRealPath(),
            'imageMagickExec() must be asked to write to a temporary path, not directly to the public, final output path.'
        );
        self::assertFileDoesNotExist(
            $capturedOutputArgument,
            'the temporary file must have been renamed into place, not left behind next to it.'
        );

        unlink($sourceFile);
        unlink($result->getRealPath());
    }

    #[Test]
    public function temporaryOutputPathEndsWithTheSameExtensionAsTheFinalOutput(): void
    {
        $temporary = $this->invokeTemporaryOutputPath('/typo3temp/assets/images/abc123.png');

        self::assertNotSame('/typo3temp/assets/images/abc123.png', $temporary);
        self::assertStringStartsWith('/typo3temp/assets/images/abc123.', $temporary);
        self::assertStringEndsWith('.tmp.png', $temporary);
        // exactly one occurrence of the extension - not doubled by naively appending it
        self::assertSame(1, substr_count($temporary, '.png'));
    }

    #[Test]
    public function temporaryOutputPathIsUniquePerCall(): void
    {
        $first = $this->invokeTemporaryOutputPath('/typo3temp/assets/images/abc123.png');
        $second = $this->invokeTemporaryOutputPath('/typo3temp/assets/images/abc123.png');

        self::assertNotSame($first, $second);
    }

    private function invokeTemporaryOutputPath(string $output): string
    {
        $subject = new \ReflectionClass(GraphicalFunctions::class)->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(GraphicalFunctions::class, 'temporaryOutputPath');
        return $method->invoke($subject, $output);
    }

    #[Test]
    public function imageMagickIdentifyReturnsFormattedValues(): void
    {
        $file = 'myImageFile.png';
        $expected = [
            '123',
            '234',
            'png',
            'myImageFile.png',
            'png',
        ];

        $subject = $this->getAccessibleMock(GraphicalFunctions::class, ['executeIdentifyCommandForImageFile'], [], '', false);
        $subject->_set('processorEnabled', true);
        $subject->expects($this->once())->method('executeIdentifyCommandForImageFile')->with($file)->willReturn('123 234 png PNG');
        $result = $subject->imageMagickIdentify($file);
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function imageMagickIdentifyReturnsFormattedValuesWithOffset(): void
    {
        $file = 'myImageFile.png';
        $expected = [
            '200+0+0',
            '400+0+0',
            'png',
            'myImageFile.png',
            'png',
        ];

        $subject = $this->getAccessibleMock(GraphicalFunctions::class, ['executeIdentifyCommandForImageFile'], [], '', false);
        $subject->_set('processorEnabled', true);
        $subject->expects($this->once())->method('executeIdentifyCommandForImageFile')->with($file)->willReturn('200+0+0 400+0+0 png PNG');
        $result = $subject->imageMagickIdentify($file);
        self::assertEquals($result, $expected);
    }
}
