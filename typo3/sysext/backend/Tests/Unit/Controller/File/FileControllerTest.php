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

namespace TYPO3\CMS\Backend\Tests\Unit\Controller\File;

use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\File\FileController;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for \TYPO3\CMS\Backend\Tests\Unit\Controller\File\FileController
 */
class FileControllerTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var File|MockObject
     */
    protected MockObject $fileResourceMock;

    /**
     * @var Folder|MockObject
     */
    protected MockObject $folderResourceMock;

    /**
     * @var ExtendedFileUtility|MockObject
     */
    protected MockObject $mockFileProcessor;

    /**
     * @var ServerRequestInterface|ObjectProphecy
     */
    protected ServerRequestInterface $request;

    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        $this->fileResourceMock = $this->getMockBuilder(File::class)
            ->onlyMethods(['toArray', 'getModificationTime', 'getExtension'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->folderResourceMock = $this->getMockBuilder(Folder::class)
            ->onlyMethods(['getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockFileProcessor = $this->getMockBuilder(ExtendedFileUtility::class)
            ->onlyMethods(['getErrorMessages'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fileResourceMock->method('toArray')->willReturn(['id' => 'foo']);
        $this->fileResourceMock->method('getModificationTime')->willReturn(123456789);
        $this->fileResourceMock->method('getExtension')->willReturn('html');

        $serverRequest = $this->prophesize(ServerRequestInterface::class);
        $this->request = $serverRequest->reveal();
    }

    /**
     * @test
     */
    public function flattenResultDataValueReturnsAnythingElseAsIs(): void
    {
        $subject = $this->getAccessibleMock(FileController::class, ['init', 'main'], [], '', false);
        self::assertTrue($subject->_call('flattenResultDataValue', true));
        self::assertSame([], $subject->_call('flattenResultDataValue', []));
    }

    /**
     * @test
     */
    public function flattenResultDataValueFlattensFile(): void
    {
        $subject = $this->getAccessibleMock(FileController::class, ['init', 'main'], [], '', false);
        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        $iconProphecy = $this->prophesize(Icon::class);
        $iconProphecy->render()->shouldBeCalled()->willReturn('');
        $iconFactoryProphecy->getIconForFileExtension(Argument::cetera())->willReturn($iconProphecy->reveal());
        $subject->_set('iconFactory', $iconFactoryProphecy->reveal());

        $result = $subject->_call('flattenResultDataValue', $this->fileResourceMock);
        self::assertSame(
            [
                'id' => 'foo',
                'date' => '29-11-73',
                'icon' => '',
                'thumbUrl' => '',
            ],
            $result
        );
    }

    /**
     * @test
     */
    public function processAjaxRequestDeleteProcessActuallyDoesNotChangeFileData(): void
    {
        $subject = $this->getAccessibleMock(FileController::class, ['init', 'main'], [], '', false);
        $fileData = ['delete' => [true]];
        $subject->_set('fileProcessor', $this->mockFileProcessor);
        $subject->_set('fileData', $fileData);
        $subject->_set('redirect', false);
        $subject->expects(self::once())->method('main');
        $subject->processAjaxRequest($this->request);
    }

    /**
     * @test
     */
    public function processAjaxRequestEditFileProcessActuallyDoesNotChangeFileData(): void
    {
        $subject = $this->getAccessibleMock(FileController::class, ['init', 'main'], [], '', false);
        $fileData = ['editfile' => [true]];
        $subject->_set('fileProcessor', $this->mockFileProcessor);
        $subject->_set('fileData', $fileData);
        $subject->_set('redirect', false);
        $subject->expects(self::once())->method('main');
        $subject->processAjaxRequest($this->request);
    }

    /**
     * @test
     */
    public function processAjaxRequestReturnsStatus200IfNoErrorOccurs(): void
    {
        $subject = $this->getAccessibleMock(FileController::class, ['init', 'main'], [], '', false);
        $fileData = ['editfile' => [true]];
        $subject->_set('fileProcessor', $this->mockFileProcessor);
        $subject->_set('fileData', $fileData);
        $subject->_set('redirect', false);
        $response = $subject->processAjaxRequest($this->request);
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function processAjaxRequestReturnsStatus500IfErrorOccurs(): void
    {
        $this->mockFileProcessor->method('getErrorMessages')->willReturn(['error occurred']);
        $subject = $this->getAccessibleMock(FileController::class, ['init', 'main'], [], '', false);
        $subject->_set('fileProcessor', $this->mockFileProcessor);
        $response = $subject->processAjaxRequest($this->request);
        self::assertEquals(500, $response->getStatusCode());
    }
}
