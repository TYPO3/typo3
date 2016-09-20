<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Rendering;

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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\VimeoHelper;
use TYPO3\CMS\Core\Resource\Rendering\VimeoRenderer;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class VimeoRendererTest
 */
class VimeoRendererTest extends UnitTestCase
{
    /**
     * @var VimeoRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * Set up the test
     */
    protected function setUp()
    {
        parent::setUp();

        /** @var VimeoHelper|\PHPUnit_Framework_MockObject_MockObject $vimeoHelper */
        $vimeoHelper = $this->getAccessibleMock(VimeoHelper::class, ['getOnlineMediaId'], ['vimeo']);
        $vimeoHelper->expects($this->any())->method('getOnlineMediaId')->will($this->returnValue('7331'));

        $this->subject = $this->getAccessibleMock(VimeoRenderer::class, ['getOnlineMediaHelper'], []);
        $this->subject->expects($this->any())->method('getOnlineMediaHelper')->will($this->returnValue($vimeoHelper));
    }

    /**
     * @test
     */
    public function getPriorityReturnsCorrectValue()
    {
        $this->assertSame(1, $this->subject->getPriority());
    }

    /**
     * @test
     */
    public function canRenderReturnsTrueOnCorrectFile()
    {
        /** @var File|\PHPUnit_Framework_MockObject_MockObject $fileResourceMock1 */
        $fileResourceMock1 = $this->getMock(File::class, [], [], '', false);
        $fileResourceMock1->expects($this->any())->method('getMimeType')->will($this->returnValue('video/vimeo'));
        /** @var File|\PHPUnit_Framework_MockObject_MockObject $fileResourceMock2 */
        $fileResourceMock2 = $this->getMock(File::class, [], [], '', false);
        $fileResourceMock2->expects($this->any())->method('getMimeType')->will($this->returnValue('video/unknown'));
        $fileResourceMock2->expects($this->any())->method('getExtension')->will($this->returnValue('vimeo'));

        $this->assertTrue($this->subject->canRender($fileResourceMock1));
        $this->assertTrue($this->subject->canRender($fileResourceMock2));
    }

    /**
     * @test
     */
    public function canRenderReturnsFalseOnCorrectFile()
    {
        /** @var File|\PHPUnit_Framework_MockObject_MockObject $fileResourceMock */
        $fileResourceMock = $this->getMock(File::class, [], [], '', false);
        $fileResourceMock->expects($this->any())->method('getMimeType')->will($this->returnValue('video/youtube'));

        $this->assertFalse($this->subject->canRender($fileResourceMock));
    }

    /**
     * @test
     */
    public function renderOutputIsCorrect()
    {
        /** @var File|\PHPUnit_Framework_MockObject_MockObject $fileResourceMock */
        $fileResourceMock = $this->getMock(File::class, [], [], '', false);

        $this->assertSame(
            '<iframe src="https://player.vimeo.com/video/7331?title=0&amp;byline=0&amp;portrait=0" allowfullscreen width="300" height="200"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200')
        );
    }

    /**
     * @test
     */
    public function renderOutputWithLoopIsCorrect()
    {
        /** @var File|\PHPUnit_Framework_MockObject_MockObject $fileResourceMock */
        $fileResourceMock = $this->getMock(File::class, [], [], '', false);

        $this->assertSame(
            '<iframe src="https://player.vimeo.com/video/7331?loop=1&amp;title=0&amp;byline=0&amp;portrait=0" allowfullscreen width="300" height="200"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['loop' => 1])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithAutoplayIsCorrect()
    {
        /** @var File|\PHPUnit_Framework_MockObject_MockObject $fileResourceMock */
        $fileResourceMock = $this->getMock(File::class, [], [], '', false);

        $this->assertSame(
            '<iframe src="https://player.vimeo.com/video/7331?autoplay=1&amp;title=0&amp;byline=0&amp;portrait=0" allowfullscreen width="300" height="200"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['autoplay' => 1])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithAutoplayFromReferenceIsCorrect()
    {
        /** @var File|\PHPUnit_Framework_MockObject_MockObject $fileResourceMock */
        $fileResourceMock = $this->getMock(File::class, [], [], '', false);

        /** @var FileReference|\PHPUnit_Framework_MockObject_MockObject $fileResourceMock */
        $fileReferenceMock = $this->getMock(FileReference::class, [], [], '', false);
        $fileReferenceMock->expects($this->any())->method('getProperty')->will($this->returnValue(1));
        $fileReferenceMock->expects($this->any())->method('getOriginalFile')->willReturn($fileResourceMock);

        $this->assertSame(
            '<iframe src="https://player.vimeo.com/video/7331?autoplay=1&amp;title=0&amp;byline=0&amp;portrait=0" allowfullscreen width="300" height="200"></iframe>',
            $this->subject->render($fileReferenceMock, '300m', '200')
        );
    }

    /**
     * @test
     */
    public function renderOutputWithAutoplayAndWithoutControllsIsCorrect()
    {
        /** @var File|\PHPUnit_Framework_MockObject_MockObject $fileResourceMock */
        $fileResourceMock = $this->getMock(File::class, [], [], '', false);

        $this->assertSame(
            '<iframe src="https://player.vimeo.com/video/7331?autoplay=1&amp;title=0&amp;byline=0&amp;portrait=0" allowfullscreen width="300" height="200"></iframe>',
            $this->subject->render($fileResourceMock, '300m', '200', ['autoplay' => 1])
        );
    }
}
