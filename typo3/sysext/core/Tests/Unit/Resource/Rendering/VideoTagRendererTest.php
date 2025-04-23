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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Rendering;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Rendering\VideoTagRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class VideoTagRendererTest extends UnitTestCase
{
    #[Test]
    public function getPriorityReturnsCorrectValue(): void
    {
        $VideoTagRenderer = new VideoTagRenderer();

        self::assertSame(1, $VideoTagRenderer->getPriority());
    }

    #[Test]
    public function canRenderReturnsTrueOnCorrectFile(): void
    {
        $VideoTagRenderer = new VideoTagRenderer();

        $fileResourceMock1 = $this->createMock(File::class);
        $fileResourceMock1->method('getMimeType')->willReturn('video/mp4');
        $fileResourceMock2 = $this->createMock(File::class);
        $fileResourceMock2->method('getMimeType')->willReturn('video/webm');
        $fileResourceMock3 = $this->createMock(File::class);
        $fileResourceMock3->method('getMimeType')->willReturn('video/ogg');
        $fileResourceMock4 = $this->createMock(File::class);
        $fileResourceMock4->method('getMimeType')->willReturn('application/ogg');

        self::assertTrue($VideoTagRenderer->canRender($fileResourceMock1));
        self::assertTrue($VideoTagRenderer->canRender($fileResourceMock2));
        self::assertTrue($VideoTagRenderer->canRender($fileResourceMock3));
        self::assertTrue($VideoTagRenderer->canRender($fileResourceMock4));
    }

    #[Test]
    public function canRenderReturnsFalseOnCorrectFile(): void
    {
        $VideoTagRenderer = new VideoTagRenderer();

        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->method('getMimeType')->willReturn('audio/mpeg');

        self::assertFalse($VideoTagRenderer->canRender($fileResourceMock));
    }

    /**
     * Array of configurations
     */
    public static function renderArgumentsDataProvider(): array
    {
        return [
            [
                '//:path/myVideoFile?foo=bar&baz=true',
                [],
                '<video width="300" height="200" controls><source src="//:path/myVideoFile?foo=bar&amp;baz=true" type="video/mp4"></video>',
            ],
            [
                '//:path/myVideoFile',
                ['loop' => 1],
                '<video width="300" height="200" controls loop><source src="//:path/myVideoFile" type="video/mp4"></video>',
            ],
            [
                '//:path/myVideoFile',
                ['autoplay' => 1],
                '<video width="300" height="200" controls autoplay muted><source src="//:path/myVideoFile" type="video/mp4"></video>',
            ],
            [
                '//:path/myVideoFile',
                ['controls' => 0, 'autoplay' => 1],
                '<video width="300" height="200" autoplay muted><source src="//:path/myVideoFile" type="video/mp4"></video>',
            ],
            [
                '//:path/myVideoFile',
                ['controls' => 1, 'controlsList' => 'nodownload'],
                '<video width="300" height="200" controls controlsList="nodownload"><source src="//:path/myVideoFile" type="video/mp4"></video>',
            ],
            [
                '//:path/myVideoFile',
                ['additionalAttributes' => ['muted' => 'muted', 'foo' => 'bar']],
                '<video muted="muted" foo="bar" width="300" height="200" controls><source src="//:path/myVideoFile" type="video/mp4"></video>',
            ],
            [
                '//:path/myVideoFile',
                ['data' => ['js-required' => 'yes', 'custom-id' => 'video-123']],
                '<video data-js-required="yes" data-custom-id="video-123" width="300" height="200" controls><source src="//:path/myVideoFile" type="video/mp4"></video>',
            ],
            [
                '//:path/myVideoFile',
                [
                    'data' => [
                        'js-required' => 'yes',
                        'custom-id' => 'video-123',
                    ],
                    'additionalAttributes' => [
                        'muted' => 'muted',
                        'foo' => 'bar',
                    ],
                    'additionalConfig' => [
                        'playsinline' => '1',
                        'controls' => '1',
                    ],
                ],
                '<video muted="muted" foo="bar" data-js-required="yes" data-custom-id="video-123" width="300" height="200" controls playsinline><source src="//:path/myVideoFile" type="video/mp4"></video>',
            ],
            [
                '//:path/myVideoFile',
                ['preload' => 'auto'],
                '<video width="300" height="200" controls preload="auto"><source src="//:path/myVideoFile" type="video/mp4"></video>',
            ],
            [
                '//:path/myVideoFile',
                [
                    'data' => [
                        'js-required' => 'yes',
                        'custom-id' => 'video-123',
                    ],
                    'additionalAttributes' => [
                        'muted' => 'muted',
                        'foo' => 'bar',
                    ],
                    'controlsList' => 'nodownload',
                ],
                '<video muted="muted" foo="bar" data-js-required="yes" data-custom-id="video-123" width="300" height="200" controls controlsList="nodownload"><source src="//:path/myVideoFile" type="video/mp4"></video>',
            ],
            [
                '//:path/myVideoFile',
                [
                    'additionalConfig' => [
                        'playsinline' => '1',
                        'controls' => '1',
                        'api' => '1',
                        'no-cookie' => '1',
                    ],
                ],
                '<video width="300" height="200" controls playsinline><source src="//:path/myVideoFile" type="video/mp4"></video>',
            ],
        ];
    }

    #[DataProvider('renderArgumentsDataProvider')]
    #[Test]
    public function renderOutputIsCorrect(string $url, array $arguments, string $expected): void
    {
        $VideoTagRenderer = new VideoTagRenderer();

        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->method('getMimeType')->willReturn('video/mp4');
        $fileResourceMock->method('getPublicUrl')->willReturn($url);

        self::assertSame(
            $expected,
            $VideoTagRenderer->render($fileResourceMock, '300m', '200', $arguments)
        );
    }
}
