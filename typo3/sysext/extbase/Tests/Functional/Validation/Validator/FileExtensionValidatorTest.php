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
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\CMS\Extbase\Validation\Validator\FileExtensionValidator;

final class FileExtensionValidatorTest extends AbstractUploadedFileTestCase
{
    public static function allowedFileNamesDataProvider(): array
    {
        return [
            'lowercase extension' => ['photo.jpg'],
            'uppercase extension' => ['photo.JPG'],
            'mixed case extension' => ['photo.Jpg'],
        ];
    }

    #[DataProvider('allowedFileNamesDataProvider')]
    #[Test]
    public function validatorHasNoErrorsForAllowedFileExtensionRegardlessOfCase(string $fileName): void
    {
        $validator = new FileExtensionValidator();
        $validator->setOptions(['allowedFileExtensions' => ['jpg']]);
        $uploadedFile = new UploadedFile('/tmp/testfile', 100, UPLOAD_ERR_OK, $fileName);
        self::assertFalse($validator->validate($uploadedFile)->hasErrors());
    }

    #[Test]
    public function validatorHasErrorsForFileExtensionThatIsNotAllowed(): void
    {
        $validator = new FileExtensionValidator();
        $validator->setOptions(['allowedFileExtensions' => ['jpg']]);
        $uploadedFile = new UploadedFile('/tmp/testfile', 100, UPLOAD_ERR_OK, 'document.PDF');
        $result = $validator->validate($uploadedFile);
        self::assertTrue($result->hasErrors());
        self::assertSame("The file extension 'PDF' is not allowed.", $result->getFirstError()->getMessage());
    }

    #[Test]
    public function validatorPreservesBatchErrorIndexesAndCustomMessage(): void
    {
        $validator = new FileExtensionValidator();
        $validator->setOptions([
            'allowedFileExtensions' => ['JPG'],
            'notAllowedMessage' => 'Disallowed extension',
        ]);
        $files = new ObjectStorage();
        $files->attach(new UploadedFile('/tmp/first', 1, UPLOAD_ERR_OK, 'first.jpg'));
        $files->attach(new UploadedFile('/tmp/second', 1, UPLOAD_ERR_OK, 'second.txt'));
        $files->attach(new UploadedFile('/tmp/third', 1, UPLOAD_ERR_OK, 'third.gif'));

        $result = $validator->validate($files);

        self::assertSame([], $result->getErrors());
        self::assertFalse($result->forProperty('0')->hasErrors());
        foreach (['1', '2'] as $index) {
            $errors = $result->forProperty($index)->getErrors();
            self::assertCount(1, $errors);
            self::assertSame(1754043401, $errors[0]->getCode());
            self::assertSame('Disallowed extension', $errors[0]->getMessage());
        }
    }

    #[Test]
    public function validatorUsesUpdatedOptionsOnTheNextCall(): void
    {
        $validator = new FileExtensionValidator();
        $file = new UploadedFile('/tmp/file', 1, UPLOAD_ERR_OK, 'file.txt');
        $validator->setOptions(['allowedFileExtensions' => ['txt']]);
        self::assertFalse($validator->validate($file)->hasErrors());

        $validator->setOptions(['allowedFileExtensions' => ['jpg']]);
        self::assertTrue($validator->validate($file)->hasErrors());
    }

    #[Test]
    public function validatorMergesStorageDefaultsAndRefreshesThemOnTheNextCall(): void
    {
        $originalConfiguration = $GLOBALS['TYPO3_CONF_VARS']['SYS'];
        try {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'] = 'TXT';
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'] = 'jpg';
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['miscfile_ext'] = 'pdf';
            $validator = new FileExtensionValidator();
            $validator->setOptions(['allowedFileExtensions' => ['csv'], 'useStorageDefaults' => true]);
            $files = new ObjectStorage();
            foreach (['txt', 'jpg', 'pdf', 'csv'] as $extension) {
                $files->attach(new UploadedFile('/tmp/' . $extension, 1, UPLOAD_ERR_OK, 'file.' . $extension));
            }
            self::assertFalse($validator->validate($files)->hasErrors());

            $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'] = 'md';
            $result = $validator->validate($files);
            self::assertTrue($result->forProperty('0')->hasErrors());
            self::assertFalse($result->forProperty('1')->hasErrors());
            self::assertFalse($result->forProperty('2')->hasErrors());
            self::assertFalse($result->forProperty('3')->hasErrors());
        } finally {
            $GLOBALS['TYPO3_CONF_VARS']['SYS'] = $originalConfiguration;
        }
    }

    #[Test]
    public function validatorRejectsMissingAllowedExtensions(): void
    {
        $validator = new FileExtensionValidator();
        $validator->setOptions([]);
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1754043328);
        $validator->validate(new UploadedFile('/tmp/file', 1, UPLOAD_ERR_OK, 'file.txt'));
    }

    #[Test]
    public function validatorRejectsObjectStorageContainingOtherObjects(): void
    {
        $validator = new FileExtensionValidator();
        $validator->setOptions(['allowedFileExtensions' => ['txt']]);
        $files = new ObjectStorage();
        $files->attach(new \stdClass());
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1722763902);
        $validator->validate($files);
    }
}
