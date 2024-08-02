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
use TYPO3\CMS\Extbase\Validation\Validator\FileSizeValidator;

final class FileSizeValidatorTest extends AbstractUploadedFileTestCase
{
    #[Test]
    public function validatorThrowsExceptionIfMinimumOptionIsInvalid(): void
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1708595605);
        $options = ['minimum' => '0', 'maximum' => '1B'];
        $validator = new FileSizeValidator();
        $validator->setOptions($options);

        $testFilename = $this->createTestFile('invalid_minimum_option_testfile.txt', 'TYPO3');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');
        $validator->validate($uploadedFile);
    }

    #[Test]
    public function validatorThrowsExceptionIfMaximumOptionIsInvalid(): void
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1708595606);
        $options = ['minimum' => '0B', 'maximum' => '1'];
        $validator = new FileSizeValidator();
        $validator->setOptions($options);

        $testFilename = $this->createTestFile('invalid_maximum_option_testfile.txt', 'TYPO3');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');
        $validator->validate($uploadedFile);
    }

    #[Test]
    public function validatorHasErrorsIfUploadedFileSizeIsToSmall(): void
    {
        $options = ['minimum' => '10B', 'maximum' => '20B'];
        $validator = new FileSizeValidator();
        $validator->setOptions($options);

        $testFilename = $this->createTestFile('too_small_testfile.txt', 'TYPO3');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');
        self::assertTrue($validator->validate($uploadedFile)->hasErrors());
    }

    #[Test]
    public function validatorHasErrorsIfUploadedFileSizeIsToLarge(): void
    {
        $options = ['minimum' => '0B', 'maximum' => '5B'];
        $validator = new FileSizeValidator();
        $validator->setOptions($options);

        $testFilename = $this->createTestFile('too_large_testfile.txt', 'TYPO3 - Inspiring People To Share');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');
        self::assertTrue($validator->validate($uploadedFile)->hasErrors());
    }

    #[Test]
    public function validatorHasErrorsForObjectStorageWithInvalidUploadedFile(): void
    {
        $options = ['minimum' => '0B', 'maximum' => '5B'];
        $validator = new FileSizeValidator();
        $validator->setOptions($options);

        $testFilename = $this->createTestFile('too_large_testfile.txt', 'TYPO3 - Inspiring People To Share');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($uploadedFile);

        $validationResult = $validator->validate($objectStorage);
        self::assertTrue($validationResult->hasErrors());
        self::assertTrue($validationResult->forProperty('0')->hasErrors());
    }

    #[Test]
    public function validatorThrowsExceptionForNonUploadedFileObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1712057926);

        $validator = new FileSizeValidator();
        $validator->validate('file.txt');
    }

    #[Test]
    public function validatorThrowsExceptionForObjectStorageWithNonUploadedFileObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1722763902);

        $objectStorage = new ObjectStorage();
        $objectStorage->attach(new \stdClass());

        $validator = new FileSizeValidator();
        $validator->validate($objectStorage);
    }
}
