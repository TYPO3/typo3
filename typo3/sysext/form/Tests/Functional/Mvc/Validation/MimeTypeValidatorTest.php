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

namespace TYPO3\CMS\Form\Tests\Functional\Mvc\Validation;

use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Form\Mvc\Property\TypeConverter\PseudoFile;
use TYPO3\CMS\Form\Mvc\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\CMS\Form\Mvc\Validation\MimeTypeValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class MimeTypeValidatorTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        mkdir($this->instancePath . '/tmp');
        file_put_contents($this->instancePath . '/tmp/file.exe', "MZ\x90\x00\x03\x00");
        file_put_contents($this->instancePath . '/tmp/file.zip', "PK\x03\x04");
        file_put_contents($this->instancePath . '/tmp/file.jpg', "\xFF\xD8\xFF\xDB");
        file_put_contents($this->instancePath . '/tmp/file.gif', 'GIF87a');
        file_put_contents($this->instancePath . '/tmp/file.pdf', '%PDF-');
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/tmp', true);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function MimeTypeValidatorThrowsExceptionIfAllowedMimeTypesOptionIsString(): void
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1471713296);
        $options = ['allowedMimeTypes' => ''];
        $validator = new MimeTypeValidator();
        $validator->setOptions($options);
        $validator->validate(true);
    }

    /**
     * @test
     */
    public function MimeTypeValidatorThrowsExceptionIfAllowedMimeTypesOptionIsEmptyArray(): void
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1471713296);
        $options = ['allowedMimeTypes' => []];
        $validator = new MimeTypeValidator();
        $validator->setOptions($options);
        $validator->validate(true);
    }

    /**
     * @test
     */
    public function MimeTypeValidatorReturnsTrueIfFileResourceIsNotAllowedMimeType(): void
    {
        $options = ['allowedMimeTypes' => ['image/jpeg']];
        $validator = new MimeTypeValidator();
        $validator->setOptions($options);
        $mockedStorage = $this->getMockBuilder(ResourceStorage::class)->disableOriginalConstructor()->getMock();
        $file = new File(['name' => 'foo', 'identifier' => '/foo', 'mime_type' => 'image/png'], $mockedStorage);
        self::assertTrue($validator->validate($file)->hasErrors());
    }

    /**
     * @test
     */
    public function MimeTypeValidatorReturnsFalseIfInputIsEmptyString(): void
    {
        $options = ['allowedMimeTypes' => ['fake']];
        $validator = new MimeTypeValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function MimeTypeValidatorReturnsTrueIfInputIsNoFileResource(): void
    {
        $options = ['allowedMimeTypes' => ['fake']];
        $validator = new MimeTypeValidator();
        $validator->setOptions($options);
        self::assertTrue($validator->validate('string')->hasErrors());
    }

    public function fileExtensionMatchesMimeTypesDataProvider(): array
    {
        $allowedMimeTypes = ['application/pdf', 'application/vnd.oasis.opendocument.text'];
        return [
            // filename,      file mime-type,    allowed types,     is valid (is allowed)
            ['something.pdf', 'application/pdf', $allowedMimeTypes, true],
            ['something.txt', 'application/pdf', $allowedMimeTypes, false],
            ['something.pdf', 'application/pdf', [false], false],
            ['something.pdf', 'false', $allowedMimeTypes, false],
        ];
    }

    /**
     * @test
     * @dataProvider fileExtensionMatchesMimeTypesDataProvider
     */
    public function fileExtensionMatchesMimeTypes(string $fileName, string $fileMimeType, array $allowedMimeTypes, bool $isValid): void
    {
        $options = ['allowedMimeTypes' => $allowedMimeTypes];
        $validator = new MimeTypeValidator();
        $validator->setOptions($options);
        $mockedStorage = $this->getMockBuilder(ResourceStorage::class)->disableOriginalConstructor()->getMock();
        $file = new File([
            'name' => $fileName,
            'identifier' => '/folder/' . $fileName,
            'mime_type' => $fileMimeType,
        ], $mockedStorage);
        $result = $validator->validate($file);
        self::assertSame($isValid, !$result->hasErrors());
    }

    public function validateHandlesMimeTypesOfFilesDataProvider(): array
    {
        // error-codes
        // + 1471708998: mime-type not allowed
        // + 1613126216: mime-type to file-extension mismatch
        return [
            'submitted gif as upload.gif' => [
                [
                    'tmp_name' => 'file.gif',
                    'name' => 'upload.gif',
                    'type' => 'does/not-matter',
                ],
                ['image/gif'],
            ],
            'submitted jpg as upload.jpg' => [
                [
                    'tmp_name' => 'file.jpg',
                    'name' => 'upload.jpg',
                    'type' => 'does/not-matter',
                ],
                ['image/jpeg'],
            ],
            'submitted pdf as upload.pdf' => [
                [
                    'tmp_name' => 'file.pdf',
                    'name' => 'upload.pdf',
                    'type' => 'does/not-matter',
                ],
                ['application/pdf'],
            ],
            'submitted exe as upload.exe' => [
                [
                    'tmp_name' => 'file.exe',
                    'name' => 'upload.exe',
                    'type' => 'does/not-matter',
                ], // upload data (as in $_FILES)
                ['image/gif'], // allowed mime-types
                [1471708998], // expected error-codes
            ],
            'submitted gif as upload.exe' => [
                [
                    'tmp_name' => 'file.gif',
                    'name' => 'upload.exe',
                    'type' => 'does/not-matter',
                ], // upload data (as in $_FILES)
                ['image/gif'], // allowed mime-types
                [1613126216], // expected error-codes
            ],
        ];
    }

    /**
     * @param array<string, int|string> $uploadData
     * @param List<string> $allowedMimeTypes
     * @param List<int> $expectedErrorCodes
     * @test
     * @dataProvider validateHandlesMimeTypesOfFilesDataProvider
     */
    public function validateHandlesMimeTypesOfFiles(array $uploadData, array $allowedMimeTypes, array $expectedErrorCodes = []): void
    {
        $uploadData['tmp_name'] = $this->instancePath . '/tmp/' . $uploadData['tmp_name'];
        $uploadData['error'] = \UPLOAD_ERR_OK;
        $uploadData['size'] = filesize($uploadData['tmp_name']);

        $validator = new MimeTypeValidator();
        $validator->setOptions(['allowedMimeTypes' => $allowedMimeTypes]);

        $resource = new PseudoFile($uploadData);
        $result = $validator->validate($resource);
        $errorCodes = array_map([$this, 'resolveErrorCode'], $result->getErrors());
        self::assertSame($expectedErrorCodes, $errorCodes);
    }

    private function resolveErrorCode(Error $error): int
    {
        return $error->getCode();
    }
}
