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

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\YouTubeHelper;
use TYPO3\CMS\Core\Resource\Rendering\YouTubeRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class YouTubeRendererTest
 */
class YouTubeRendererTest extends UnitTestCase
{
    protected YouTubeRenderer&MockObject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_HOST'] = 'test.server.org';

        $youTubeHelper = $this->getAccessibleMock(YouTubeHelper::class, ['getOnlineMediaId'], ['youtube']);
        $youTubeHelper->method('getOnlineMediaId')->willReturn('7331');

        $this->subject = $this->getAccessibleMock(YouTubeRenderer::class, ['getOnlineMediaHelper', 'shouldIncludeFrameBorderAttribute']);
        $this->subject->method('shouldIncludeFrameBorderAttribute')->willReturn(false);
        $this->subject->method('getOnlineMediaHelper')->willReturn($youTubeHelper);
    }

    /**
     * @test
     */
    public function getPriorityReturnsCorrectValue(): void
    {
        self::assertSame(1, $this->subject->getPriority());
    }

    /**
     * @test
     */
    public function canRenderReturnsTrueOnCorrectFile(): void
    {
        $fileResourceMock1 = $this->createMock(File::class);
        $fileResourceMock1->method('getMimeType')->willReturn('video/youtube');

        $fileResourceMock2 = $this->createMock(File::class);
        $fileResourceMock2->method('getMimeType')->willReturn('video/unknown');
        $fileResourceMock2->method('getExtension')->willReturn('youtube');

        self::assertTrue($this->subject->canRender($fileResourceMock1));
        self::assertTrue($this->subject->canRender($fileResourceMock2));
    }

    /**
     * @test
     */
    public function canRenderReturnsFalseOnCorrectFile(): void
    {
        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->method('getMimeType')->willReturn('video/vimeo');

        self::assertFalse($this->subject->canRender($fileResourceMock));
    }

    /**
     * @test
     */
    public function renderOutputWithLoopIsCorrect(): void
    {
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=1&amp;loop=1&amp;playlist=7331&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['controls' => 1, 'loop' => 1])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithAutoplayIsCorrect(): void
    {
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=1&amp;autoplay=1&amp;mute=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="autoplay; fullscreen"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['controls' => 1, 'autoplay' => 1])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithAutoplayFromFileReferenceIsCorrect(): void
    {
        $fileResourceMock = $this->createMock(File::class);

        $fileReferenceMock = $this->createMock(FileReference::class);
        $fileReferenceMock->method('getProperty')->willReturn(1);
        $fileReferenceMock->method('getOriginalFile')->willReturn($fileResourceMock);

        self::assertSame(
            '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=1&amp;autoplay=1&amp;mute=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="autoplay; fullscreen"></iframe>',
            $this->subject->render($fileReferenceMock, '300m', '200', ['controls' => 1])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithAutoplayAndWithoutControlsIsCorrect(): void
    {
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=0&amp;autoplay=1&amp;mute=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="autoplay; fullscreen"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['controls' => 0, 'autoplay' => 1])
        );
    }

    public static function renderOutputWithControlsDataProvider(): array
    {
        return [
            'no options given, visible player controls (default)' => [
                '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
                [],
            ],
            'with option controls = foo as invalid string' => [
                '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
                ['controls' => 'foo'],
            ],
            'with option controls = true as string' => [
                '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
                ['controls' => 'true'],
            ],
            'with option controls = false as string' => [
                '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
                ['controls' => 'false'],
            ],
            'with option controls = true as boolean' => [
                '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
                ['controls' => true],
            ],
            'with option controls = false as boolean, hide player controls' => [
                '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=0&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
                ['controls' => false],
            ],
            'with option controls = 0 as string' => [
                '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=0&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
                ['controls' => '0'],
            ],
            'with option controls = 1 as string' => [
                '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
                ['controls' => '1'],
            ],
            'with option controls = 2 as string' => [
                '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
                ['controls' => '2'],
            ],
            'with option controls = 3 as string' => [
                '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
                ['controls' => '3'],
            ],
            'with option controls = negative number as string' => [
                '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
                ['controls' => '-42'],
            ],
            'with option controls = 0 as int' => [
                '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=0&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
                ['controls' => 0],
            ],
            'with option controls = 1 as int' => [
                '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
                ['controls' => 1],
            ],
            'with option controls = 2 as int' => [
                '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
                ['controls' => 1],
            ],
            'with option controls = 3 as int' => [
                '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
                ['controls' => 3],
            ],
            'with option controls = negative number as int' => [
                '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
                ['controls' => -42],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderOutputWithControlsDataProvider
     */
    public function renderOutputWithDefaultControlsIsCorrect($expected, $options): void
    {
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            $expected,
            $this->subject->render($fileResourceMock, '300m', '200', $options)
        );
    }

    /**
     * @test
     */
    public function renderOutputWithRelatedVideosTurnedOffIsCorrect(): void
    {
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=1&amp;rel=0&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['controls' => 1, 'relatedVideos' => 0])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithAdditionalAttributes(): void
    {
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=0&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen foo="bar" custom-play="preload" sanitizetest="&lt;&gt;&quot;&apos;test" width="300" height="200" allow="fullscreen"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['controls' => 0, 'additionalAttributes' => ['foo' => 'bar', 'custom-play' => 'preload', '<"\'>sanitize^&test' => '<>"\'test']])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithDataAttributesForCustomization(): void
    {
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=0&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen data-player-handler="youTube" data-custom-playerId="player-123" width="300" height="200" allow="fullscreen"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['controls' => 0, 'data' => ['player-handler' => 'youTube', 'custom-playerId' => 'player-123']])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithCombinationOfDataAndAdditionalAttributes(): void
    {
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=0&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen foo="bar" custom-play="preload" data-player-handler="youTube" data-custom-playerId="player-123" width="300" height="200" allow="fullscreen"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['controls' => 0, 'data' => ['player-handler' => 'youTube', 'custom-playerId' => 'player-123'], 'additionalAttributes' => ['foo' => 'bar', 'custom-play' => 'preload']])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithDisabledNoCookieIsCorrect(): void
    {
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://www.youtube.com/embed/7331?autohide=1&amp;controls=0&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['controls' => 0, 'no-cookie' => 0])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithModestbrandingIsCorrect(): void
    {
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=1&amp;modestbranding=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['controls' => 1, 'modestbranding' => 1])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithCustomAllowIsCorrect(): void
    {
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=0&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="foo; bar"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['controls' => 0, 'allow' => 'foo; bar'])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithCustomAllowAndAutoplayIsCorrect(): void
    {
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://www.youtube-nocookie.com/embed/7331?autohide=1&amp;controls=0&amp;autoplay=1&amp;mute=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="foo; bar"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['controls' => 0, 'autoplay' => 1, 'allow' => 'foo; bar'])
        );
    }

    /**
     * @test
     */
    public function renderOutputIsEscaped(): void
    {
        $youtubeHelper = $this->getAccessibleMock(YouTubeHelper::class, ['getOnlineMediaId'], ['youtube']);
        $youtubeHelper->method('getOnlineMediaId')->willReturn('7331<script>danger</script>\'"random"quotes;');

        $subject = $this->getAccessibleMock(YouTubeRenderer::class, ['getOnlineMediaHelper', 'shouldIncludeFrameBorderAttribute']);
        $subject->method('shouldIncludeFrameBorderAttribute')->willReturn(false);
        $subject->method('getOnlineMediaHelper')->willReturn($youtubeHelper);

        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://www.youtube-nocookie.com/embed/7331%3Cscript%3Edanger%3C%2Fscript%3E%27%22random%22quotes%3B?autohide=1&amp;controls=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2Ftest.server.org" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
            $subject->render($fileResourceMock, '300m', '200')
        );
    }
}
