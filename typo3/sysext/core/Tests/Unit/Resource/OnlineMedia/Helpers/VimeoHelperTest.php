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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\OnlineMedia\Helpers;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\VimeoHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class VimeoHelperTest extends UnitTestCase
{
    public static function transformUrlDataProvider(): array
    {
        return [
            [null, null, false],
            ['https://typo3.org/', null, false],
            ['https://vimeo.com/', '7215347324', true],
            ['https://vimeo.com/', '7215347324/hasf8a65sdsa7d', true],
            ['https://vimeo.com/video/', '7215347324', true],
            ['https://vimeo.com/video/', '7215347324/hasf8a65sdsa7d', true],
            ['https://player.vimeo.com/', '7215347324', true],
            ['https://player.vimeo.com/', '7215347324/hasf8a65sdsa7d', true],
            ['https://vimeo.com/event/', '7215347324', true],
        ];
    }

    /**
     * @test
     * @dataProvider transformUrlDataProvider
     */
    public function transformUrlToFileReturnsExpectedResult(?string $url, ?string $videoId, bool $expectsMock): void
    {
        $mockedFolder = $this->createMock(Folder::class);
        $expectedResult = null;
        if ($expectsMock) {
            $expectedResult = $this->createMock(File::class);
        }

        $subject = $this->getAccessibleMock(VimeoHelper::class, ['transformMediaIdToFile'], ['video/vimeo']);
        $subject->method('transformMediaIdToFile')
            ->with($videoId, $mockedFolder, 'video/vimeo')
            ->willReturn($expectedResult);

        $result = $subject->transformUrlToFile($url . $videoId, $mockedFolder);

        self::assertSame($expectedResult, $result);
    }
}
