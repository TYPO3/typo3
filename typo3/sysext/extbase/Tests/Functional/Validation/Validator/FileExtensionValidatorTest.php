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
}
