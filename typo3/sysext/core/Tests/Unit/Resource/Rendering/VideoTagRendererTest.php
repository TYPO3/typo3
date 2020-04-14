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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Rendering\VideoTagRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class VideoTagRendererTest
 */
class VideoTagRendererTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getPriorityReturnsCorrectValue()
    {
        $VideoTagRenderer = new VideoTagRenderer();

        self::assertSame(1, $VideoTagRenderer->getPriority());
    }

    /**
     * @test
     */
    public function canRenderReturnsTrueOnCorrectFile()
    {
        $VideoTagRenderer = new VideoTagRenderer();

        $fileResourceMock1 = $this->createMock(File::class);
        $fileResourceMock1->expects(self::any())->method('getMimeType')->willReturn('video/mp4');
        $fileResourceMock2 = $this->createMock(File::class);
        $fileResourceMock2->expects(self::any())->method('getMimeType')->willReturn('video/webm');
        $fileResourceMock3 = $this->createMock(File::class);
        $fileResourceMock3->expects(self::any())->method('getMimeType')->willReturn('video/ogg');
        $fileResourceMock4 = $this->createMock(File::class);
        $fileResourceMock4->expects(self::any())->method('getMimeType')->willReturn('application/ogg');

        self::assertTrue($VideoTagRenderer->canRender($fileResourceMock1));
        self::assertTrue($VideoTagRenderer->canRender($fileResourceMock2));
        self::assertTrue($VideoTagRenderer->canRender($fileResourceMock3));
        self::assertTrue($VideoTagRenderer->canRender($fileResourceMock4));
    }

    /**
     * @test
     */
    public function canRenderReturnsFalseOnCorrectFile()
    {
        $VideoTagRenderer = new VideoTagRenderer();

        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->expects(self::any())->method('getMimeType')->willReturn('audio/mpeg');

        self::assertFalse($VideoTagRenderer->canRender($fileResourceMock));
    }

    /**
     * Array of configurations
     */
    public function renderArgumentsDataProvider()
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
                '<video width="300" height="200" controls autoplay><source src="//:path/myVideoFile" type="video/mp4"></video>',
            ],
            [
                '//:path/myVideoFile',
                ['controls' => 0, 'autoplay' => 1],
                '<video width="300" height="200" autoplay><source src="//:path/myVideoFile" type="video/mp4"></video>',
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
                        'custom-id' => 'video-123'
                    ],
                    'additionalAttributes' => [
                        'muted' => 'muted',
                        'foo' => 'bar'
                    ],
                    'additionalConfig' => [
                        'playsinline' => '1',
                        'controls' => '1'
                    ]
                ],
                '<video muted="muted" foo="bar" data-js-required="yes" data-custom-id="video-123" width="300" height="200" controls playsinline><source src="//:path/myVideoFile" type="video/mp4"></video>',
            ],
            [
                '//:path/myVideoFile',
                ['preload' => 'auto'],
                '<video width="300" height="200" controls preload="auto"><source src="//:path/myVideoFile" type="video/mp4"></video>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderArgumentsDataProvider
     * @param string $url
     * @param array $arguments
     * @param string $expected
     */
    public function renderOutputIsCorrect($url, $arguments, $expected)
    {
        $VideoTagRenderer = new VideoTagRenderer();

        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->expects(self::any())->method('getMimeType')->willReturn('video/mp4');
        $fileResourceMock->expects(self::any())->method('getPublicUrl')->willReturn($url);

        self::assertSame(
            $expected,
            $VideoTagRenderer->render($fileResourceMock, '300m', '200', $arguments)
        );
    }
}
