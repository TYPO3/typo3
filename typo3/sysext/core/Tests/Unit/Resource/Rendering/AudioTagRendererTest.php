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

/**
 * Class AudioTagRendererTest
 */
class AudioTagRendererTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function getPriorityReturnsCorrectValue()
    {
        $audioTagRenderer = new \TYPO3\CMS\Core\Resource\Rendering\AudioTagRenderer();

        $this->assertSame(1, $audioTagRenderer->getPriority());
    }

    /**
     * @test
     */
    public function canRenderReturnsTrueOnCorrectFile()
    {
        $audioTagRenderer = new \TYPO3\CMS\Core\Resource\Rendering\AudioTagRenderer();

        $fileResourceMock1 = $this->getMock(\TYPO3\CMS\Core\Resource\File::class, [], [], '', false);
        $fileResourceMock1->expects($this->any())->method('getMimeType')->will($this->returnValue('audio/mpeg'));
        $fileResourceMock2 = $this->getMock(\TYPO3\CMS\Core\Resource\File::class, [], [], '', false);
        $fileResourceMock2->expects($this->any())->method('getMimeType')->will($this->returnValue('audio/wav'));
        $fileResourceMock3 = $this->getMock(\TYPO3\CMS\Core\Resource\File::class, [], [], '', false);
        $fileResourceMock3->expects($this->any())->method('getMimeType')->will($this->returnValue('audio/ogg'));

        $this->assertTrue($audioTagRenderer->canRender($fileResourceMock1));
        $this->assertTrue($audioTagRenderer->canRender($fileResourceMock2));
        $this->assertTrue($audioTagRenderer->canRender($fileResourceMock3));
    }

    /**
     * @test
     */
    public function canRenderReturnsFalseOnCorrectFile()
    {
        $audioTagRenderer = new \TYPO3\CMS\Core\Resource\Rendering\AudioTagRenderer();

        $fileResourceMock = $this->getMock(\TYPO3\CMS\Core\Resource\File::class, [], [], '', false);
        $fileResourceMock->expects($this->any())->method('getMimeType')->will($this->returnValue('video/mp4'));

        $this->assertFalse($audioTagRenderer->canRender($fileResourceMock));
    }

    /**
     * @test
     */
    public function renderOutputIsCorrect()
    {
        $audioTagRenderer = new \TYPO3\CMS\Core\Resource\Rendering\AudioTagRenderer();

        $fileResourceMock = $this->getMock(\TYPO3\CMS\Core\Resource\File::class, [], [], '', false);
        $fileResourceMock->expects($this->any())->method('getMimeType')->will($this->returnValue('audio/mpeg'));
        $fileResourceMock->expects($this->any())->method('getPublicUrl')->will($this->returnValue('//:path/myAudioFile?foo=bar&baz=true'));

        $this->assertSame(
            '<audio controls><source src="//:path/myAudioFile?foo=bar&amp;baz=true" type="audio/mpeg"></audio>',
            $audioTagRenderer->render($fileResourceMock, '300m', '200')
        );
    }

    /**
     * @test
     */
    public function renderOutputWithLoopIsCorrect()
    {
        $audioTagRenderer = new \TYPO3\CMS\Core\Resource\Rendering\AudioTagRenderer();

        $fileResourceMock = $this->getMock(\TYPO3\CMS\Core\Resource\File::class, [], [], '', false);
        $fileResourceMock->expects($this->any())->method('getMimeType')->will($this->returnValue('audio/mpeg'));
        $fileResourceMock->expects($this->any())->method('getPublicUrl')->will($this->returnValue('//:path/myAudioFile'));

        $this->assertSame(
            '<audio controls loop><source src="//:path/myAudioFile" type="audio/mpeg"></audio>',
            $audioTagRenderer->render($fileResourceMock, '300m', '200', ['loop' => 1])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithAutoplayIsCorrect()
    {
        $audioTagRenderer = new \TYPO3\CMS\Core\Resource\Rendering\AudioTagRenderer();

        $fileResourceMock = $this->getMock(\TYPO3\CMS\Core\Resource\File::class, [], [], '', false);
        $fileResourceMock->expects($this->any())->method('getMimeType')->will($this->returnValue('audio/mpeg'));
        $fileResourceMock->expects($this->any())->method('getPublicUrl')->will($this->returnValue('//:path/myAudioFile'));

        $this->assertSame(
            '<audio controls autoplay><source src="//:path/myAudioFile" type="audio/mpeg"></audio>',
            $audioTagRenderer->render($fileResourceMock, '300m', '200', ['autoplay' => 1])
        );
    }

    /**
     * @test
     */
    public function renderOutputWithAutoplayAndWithoutControllsIsCorrect()
    {
        $audioTagRenderer = new \TYPO3\CMS\Core\Resource\Rendering\AudioTagRenderer();

        $fileResourceMock = $this->getMock(\TYPO3\CMS\Core\Resource\File::class, [], [], '', false);
        $fileResourceMock->expects($this->any())->method('getMimeType')->will($this->returnValue('audio/mpeg'));
        $fileResourceMock->expects($this->any())->method('getPublicUrl')->will($this->returnValue('//:path/myAudioFile'));

        $this->assertSame(
            '<audio autoplay><source src="//:path/myAudioFile" type="audio/mpeg"></audio>',
            $audioTagRenderer->render($fileResourceMock, '300m', '200', ['controls' => 0, 'autoplay' => 1])
        );
    }
}
