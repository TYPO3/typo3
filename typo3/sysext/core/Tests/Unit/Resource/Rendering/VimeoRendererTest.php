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
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\VimeoHelper;
use TYPO3\CMS\Core\Resource\Rendering\VimeoRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class VimeoRendererTest
 */
class VimeoRendererTest extends UnitTestCase
{
    /**
     * @var VimeoRenderer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $subject;

    /**
     * Set up the test
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var VimeoHelper|\PHPUnit\Framework\MockObject\MockObject $vimeoHelper */
        $vimeoHelper = $this->getAccessibleMock(VimeoHelper::class, ['getOnlineMediaId'], ['vimeo']);
        $vimeoHelper->expects(self::any())->method('getOnlineMediaId')->willReturn('7331');

        $this->subject = $this->getAccessibleMock(VimeoRenderer::class, ['getOnlineMediaHelper'], []);
        $this->subject->expects(self::any())->method('getOnlineMediaHelper')->willReturn($vimeoHelper);
    }

    /**
     * @test
     */
    public function getPriorityReturnsCorrectValue()
    {
        self::assertSame(1, $this->subject->getPriority());
    }

    /**
     * @test
     */
    public function canRenderReturnsTrueOnCorrectFile()
    {
        /** @var File|\PHPUnit\Framework\MockObject\MockObject $fileResourceMock1 */
        $fileResourceMock1 = $this->createMock(File::class);
        $fileResourceMock1->expects(self::any())->method('getMimeType')->willReturn('video/vimeo');
        /** @var File|\PHPUnit\Framework\MockObject\MockObject $fileResourceMock2 */
        $fileResourceMock2 = $this->createMock(File::class);
        $fileResourceMock2->expects(self::any())->method('getMimeType')->willReturn('video/unknown');
        $fileResourceMock2->expects(self::any())->method('getExtension')->willReturn('vimeo');

        self::assertTrue($this->subject->canRender($fileResourceMock1));
        self::assertTrue($this->subject->canRender($fileResourceMock2));
    }

    /**
     * @test
     */
    public function canRenderReturnsFalseOnCorrectFile()
    {
        /** @var File|\PHPUnit\Framework\MockObject\MockObject $fileResourceMock */
        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->expects(self::any())->method('getMimeType')->willReturn('video/youtube');

        self::assertFalse($this->subject->canRender($fileResourceMock));
    }

    /**
     * @test
     */
    public function renderOutputIsCorrect()
    {
        /** @var File|\PHPUnit\Framework\MockObject\MockObject $fileResourceMock */
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://player.vimeo.com/video/7331?title=0&amp;byline=0&amp;portrait=0" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200')
        );
    }

    /**
     * @test
     */
    public function renderOutputWithLoopIsCorrect()
    {
        /** @var File|\PHPUnit\Framework\MockObject\MockObject $fileResourceMock */
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://player.vimeo.com/video/7331?loop=1&amp;title=0&amp;byline=0&amp;portrait=0" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['loop' => 1])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithAutoplayIsCorrect()
    {
        /** @var File|\PHPUnit\Framework\MockObject\MockObject $fileResourceMock */
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://player.vimeo.com/video/7331?autoplay=1&amp;title=0&amp;byline=0&amp;portrait=0" allowfullscreen width="300" height="200" allow="autoplay; fullscreen"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['autoplay' => 1])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithAutoplayFromReferenceIsCorrect()
    {
        /** @var File|\PHPUnit\Framework\MockObject\MockObject $fileResourceMock */
        $fileResourceMock = $this->createMock(File::class);

        /** @var FileReference|\PHPUnit\Framework\MockObject\MockObject $fileResourceMock */
        $fileReferenceMock = $this->createMock(FileReference::class);
        $fileReferenceMock->expects(self::any())->method('getProperty')->willReturn(1);
        $fileReferenceMock->expects(self::any())->method('getOriginalFile')->willReturn($fileResourceMock);

        self::assertSame(
            '<iframe src="https://player.vimeo.com/video/7331?autoplay=1&amp;title=0&amp;byline=0&amp;portrait=0" allowfullscreen width="300" height="200" allow="autoplay; fullscreen"></iframe>',
            $this->subject->render($fileReferenceMock, '300m', '200')
        );
    }

    /**
     * @test
     */
    public function renderOutputWithAutoplayAndWithoutControlsIsCorrect()
    {
        /** @var File|\PHPUnit\Framework\MockObject\MockObject $fileResourceMock */
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://player.vimeo.com/video/7331?autoplay=1&amp;title=0&amp;byline=0&amp;portrait=0" allowfullscreen width="300" height="200" allow="autoplay; fullscreen"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['autoplay' => 1])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithAdditionalAttributes()
    {
        /** @var File|\PHPUnit\Framework\MockObject\MockObject $fileResourceMock */
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://player.vimeo.com/video/7331?title=0&amp;byline=0&amp;portrait=0" allowfullscreen foo="bar" custom-play="preload" sanitizetest="&lt;&gt;&quot;&apos;test" width="300" height="200" allow="fullscreen"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['additionalAttributes' => ['foo' => 'bar', 'custom-play' => 'preload', '<"\'>sanitize^&test' => '<>"\'test']])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithDataAttributesForCustomization()
    {
        /** @var File|\PHPUnit\Framework\MockObject\MockObject $fileResourceMock */
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://player.vimeo.com/video/7331?title=0&amp;byline=0&amp;portrait=0" allowfullscreen data-player-handler="vimeo" data-custom-playerId="player-123" data-sanitizetest="test" width="300" height="200" allow="fullscreen"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['data' => ['player-handler' => 'vimeo', 'custom-playerId' => 'player-123', '*sanitize&test"' => 'test']])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithCombinationOfDataAndAdditionalAttributes()
    {
        /** @var File|\PHPUnit\Framework\MockObject\MockObject $fileResourceMock */
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://player.vimeo.com/video/7331?title=0&amp;byline=0&amp;portrait=0" allowfullscreen foo="bar" custom-play="preload" data-player-handler="vimeo" data-custom-playerId="player-123" width="300" height="200" allow="fullscreen"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['data' => ['player-handler' => 'vimeo', 'custom-playerId' => 'player-123'], 'additionalAttributes' => ['foo' => 'bar', 'custom-play' => 'preload']])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithCustomAllowIsCorrect()
    {
        /** @var File|\PHPUnit\Framework\MockObject\MockObject $fileResourceMock */
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://player.vimeo.com/video/7331?title=0&amp;byline=0&amp;portrait=0" allowfullscreen width="300" height="200" allow="foo; bar"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['allow' => 'foo; bar'])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithCustomAllowAndAutoplayIsCorrect()
    {
        /** @var File|\PHPUnit\Framework\MockObject\MockObject $fileResourceMock */
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://player.vimeo.com/video/7331?autoplay=1&amp;title=0&amp;byline=0&amp;portrait=0" allowfullscreen width="300" height="200" allow="foo; bar"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['allow' => 'foo; bar', 'autoplay' => 1])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithPrivateVimeoCodeIsCorrect()
    {
        /** @var VimeoHelper|\PHPUnit\Framework\MockObject\MockObject $vimeoHelper */
        $vimeoHelper = $this->getAccessibleMock(VimeoHelper::class, ['getOnlineMediaId'], ['vimeo']);
        $vimeoHelper->expects(self::any())->method('getOnlineMediaId')->willReturn('7331/private0123');

        $subject = $this->getAccessibleMock(VimeoRenderer::class, ['getOnlineMediaHelper'], []);
        $subject->expects(self::any())->method('getOnlineMediaHelper')->willReturn($vimeoHelper);

        /** @var File|\PHPUnit\Framework\MockObject\MockObject $fileResourceMock */
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://player.vimeo.com/video/7331/private0123?title=0&amp;byline=0&amp;portrait=0" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
            $subject->render($fileResourceMock, '300m', '200')
        );
    }

    /**
     * @test
     */
    public function renderOutputIsEscaped()
    {
        /** @var VimeoHelper|\PHPUnit\Framework\MockObject\MockObject $vimeoHelper */
        $vimeoHelper = $this->getAccessibleMock(VimeoHelper::class, ['getOnlineMediaId'], ['vimeo']);
        $vimeoHelper->expects(self::any())->method('getOnlineMediaId')->willReturn('7331<script>danger</script>\'"random"quotes;');

        $subject = $this->getAccessibleMock(VimeoRenderer::class, ['getOnlineMediaHelper'], []);
        $subject->expects(self::any())->method('getOnlineMediaHelper')->willReturn($vimeoHelper);

        /** @var File|\PHPUnit\Framework\MockObject\MockObject $fileResourceMock */
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://player.vimeo.com/video/7331&lt;script&gt;danger&lt;/script&gt;&apos;&quot;random&quot;quotes;?title=0&amp;byline=0&amp;portrait=0" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
            $subject->render($fileResourceMock, '300m', '200')
        );
    }

    /**
     * @test
     */
    public function renderOutputWithApiIsCorrect()
    {
        /** @var File|\PHPUnit\Framework\MockObject\MockObject $fileResourceMock */
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://player.vimeo.com/video/7331?api=1&amp;title=0&amp;byline=0&amp;portrait=0" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['api' => 1])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithDisabledNoCookieIsCorrect()
    {
        /** @var File|\PHPUnit_Framework_MockObject_MockObject $fileResourceMock */
        $fileResourceMock = $this->createMock(File::class);

        self::assertSame(
            '<iframe src="https://player.vimeo.com/video/7331?api=1&amp;dnt=1&amp;title=0&amp;byline=0&amp;portrait=0" allowfullscreen width="300" height="200" allow="fullscreen"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['api' => 1, 'no-cookie' => 1])
        );
    }
}
