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
use TYPO3\CMS\Form\Mvc\Validation\FileSizeValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FileSizeValidatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function FileSizeValidatorThrowsExceptionIfMinimumOptionIsInvalid()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1505304205);

        $options = ['minimum' => '0', 'maximum' => '1B'];
        $validator = $this->getMockBuilder(FileSizeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs(['options' => $options])
            ->getMock();

        $validator->validate(true);
    }

    /**
     * @test
     */
    public function FileSizeValidatorThrowsExceptionIfMaximumOptionIsInvalid()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1505304206);

        $options = ['minimum' => '0B', 'maximum' => '1'];
        $validator = $this->getMockBuilder(FileSizeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs(['options' => $options])
            ->getMock();

        $validator->validate(true);
    }

    /**
     * @test
     */
    public function FileSizeValidatorHasErrorsIfFileResourceSizeIsToSmall()
    {
        $options = ['minimum' => '1M', 'maximum' => '10M'];
        $validator = $this->getMockBuilder(FileSizeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs(['options' => $options])
            ->getMock();

        $mockedStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $file = new File(['identifier' => '/foo', 'name'=> 'bar.txt', 'size' => '1'], $mockedStorage);
        self::assertTrue($validator->validate($file)->hasErrors());
    }

    /**
     * @test
     */
    public function FileSizeValidatorHasErrorsIfFileResourceSizeIsToBig()
    {
        $options = ['minimum' => '1M', 'maximum' => '1M'];
        $validator = $this->getMockBuilder(FileSizeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs(['options' => $options])
            ->getMock();

        $mockedStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $file = new File(['identifier' => '/foo', 'name' => 'bar.txt', 'size' => '1048577'], $mockedStorage);
        self::assertTrue($validator->validate($file)->hasErrors());
    }

    /**
     * @test
     */
    public function FileSizeValidatorHasNoErrorsIfInputIsEmptyString()
    {
        $options = ['minimum' => '0B', 'maximum' => '1M'];
        $validator = $this->getMockBuilder(FileSizeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs(['options' => $options])
            ->getMock();

        self::assertFalse($validator->validate('')->hasErrors());
    }

    /**
     * @test
     */
    public function FileSizeValidatorHasErrorsIfInputIsNoFileResource()
    {
        $options = ['minimum' => '0B', 'maximum' => '1M'];
        $validator = $this->getMockBuilder(FileSizeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs(['options' => $options])
            ->getMock();

        self::assertTrue($validator->validate('string')->hasErrors());
    }
}
