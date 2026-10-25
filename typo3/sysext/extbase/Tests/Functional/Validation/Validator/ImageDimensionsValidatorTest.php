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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\CMS\Extbase\Validation\Validator\ImageDimensionsValidator;

final class ImageDimensionsValidatorTest extends AbstractUploadedFileTestCase
{
    public static function dimensionBoundaryDataProvider(): array
    {
        return [
            'exact minimum width' => [['minWidth' => 900], []],
            'width below minimum' => [['minWidth' => 901], [1715964042]],
            'exact maximum width' => [['maxWidth' => 900], []],
            'width above maximum' => [['maxWidth' => 899], [1715964044]],
            'exact minimum height' => [['minHeight' => 382], []],
            'height below minimum' => [['minHeight' => 383], [1715964043]],
            'exact maximum height' => [['maxHeight' => 382], []],
            'height above maximum' => [['maxHeight' => 381], [1715964045]],
        ];
    }

    #[DataProvider('dimensionBoundaryDataProvider')]
    #[Test]
    public function validatesInclusiveDimensionBoundaries(array $options, array $expectedErrorCodes): void
    {
        $validator = new ImageDimensionsValidator();
        $validator->setOptions($options);
        $result = $validator->validate($this->getUploadedFileImage());

        self::assertSame($expectedErrorCodes, array_map(static fn(Error $error): int => $error->getCode(), $result->getErrors()));
    }

    #[Test]
    public function validatorThrowsExceptionIfMinWidthIsGreaterThanMaxWidth(): void
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1716008127);

        $options = ['minWidth' => 500, 'maxWidth' => 400];
        $validator = new ImageDimensionsValidator();
        $validator->setOptions($options);

        $uploadedFileImage = $this->getUploadedFileImage();
        $validator->validate($uploadedFileImage)->getFirstError()->getCode();
    }

    #[Test]
    public function validatorThrowsExceptionIfMinHeightIsGreaterThanMaxHeight(): void
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1716008128);

        $options = ['minHeight' => 500, 'maxHeight' => 400];
        $validator = new ImageDimensionsValidator();
        $validator->setOptions($options);

        $uploadedFileImage = $this->getUploadedFileImage();
        $validator->validate($uploadedFileImage)->getFirstError()->getCode();
    }

    #[Test]
    public function validatorHasExpectedErrorIfWidthNotFulfilled(): void
    {
        $options = ['width' => 500];
        $validator = new ImageDimensionsValidator();
        $validator->setOptions($options);

        $uploadedFileImage = $this->getUploadedFileImage();
        self::assertSame(1715964040, $validator->validate($uploadedFileImage)->getFirstError()->getCode());
    }

    #[Test]
    public function validatorHasExpectedErrorIfHeightNotFulfilled(): void
    {
        $options = ['height' => 500];
        $validator = new ImageDimensionsValidator();
        $validator->setOptions($options);

        $uploadedFileImage = $this->getUploadedFileImage();
        self::assertSame(1715964041, $validator->validate($uploadedFileImage)->getFirstError()->getCode());
    }

    #[Test]
    public function validatorHasExpectedErrorIfMinimumWidthNotFulfilled(): void
    {
        $options = ['minWidth' => 1000];
        $validator = new ImageDimensionsValidator();
        $validator->setOptions($options);

        $uploadedFileImage = $this->getUploadedFileImage();
        self::assertSame(1715964042, $validator->validate($uploadedFileImage)->getFirstError()->getCode());
    }

    #[Test]
    public function validatorHasExpectedErrorIfMinimumHeightNotFulfilled(): void
    {
        $options = ['minHeight' => 1000];
        $validator = new ImageDimensionsValidator();
        $validator->setOptions($options);

        $uploadedFileImage = $this->getUploadedFileImage();
        self::assertSame(1715964043, $validator->validate($uploadedFileImage)->getFirstError()->getCode());
    }

    #[Test]
    public function validatorHasExpectedErrorIfMaximumWidthNotFulfilled(): void
    {
        $options = ['maxWidth' => 100];
        $validator = new ImageDimensionsValidator();
        $validator->setOptions($options);

        $uploadedFileImage = $this->getUploadedFileImage();
        self::assertSame(1715964044, $validator->validate($uploadedFileImage)->getFirstError()->getCode());
    }

    #[Test]
    public function validatorHasExpectedErrorIfMaximumHeightNotFulfilled(): void
    {
        $options = ['maxHeight' => 100];
        $validator = new ImageDimensionsValidator();
        $validator->setOptions($options);

        $uploadedFileImage = $this->getUploadedFileImage();
        self::assertSame(1715964045, $validator->validate($uploadedFileImage)->getFirstError()->getCode());
    }

    #[Test]
    public function validatorHasErrorsForObjectStorageWithInvalidUploadedFile(): void
    {
        $options = ['maxHeight' => 100];
        $validator = new ImageDimensionsValidator();
        $validator->setOptions($options);

        $uploadedFileImage = $this->getUploadedFileImage();

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($uploadedFileImage);

        $validationResult = $validator->validate($objectStorage);
        self::assertTrue($validationResult->hasErrors());
        self::assertTrue($validationResult->forProperty('0')->hasErrors());
    }

    #[Test]
    public function validatorHasNoErrorsIfImageDimensionsValid(): void
    {
        $options = ['minWidth' => 100, 'maxWidth' => 1000, 'minHeight' => 100, 'maxHeight' => 400];
        $validator = new ImageDimensionsValidator();
        $validator->setOptions($options);

        $uploadedFileImage = $this->getUploadedFileImage();
        self::assertFalse($validator->validate($uploadedFileImage)->hasErrors());
    }

    #[Test]
    public function validatorThrowsExceptionForNonUploadedFileObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1712057926);

        $subject = new ImageDimensionsValidator();
        $subject->validate('file.txt');
    }

    #[Test]
    public function validatorThrowsExceptionForObjectStorageWithNonUploadedFileObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1722763902);

        $objectStorage = new ObjectStorage();
        $objectStorage->attach(new \stdClass());

        $subject = new ImageDimensionsValidator();
        $subject->validate($objectStorage);
    }

    private function getUploadedFileImage(): UploadedFile
    {
        $imageFile = GeneralUtility::getFileAbsFileName('EXT:extbase/Tests/Functional/Validation/Fixtures/image-900x382.png');
        return new UploadedFile($imageFile, 16473, UPLOAD_ERR_OK, 'image-900x382.png');
    }
}
