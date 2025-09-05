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

namespace TYPO3\CMS\Extbase\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ImageScriptServiceTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected ImageService $subject;

    /**
     * Initialize ImageService and environment service mock
     */
    protected function setUp(): void
    {
        parent::setUp();
        $resourceFactory = $this->createMock(ResourceFactory::class);
        $this->subject = new ImageService($resourceFactory);
        $_SERVER['HTTP_HOST'] = 'foo.bar';
    }

    #[Test]
    public function fileIsUnwrappedFromReferenceForProcessing(): void
    {
        $reference = $this->getMockBuilder(FileReference::class)->disableOriginalConstructor()->getMock();
        $file = $this->createMock(File::class);
        $processedFile = $this->createMock(ProcessedFile::class);
        $file->expects($this->once())->method('process')->willReturn($processedFile);
        $reference->expects($this->once())->method('getOriginalFile')->willReturn($file);
        $processedFile->expects($this->once())->method('getOriginalFile')->willReturn($file);
        $processedFile->expects($this->atLeastOnce())->method('getPublicUrl')->willReturn('https://example.com/foo.png');

        $this->subject->applyProcessingInstructions($reference, []);
    }

    public static function prefixIsCorrectlyAppliedToGetImageUriDataProvider(): array
    {
        return [
            'with scheme' => ['http://foo.bar/img.jpg', 'http://foo.bar/img.jpg'],
            'scheme relative' => ['//foo.bar/img.jpg', '//foo.bar/img.jpg'],
            'without scheme' => ['/prefix/foo.bar/img.jpg', '/prefix/foo.bar/img.jpg'],
        ];
    }

    #[DataProvider('prefixIsCorrectlyAppliedToGetImageUriDataProvider')]
    #[Test]
    public function prefixIsCorrectlyAppliedToGetImageUri($imageUri, $expected): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->absRefPrefix = '/prefix/';

        $file = $this->createMock(File::class);
        $file->expects($this->once())->method('getPublicUrl')->willReturn($imageUri);

        self::assertSame($expected, $this->subject->getImageUri($file));
    }

    public static function prefixIsCorrectlyAppliedToGetImageUriWithAbsolutePathDataProvider(): array
    {
        return [
            'with scheme' => ['http://foo.bar/img.jpg', 'http://foo.bar/img.jpg'],
            'scheme relative' => ['//foo.bar/img.jpg', '//foo.bar/img.jpg'],
            'without scheme' => ['/prefix/foo.bar/img.jpg', 'http://foo.bar/prefix/foo.bar/img.jpg'],
        ];
    }

    #[DataProvider('prefixIsCorrectlyAppliedToGetImageUriWithAbsolutePathDataProvider')]
    #[Test]
    public function prefixIsCorrectlyAppliedToGetImageUriWithForcedAbsoluteUrl($imageUri, $expected): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->absRefPrefix = '/prefix/';

        $file = $this->createMock(File::class);
        $file->expects($this->once())->method('getPublicUrl')->willReturn($imageUri);

        self::assertSame($expected, $this->subject->getImageUri($file, true));
    }
}
