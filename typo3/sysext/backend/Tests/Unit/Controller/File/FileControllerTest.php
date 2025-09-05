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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\Controller\File\FileController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileControllerTest extends UnitTestCase
{
    protected File&MockObject $fileResourceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileResourceMock = $this->getMockBuilder(File::class)
            ->onlyMethods(['toArray', 'getModificationTime', 'getExtension', 'getParentFolder'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->fileResourceMock->method('toArray')->willReturn(['id' => 'foo']);
        $this->fileResourceMock->method('getModificationTime')->willReturn(123456789);
        $this->fileResourceMock->method('getExtension')->willReturn('html');
        $this->fileResourceMock->method('getParentFolder')->willReturn($this->createMock(Folder::class));
    }

    #[Test]
    public function flattenResultDataValueReturnsAnythingElseAsIs(): void
    {
        $subject = $this->getAccessibleMock(FileController::class, ['init', 'main'], [], '', false);
        self::assertTrue($subject->_call('flattenResultDataValue', true));
        self::assertSame([], $subject->_call('flattenResultDataValue', []));
    }

    #[Test]
    public function flattenResultDataValueFlattensFile(): void
    {
        $iconFactoryMock = $this->createMock(IconFactory::class);
        $icon = $this->createMock(Icon::class);
        $icon->expects($this->once())->method('render')->willReturn('');
        $iconFactoryMock->method('getIconForFileExtension')->willReturn($icon);
        $subject = $this->getAccessibleMock(
            FileController::class,
            ['init', 'main'],
            [
                $this->createMock(ResourceFactory::class),
                new ExtendedFileUtility(),
                $iconFactoryMock,
                $this->createMock(UriBuilder::class),
                new FlashMessageService(),
            ],
        );

        $result = $subject->_call('flattenResultDataValue', $this->fileResourceMock);
        self::assertSame(
            [
                'id' => 'foo',
                'date' => '1973-11-29',
                'icon' => '',
                'thumbUrl' => '',
                'path' => '',
            ],
            $result
        );
    }

    #[Test]
    public function processAjaxRequestDeleteProcessActuallyDoesNotChangeFileData(): void
    {
        $subject = $this->getAccessibleMock(
            FileController::class,
            ['init', 'main'],
            [
                $this->createMock(ResourceFactory::class),
                new ExtendedFileUtility(),
                $this->createMock(IconFactory::class),
                $this->createMock(UriBuilder::class),
                new FlashMessageService(),
            ],
        );
        $subject->_set('fileData', ['delete' => [true]]);
        $subject->_set('redirect', false);
        $subject->expects($this->once())->method('main');
        $subject->processAjaxRequest(new ServerRequest());
    }

    #[Test]
    public function processAjaxRequestEditFileProcessActuallyDoesNotChangeFileData(): void
    {
        $subject = $this->getAccessibleMock(
            FileController::class,
            ['init', 'main'],
            [
                $this->createMock(ResourceFactory::class),
                new ExtendedFileUtility(),
                $this->createMock(IconFactory::class),
                $this->createMock(UriBuilder::class),
                new FlashMessageService(),
            ],
        );
        $subject->_set('fileData', ['editfile' => [true]]);
        $subject->_set('redirect', false);
        $subject->expects($this->once())->method('main');
        $subject->processAjaxRequest(new ServerRequest());
    }

    #[Test]
    public function processAjaxRequestReturnsStatus200IfNoErrorOccurs(): void
    {
        $subject = $this->getAccessibleMock(
            FileController::class,
            ['init', 'main'],
            [
                $this->createMock(ResourceFactory::class),
                new ExtendedFileUtility(),
                $this->createMock(IconFactory::class),
                $this->createMock(UriBuilder::class),
                new FlashMessageService(),
            ],
        );
        $subject->_set('fileData', ['editfile' => [true]]);
        $subject->_set('redirect', false);
        $response = $subject->processAjaxRequest(new ServerRequest());
        self::assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function processAjaxRequestReturnsStatus500IfErrorOccurs(): void
    {
        $flashMessageService = new FlashMessageService();
        $messageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $messageQueue->addMessage(new FlashMessage('Error occurred', 'Error occurred', ContextualFeedbackSeverity::ERROR));
        $subject = $this->getAccessibleMock(
            FileController::class,
            ['init', 'main'],
            [
                $this->createMock(ResourceFactory::class),
                new ExtendedFileUtility(),
                $this->createMock(IconFactory::class),
                $this->createMock(UriBuilder::class),
                $flashMessageService,
            ],
        );
        $subject->_set('fileData', []);
        $response = $subject->processAjaxRequest(new ServerRequest());
        self::assertEquals(500, $response->getStatusCode());
    }
}
