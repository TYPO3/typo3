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
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\CMS\Extbase\Validation\Validator\FileSizeValidator;

final class FileSizeValidatorTest extends AbstractUploadedFileTestCase
{
    public static function sizeBoundaryDataProvider(): array
    {
        return [
            'one byte below minimum' => [9, 1708595754],
            'exact minimum' => [10, null],
            'exact maximum' => [20, null],
            'one byte above maximum' => [21, 1708595755],
        ];
    }

    #[DataProvider('sizeBoundaryDataProvider')]
    #[Test]
    public function validatesExactByteBoundaries(int $bytes, ?int $expectedErrorCode): void
    {
        $validator = new FileSizeValidator();
        $validator->setOptions(['minimum' => '10B', 'maximum' => '20B']);
        $path = $this->createTestFile('size-boundary.txt', str_repeat('x', $bytes));
        $result = $validator->validate(new UploadedFile($path, $bytes, UPLOAD_ERR_OK, 'file.txt'));

        self::assertSame(
            $expectedErrorCode === null ? [] : [$expectedErrorCode],
            array_map(static fn(Error $error): int => $error->getCode(), $result->getErrors())
        );
    }

    public static function collectionSizeErrorDataProvider(): array
    {
        return [
            'below minimum' => [9, 1708595754],
            'above maximum' => [21, 1708595755],
        ];
    }

    #[DataProvider('collectionSizeErrorDataProvider')]
    #[Test]
    public function collectionErrorsIdentifyTheSizeRuleAndProperty(int $bytes, int $expectedErrorCode): void
    {
        $validator = new FileSizeValidator();
        $validator->setOptions(['minimum' => '10B', 'maximum' => '20B']);
        $path = $this->createTestFile('collection-boundary.txt', str_repeat('x', $bytes));
        $files = new ObjectStorage();
        $files->attach(new UploadedFile($path, $bytes, UPLOAD_ERR_OK, 'file.txt'));
        $result = $validator->validate($files);

        self::assertSame(['0'], array_map('strval', array_keys($result->getFlattenedErrors())));
        self::assertSame([$expectedErrorCode], array_map(static fn(Error $error): int => $error->getCode(), $result->forProperty('0')->getErrors()));
    }

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
    public function validatorHasNoErrorsWithDefaultOptions(): void
    {
        $validator = new FileSizeValidator();
        $validator->setOptions([]);

        $testFilename = $this->createTestFile('default_options_testfile.txt', 'TYPO3 - Inspiring People To Share');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');
        self::assertFalse($validator->validate($uploadedFile)->hasErrors());
    }

    #[Test]
    #[IgnoreDeprecations]
    public function validatorHasNoErrorsIfUploadedFileSizeIsWithinDecimalMaximum(): void
    {
        $this->expectUserDeprecationMessageMatches('/Using decimal values like "1.5K" for the option "maximum"/');
        $options = ['minimum' => '0B', 'maximum' => '1.5K'];
        $validator = new FileSizeValidator();
        $validator->setOptions($options);

        $testFilename = $this->createTestFile('decimal_maximum_testfile.txt', str_repeat('a', 1100));
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');
        self::assertFalse($validator->validate($uploadedFile)->hasErrors());
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

    #[Test]
    public function validatorUsesCustomByteSizeUnitsString(): void
    {
        $options = ['minimum' => '10B', 'maximum' => '20B', 'byteSizeUnits' => ' b| kb| mb| gb'];
        $validator = new FileSizeValidator();
        $validator->setOptions($options);

        $testFilename = $this->createTestFile('too_small_testfile.txt', 'TYPO3');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');
        $validationError = $validator->validate($uploadedFile)->getFirstError()->getMessage();
        self::assertEquals('The file must be larger than 10 b in size.', $validationError);
    }
}
