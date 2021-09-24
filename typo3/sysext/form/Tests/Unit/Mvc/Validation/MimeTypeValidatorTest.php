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

namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Validation;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Form\Mvc\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\CMS\Form\Mvc\Validation\MimeTypeValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class MimeTypeValidatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function MimeTypeValidatorThrowsExceptionIfAllowedMimeTypesOptionIsString(): void
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1471713296);

        $options = ['allowedMimeTypes' => ''];
        $validator = $this->getMockBuilder(MimeTypeValidator::class)
            ->onlyMethods(['translateErrorMessage'])
            ->setConstructorArgs(['options' => $options])
            ->getMock();

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
        $validator = $this->getMockBuilder(MimeTypeValidator::class)
            ->onlyMethods(['translateErrorMessage'])
            ->setConstructorArgs(['options' => $options])
            ->getMock();

        $validator->validate(true);
    }

    /**
     * @test
     */
    public function MimeTypeValidatorReturnsTrueIfFileResourceIsNotAllowedMimeType(): void
    {
        $options = ['allowedMimeTypes' => ['image/jpeg']];
        $validator = $this->getMockBuilder(MimeTypeValidator::class)
            ->onlyMethods(['translateErrorMessage'])
            ->setConstructorArgs(['options' => $options])
            ->getMock();

        $mockedStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $file = new File(['name' => 'foo', 'identifier' => '/foo', 'mime_type' => 'image/png'], $mockedStorage);
        self::assertTrue($validator->validate($file)->hasErrors());
    }

    /**
     * @test
     */
    public function MimeTypeValidatorReturnsFalseIfInputIsEmptyString(): void
    {
        $options = ['allowedMimeTypes' => ['fake']];
        $validator = $this->getMockBuilder(MimeTypeValidator::class)
            ->onlyMethods(['translateErrorMessage'])
            ->setConstructorArgs(['options' => $options])
            ->getMock();

        self::assertFalse($validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function MimeTypeValidatorReturnsTrueIfInputIsNoFileResource(): void
    {
        $options = ['allowedMimeTypes' => ['fake']];
        $validator = $this->getMockBuilder(MimeTypeValidator::class)
            ->onlyMethods(['translateErrorMessage'])
            ->setConstructorArgs(['options' => $options])
            ->getMock();

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
     * @param string $fileName
     * @param string $fileMimeType
     * @param array $allowedMimeTypes
     * @param bool $isValid
     * @test
     * @dataProvider fileExtensionMatchesMimeTypesDataProvider
     */
    public function fileExtensionMatchesMimeTypes(string $fileName, string $fileMimeType, array $allowedMimeTypes, bool $isValid): void
    {
        $options = ['allowedMimeTypes' => $allowedMimeTypes];
        $validator = $this->getMockBuilder(MimeTypeValidator::class)
            ->onlyMethods(['translateErrorMessage'])
            ->setConstructorArgs(['options' => $options])
            ->getMock();
        $mockedStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $file = new File([
            'name' => $fileName,
            'identifier' => '/folder/' . $fileName,
            'mime_type' => $fileMimeType,
        ], $mockedStorage);
        $result = $validator->validate($file);
        self::assertSame($isValid, !$result->hasErrors());
    }
}
