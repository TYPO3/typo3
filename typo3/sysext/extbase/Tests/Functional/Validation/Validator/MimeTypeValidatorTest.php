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

namespace TYPO3\CMS\Extbase\Tests\Functional\Validation\Validator;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\CMS\Extbase\Validation\Validator\MimeTypeValidator;

final class MimeTypeValidatorTest extends AbstractUploadedFileTestCase
{
    #[Test]
    public function validatorThrowsExceptionIfAllowedMimeTypesOptionIsString(): void
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1708526223);
        $options = ['allowedMimeTypes' => ''];
        $validator = new MimeTypeValidator();
        $validator->setOptions($options);

        $testFilename = $this->createTestFile('wrong_mimetype_option_testfile.txt', 'TYPO3');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');
        $validator->validate($uploadedFile);
    }

    #[Test]
    public function validatorThrowsExceptionIfAllowedMimeTypesOptionIsEmptyArray(): void
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1708526223);
        $options = ['allowedMimeTypes' => []];
        $validator = new MimeTypeValidator();
        $validator->setOptions($options);

        $testFilename = $this->createTestFile('wrong_mimetype_option_testfile.txt', 'TYPO3');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');
        $validator->validate($uploadedFile);
    }

    #[Test]
    public function validatorHasErrorsIfMimeTypeIsNotAllowed(): void
    {
        $options = ['allowedMimeTypes' => ['image/jpeg']];
        $validator = new MimeTypeValidator();
        $validator->setOptions($options);

        $testFilename = $this->createTestFile('mimetype_pdf_file.pdf', '%PDF-');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.pdf');

        $validationResult = $validator->validate($uploadedFile);
        self::assertTrue($validationResult->hasErrors());
        self::assertEquals(1708538973, $validationResult->getFirstError()->getCode());
    }

    #[Test]
    public function validatorHasErrorsIfFileExtensionDoesNotMatchExpectedMimeType(): void
    {
        $options = ['allowedMimeTypes' => ['text/plain']];
        $validator = new MimeTypeValidator();
        $validator->setOptions($options);

        $testFilename = $this->createTestFile('mimetype_php_testfile.txt', '<?php phpinfo();');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');

        $validationResult = $validator->validate($uploadedFile);
        self::assertTrue($validationResult->hasErrors());
        self::assertEquals(1708538973, $validationResult->getFirstError()->getCode());
    }

    #[Test]
    public function validatorHasNoErrorsIfMimeTypeIsAllowed(): void
    {
        $options = ['allowedMimeTypes' => ['application/pdf']];
        $validator = new MimeTypeValidator();
        $validator->setOptions($options);

        $testFilename = $this->createTestFile('mimetype_pdf_file.pdf', '%PDF-');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.pdf');
        self::assertFalse($validator->validate($uploadedFile)->hasErrors());
    }

    #[Test]
    public function validatorHasErrorsIfFileExtensionDoesNotMatchMimeType(): void
    {
        $options = ['allowedMimeTypes' => ['application/pdf']];
        $validator = new MimeTypeValidator();
        $validator->setOptions($options);

        $testFilename = $this->createTestFile('pfd_file_as_text.txt', '%PDF-');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'test.txt');

        $validationResult = $validator->validate($uploadedFile);
        self::assertTrue($validationResult->hasErrors());
        self::assertEquals(1718469466, $validationResult->getFirstError()->getCode());
    }

    #[Test]
    public function validatorHasErrorsForObjectStorageWithInvalidUploadedFile(): void
    {
        $options = ['allowedMimeTypes' => ['application/pdf']];
        $validator = new MimeTypeValidator();
        $validator->setOptions($options);

        $testFilename = $this->createTestFile('pfd_file_as_text.txt', '%PDF-');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'test.txt');

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($uploadedFile);

        $validationResult = $validator->validate($objectStorage);
        self::assertTrue($validationResult->hasErrors());
        self::assertTrue($validationResult->forProperty('0')->hasErrors());
    }

    #[Test]
    public function validatorHasNoErrorsIfFileExtensionDoesNotMatchMimeTypeAndIgnoreFileExtensionCheckIsTrue(): void
    {
        $options = ['allowedMimeTypes' => ['application/pdf'], 'ignoreFileExtensionCheck' => true];
        $validator = new MimeTypeValidator();
        $validator->setOptions($options);

        $testFilename = $this->createTestFile('pfd_file_as_text.txt', '%PDF-');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'test.txt');

        $validationResult = $validator->validate($uploadedFile);
        self::assertFalse($validationResult->hasErrors());
    }

    #[Test]
    public function validatorThrowsExceptionForNonUploadedFileObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1712057926);

        $validator = new MimeTypeValidator();
        $validator->validate('file.txt');
    }

    #[Test]
    public function validatorThrowsExceptionForObjectStorageWithNonUploadedFileObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1722763902);

        $objectStorage = new ObjectStorage();
        $objectStorage->attach(new \stdClass());

        $validator = new MimeTypeValidator();
        $validator->validate($objectStorage);
    }
}
