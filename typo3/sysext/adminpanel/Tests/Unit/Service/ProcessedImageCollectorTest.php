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

namespace TYPO3\CMS\Adminpanel\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Adminpanel\Service\ProcessedImageCollector;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Event\AfterFileProcessingEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Frontend\Authentication\FrontendBackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ProcessedImageCollectorTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST'], $GLOBALS['BE_USER']);
        parent::tearDown();
    }

    #[Test]
    public function processedImagesAreCollectedWhenAdminPanelIsOpen(): void
    {
        $this->setUpFrontendRequestWithOpenAdminPanel();
        $subject = new ProcessedImageCollector();

        $subject->collect($this->createEvent('/fileadmin/_processed_/foo.png', 1024, 200, 100));

        self::assertSame(
            [
                '/fileadmin/_processed_/foo.png' => [
                    'name' => '/fileadmin/_processed_/foo.png',
                    'size' => 1024,
                    'width' => 200,
                    'height' => 100,
                ],
            ],
            $subject->getImages()
        );
    }

    #[Test]
    public function theSameProcessedImageIsCollectedOnlyOnce(): void
    {
        $this->setUpFrontendRequestWithOpenAdminPanel();
        $subject = new ProcessedImageCollector();

        $subject->collect($this->createEvent('/fileadmin/_processed_/foo.png', 1024, 200, 100));
        $subject->collect($this->createEvent('/fileadmin/_processed_/foo.png', 1024, 200, 100));

        self::assertCount(1, $subject->getImages());
    }

    #[Test]
    public function nothingIsCollectedWhenAdminPanelIsNotOpen(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $GLOBALS['BE_USER'] = false;
        $subject = new ProcessedImageCollector();

        $subject->collect($this->createEvent('/fileadmin/_processed_/foo.png', 1024, 200, 100));

        self::assertSame([], $subject->getImages());
    }

    #[Test]
    public function nothingIsCollectedOutsideOfATypo3ApplicationRequest(): void
    {
        // e.g. a CLI command processing images: the request carries no "applicationType"
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest();
        $subject = new ProcessedImageCollector();

        $subject->collect($this->createEvent('/fileadmin/_processed_/foo.png', 1024, 200, 100));

        self::assertSame([], $subject->getImages());
    }

    #[Test]
    public function imagesWithoutPublicUrlAreIgnored(): void
    {
        $this->setUpFrontendRequestWithOpenAdminPanel();
        $subject = new ProcessedImageCollector();

        $subject->collect($this->createEvent(null, 1024, 200, 100));

        self::assertSame([], $subject->getImages());
    }

    #[Test]
    public function missingFilesAreCollectedWithZeroSize(): void
    {
        $this->setUpFrontendRequestWithOpenAdminPanel();
        $subject = new ProcessedImageCollector();

        $processedFile = self::createStub(ProcessedFile::class);
        $processedFile->method('getPublicUrl')->willReturn('/fileadmin/_processed_/gone.png');
        $processedFile->method('getSize')->willThrowException(new \RuntimeException('File has been deleted.', 1329821480));
        $processedFile->method('getProperty')->willReturn(0);

        $subject->collect(new AfterFileProcessingEvent(
            self::createStub(DriverInterface::class),
            $processedFile,
            self::createStub(File::class),
            ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
            []
        ));

        self::assertSame(0, $subject->getImages()['/fileadmin/_processed_/gone.png']['size']);
    }

    private function setUpFrontendRequestWithOpenAdminPanel(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $backendUser = self::createStub(FrontendBackendUserAuthentication::class);
        $backendUser->method('getTSConfig')->willReturn(['admPanel.' => ['enable.' => ['all' => 1]]]);
        $backendUser->uc = ['AdminPanel' => ['display_top' => true]];
        $GLOBALS['BE_USER'] = $backendUser;
    }

    private function createEvent(?string $publicUrl, int $size, int $width, int $height): AfterFileProcessingEvent
    {
        $processedFile = self::createStub(ProcessedFile::class);
        $processedFile->method('getPublicUrl')->willReturn($publicUrl);
        $processedFile->method('getSize')->willReturn($size);
        $processedFile->method('getProperty')->willReturnMap([
            ['width', $width],
            ['height', $height],
        ]);

        return new AfterFileProcessingEvent(
            self::createStub(DriverInterface::class),
            $processedFile,
            self::createStub(File::class),
            ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
            []
        );
    }
}
