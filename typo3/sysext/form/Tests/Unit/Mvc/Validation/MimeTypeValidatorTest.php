<?php
namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Validation;

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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Form\Mvc\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\CMS\Form\Mvc\Validation\MimeTypeValidator;

/**
 * Test case
 */
class MimeTypeValidatorTest extends UnitTestCase
{

    /**
     * @test
     */
    public function MimeTypeValidatorThrowsExceptionIfAllowedMimeTypesOptionIsString()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1471713296);

        $options = ['allowedMimeTypes' => ''];
        $validator = $this->getMockBuilder(MimeTypeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs(['options' => $options])
            ->getMock();

        $validator->validate(true);
    }

    /**
     * @test
     */
    public function MimeTypeValidatorThrowsExceptionIfAllowedMimeTypesOptionIsEmptyArray()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1471713296);

        $options = ['allowedMimeTypes' => []];
        $validator = $this->getMockBuilder(MimeTypeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs(['options' => $options])
            ->getMock();

        $validator->validate(true);
    }

    /**
     * @test
     */
    public function MimeTypeValidatorReturnsTrueIfFileResourceIsNotAllowedMimeType()
    {
        $options = ['allowedMimeTypes' => ['image/jpeg']];
        $validator = $this->getMockBuilder(MimeTypeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs(['options' => $options])
            ->getMock();

        $mockedStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $file = new File(['identifier' => '/foo', 'mime_type' => 'image/png'], $mockedStorage);
        $this->assertTrue($validator->validate($file)->hasErrors());
    }

    /**
     * @test
     */
    public function MimeTypeValidatorReturnsFalseIfInputIsEmptyString()
    {
        $options = ['allowedMimeTypes' => ['fake']];
        $validator = $this->getMockBuilder(MimeTypeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs(['options' => $options])
            ->getMock();

        $this->assertFalse($validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function MimeTypeValidatorReturnsTrueIfInputIsNoFileResource()
    {
        $options = ['allowedMimeTypes' => ['fake']];
        $validator = $this->getMockBuilder(MimeTypeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs(['options' => $options])
            ->getMock();

        $this->assertTrue($validator->validate('string')->hasErrors());
    }
}
