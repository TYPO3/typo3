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

namespace TYPO3\CMS\Form\Tests\Functional\Mvc\Property\TypeConverter;

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Form\Mvc\Property\TypeConverter\UploadedFileReferenceConverter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for UploadedFileReferenceConverter with multiple file upload support
 */
final class MultipleFileUploadTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $uploadPath = $this->instancePath . '/fileadmin/user_upload/';
        GeneralUtility::mkdir_deep($uploadPath);
    }

    protected function tearDown(): void
    {
        $uploadPath = $this->instancePath . '/fileadmin/user_upload/';
        if (is_dir($uploadPath)) {
            GeneralUtility::rmdir($uploadPath, true);
        }

        parent::tearDown();
    }

    #[Test]
    public function convertFromReturnsGenericErrorAndLogsExceptionOnUnexpectedException(): void
    {
        $exception = new \RuntimeException('Could not write to /var/www/html/secret/path', 1234567890);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with(
            'An unknown error has occurred when converting an uploaded file to a resource',
            ['exception' => $exception],
        );
        $subject = $this->get(UploadedFileReferenceConverter::class);
        $subject->setLogger($logger);

        $failingValidator = self::createStub(AbstractValidator::class);
        $failingValidator->method('validate')->willThrowException($exception);

        $testFilePath = $this->createTestFile('unexpected-exception.pdf');

        $source = [
            'error' => \UPLOAD_ERR_OK,
            'name' => 'unexpected-exception.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $testFilePath,
            'size' => filesize($testFilePath),
        ];

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(
            UploadedFileReferenceConverter::class,
            [
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED => 'test-seed-unexpected-exception',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
                UploadedFileReferenceConverter::CONFIGURATION_FILE_VALIDATORS => [
                    $failingValidator,
                ],
            ]
        );

        $result = $subject->convertFrom($source, FileReference::class, [], $configuration);

        self::assertInstanceOf(Error::class, $result);
        self::assertSame('Upload failed.', $result->getMessage());
        self::assertSame(1793200460, $result->getCode());
        self::assertSame([], $this->collectUploadedFiles());
    }

    /**
     * Creates a test file from the fixture directory
     */
    private function createTestFile(string $filename): string
    {
        $fixtureFile = __DIR__ . '/Fixtures/test-document.pdf';
        return $this->createTestFileFromFixture($filename, $fixtureFile);
    }

    /**
     * Creates a test file from a fixture file
     */
    private function createTestFileFromFixture(string $filename, string $fixtureFilePath): string
    {
        $testFilesPath = $this->instancePath . '/typo3temp/var/transient/';
        GeneralUtility::mkdir_deep($testFilesPath);

        $filePath = $testFilesPath . $filename;
        copy($fixtureFilePath, $filePath);

        return $filePath;
    }

    /**
     * Returns the base names of all files persisted below the upload folder, excluding
     * the "index.html" protection files created by the framework.
     *
     * @return list<string>
     */
    private function collectUploadedFiles(): array
    {
        $uploadPath = $this->instancePath . '/fileadmin/user_upload/';
        if (!is_dir($uploadPath)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($uploadPath, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getFilename() !== 'index.html') {
                $files[] = $fileInfo->getFilename();
            }
        }
        sort($files);
        return $files;
    }
}
