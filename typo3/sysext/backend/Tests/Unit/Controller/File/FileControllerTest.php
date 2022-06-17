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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
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

    protected ServerRequestInterface $request;

    protected ObjectProphecy $flashMessageService;

    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        $this->fileResourceMock = $this->getMockBuilder(File::class)
            ->onlyMethods(['toArray', 'getModificationTime', 'getExtension', 'getParentFolder'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->flashMessageService = $this->prophesize(FlashMessageService::class);
        $this->flashMessageService->getMessageQueueByIdentifier(Argument::cetera())->willReturn($this->prophesize(FlashMessageQueue::class)->reveal());

        $this->fileResourceMock->method('toArray')->willReturn(['id' => 'foo']);
        $this->fileResourceMock->method('getModificationTime')->willReturn(123456789);
        $this->fileResourceMock->method('getExtension')->willReturn('html');
        $this->fileResourceMock->method('getParentFolder')->willReturn(null);

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
        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        $iconProphecy = $this->prophesize(Icon::class);
        $iconProphecy->render()->shouldBeCalled()->willReturn('');
        $iconFactoryProphecy->getIconForFileExtension(Argument::cetera())->willReturn($iconProphecy->reveal());
        $subject = $this->getAccessibleMock(
            FileController::class,
            ['init', 'main'],
            [
                $this->prophesize(ResourceFactory::class)->reveal(),
                $this->prophesize(ExtendedFileUtility::class)->reveal(),
                $iconFactoryProphecy->reveal(),
                $this->prophesize(UriBuilder::class)->reveal(),
                $this->flashMessageService->reveal(),
            ],
        );

        $result = $subject->_call('flattenResultDataValue', $this->fileResourceMock);
        self::assertSame(
            [
                'id' => 'foo',
                'date' => '29-11-73',
                'icon' => '',
                'thumbUrl' => '',
                'path' => '',
            ],
            $result
        );
    }

    /**
     * @test
     */
    public function processAjaxRequestDeleteProcessActuallyDoesNotChangeFileData(): void
    {
        $subject = $this->getAccessibleMock(
            FileController::class,
            ['init', 'main'],
            [
                $this->prophesize(ResourceFactory::class)->reveal(),
                $this->prophesize(ExtendedFileUtility::class)->reveal(),
                $this->prophesize(IconFactory::class)->reveal(),
                $this->prophesize(UriBuilder::class)->reveal(),
                $this->flashMessageService->reveal(),
            ],
        );
        $subject->_set('fileData', ['delete' => [true]]);
        $subject->_set('redirect', false);
        $subject->expects(self::once())->method('main');
        $subject->processAjaxRequest($this->request);
    }

    /**
     * @test
     */
    public function processAjaxRequestEditFileProcessActuallyDoesNotChangeFileData(): void
    {
        $subject = $this->getAccessibleMock(
            FileController::class,
            ['init', 'main'],
            [
                $this->prophesize(ResourceFactory::class)->reveal(),
                $this->prophesize(ExtendedFileUtility::class)->reveal(),
                $this->prophesize(IconFactory::class)->reveal(),
                $this->prophesize(UriBuilder::class)->reveal(),
                $this->flashMessageService->reveal(),
            ],
        );
        $subject->_set('fileData', ['editfile' => [true]]);
        $subject->_set('redirect', false);
        $subject->expects(self::once())->method('main');
        $subject->processAjaxRequest($this->request);
    }

    /**
     * @test
     */
    public function processAjaxRequestReturnsStatus200IfNoErrorOccurs(): void
    {
        $subject = $this->getAccessibleMock(
            FileController::class,
            ['init', 'main'],
            [
                $this->prophesize(ResourceFactory::class)->reveal(),
                $this->prophesize(ExtendedFileUtility::class)->reveal(),
                $this->prophesize(IconFactory::class)->reveal(),
                $this->prophesize(UriBuilder::class)->reveal(),
                $this->flashMessageService->reveal(),
            ],
        );
        $subject->_set('fileData', ['editfile' => [true]]);
        $subject->_set('redirect', false);
        $response = $subject->processAjaxRequest($this->request);
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function processAjaxRequestReturnsStatus500IfErrorOccurs(): void
    {
        $messageQueue = new FlashMessageQueue('test');
        $messageQueue->addMessage(new FlashMessage('Error occurred', 'Error occurred', ContextualFeedbackSeverity::ERROR));
        $this->flashMessageService->getMessageQueueByIdentifier(Argument::cetera())->willReturn($messageQueue);
        $subject = $this->getAccessibleMock(
            FileController::class,
            ['init', 'main'],
            [
                $this->prophesize(ResourceFactory::class)->reveal(),
                $this->prophesize(ExtendedFileUtility::class)->reveal(),
                $this->prophesize(IconFactory::class)->reveal(),
                $this->prophesize(UriBuilder::class)->reveal(),
                $this->flashMessageService->reveal(),
            ],
        );
        $subject->_set('fileData', []);
        $response = $subject->processAjaxRequest($this->request);
        self::assertEquals(500, $response->getStatusCode());
    }
}
