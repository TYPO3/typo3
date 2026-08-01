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

namespace TYPO3\CMS\Core\Tests\Functional\Resource;

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ProcessedFileTest extends FunctionalTestCase
{
    private const string TEST_IMAGE = 'fileadmin/ProcessedFileTest.jpg';

    /**
     * @var array<string, non-empty-string>
     */
    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Resource/Fixtures/ProcessedFileTest.jpg' => 'fileadmin/ProcessedFileTest.jpg',
        'typo3/sysext/core/Tests/Functional/Resource/Fixtures/ProcessedFileTest.txt' => 'fileadmin/ProcessedFileTest.txt',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function processedFileArrayCanBeSerialized(): void
    {
        $resourceFactory = $this->get(ResourceFactory::class);
        $originalFile = $resourceFactory->retrieveFileOrFolderObject(self::TEST_IMAGE);
        $someProcessedFile = new ProcessedFile(
            $originalFile,
            ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
            []
        );
        $processedFile = new ProcessedFile(
            $originalFile,
            ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
            [
                'width' => '2000c',
                'height' => '300c-60',
                'm' => [
                    'bgImg' => $someProcessedFile,
                    'mask' => $someProcessedFile,
                ],
            ],
        );
        serialize($processedFile->toArray());
    }

    #[Test]
    public function previewOfNonImageFileUsesStaticSvgPlaceholder(): void
    {
        $resourceFactory = $this->get(ResourceFactory::class);
        $originalFile = $resourceFactory->retrieveFileOrFolderObject('fileadmin/ProcessedFileTest.txt');
        self::assertInstanceOf(File::class, $originalFile);
        $processedFile = $originalFile->process(ProcessedFile::CONTEXT_IMAGEPREVIEW, ['width' => 64, 'height' => 64]);
        self::assertTrue($processedFile->isProcessed());
        self::assertSame('svg', $processedFile->getExtension());
        self::assertFileEquals(
            GeneralUtility::getFileAbsFileName('EXT:core/Resources/Public/Images/PreviewNotAvailable.svg'),
            $processedFile->getForLocalProcessing(false)
        );
        self::assertSame(64, (int)$processedFile->getProperty('width'));
        self::assertSame(64, (int)$processedFile->getProperty('height'));
    }
}
