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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Validation\Validator\FileNameValidator;

final class FileNameValidatorTest extends AbstractUploadedFileTestCase
{
    public static function invalidFilenamesDataProvider(): array
    {
        return [
            ['invalid.php'],
            ['invalid.phar'],
        ];
    }

    #[DataProvider('invalidFilenamesDataProvider')]
    #[Test]
    public function validatorHasErrorsForInvalidFilenames(string $filename): void
    {
        $validator = new FileNameValidator();
        $uploadedFile = new UploadedFile('/tmp/testfile', 100, UPLOAD_ERR_OK, $filename);
        self::assertTrue($validator->validate($uploadedFile)->hasErrors());
    }

    #[Test]
    public function validatorHasErrorsForObjectStorageWithInvalidUploadedFile(): void
    {
        $validator = new FileNameValidator();
        $uploadedFile1 = new UploadedFile('/tmp/testfile1', 100, UPLOAD_ERR_OK, 'valid.txt');
        $uploadedFile2 = new UploadedFile('/tmp/testfile2', 100, UPLOAD_ERR_OK, 'invalid.phar');
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($uploadedFile1);
        $objectStorage->attach($uploadedFile2);
        $validationResult = $validator->validate($objectStorage);
        self::assertTrue($validationResult->hasErrors());
        self::assertFalse($validationResult->forProperty('0')->hasErrors());
        self::assertTrue($validationResult->forProperty('1')->hasErrors());
    }

    #[Test]
    public function validatorThrowsExceptionForNonUploadedFileObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1712057926);

        $validator = new FileNameValidator();
        $validator->validate('file.txt');
    }

    #[Test]
    public function validatorThrowsExceptionForObjectStorageWithNonUploadedFileObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1722763902);

        $objectStorage = new ObjectStorage();
        $objectStorage->attach(new \stdClass());

        $validator = new FileNameValidator();
        $validator->validate($objectStorage);
    }
}
