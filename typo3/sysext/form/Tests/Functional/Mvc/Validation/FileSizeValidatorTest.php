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

namespace TYPO3\CMS\Form\Tests\Functional\Mvc\Validation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Form\Mvc\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\CMS\Form\Mvc\Validation\FileSizeValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileSizeValidatorTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'form',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    #[Test]
    public function fileSizeValidatorThrowsExceptionIfMinimumOptionIsInvalid(): void
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1505304205);
        $options = ['minimum' => '0', 'maximum' => '1B'];
        $validator = new FileSizeValidator();
        $validator->setOptions($options);
        $validator->validate(true);
    }

    #[Test]
    public function fileSizeValidatorThrowsExceptionIfMaximumOptionIsInvalid(): void
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1505304206);
        $options = ['minimum' => '0B', 'maximum' => '1'];
        $validator = new FileSizeValidator();
        $validator->setOptions($options);
        $validator->validate(true);
    }

    #[Test]
    public function fileSizeValidatorHasErrorsIfFileResourceSizeIsToSmall(): void
    {
        $options = ['minimum' => '1M', 'maximum' => '10M'];
        $validator = new FileSizeValidator();
        $validator->setOptions($options);
        $mockedStorage = $this->getMockBuilder(ResourceStorage::class)->disableOriginalConstructor()->getMock();
        $file = new File(['identifier' => '/foo', 'name' => 'bar.txt', 'size' => '1'], $mockedStorage);
        self::assertTrue($validator->validate($file)->hasErrors());
    }

    #[Test]
    public function fileSizeValidatorHasErrorsIfFileResourceSizeIsToBig(): void
    {
        $options = ['minimum' => '1M', 'maximum' => '1M'];
        $validator = new FileSizeValidator();
        $validator->setOptions($options);
        $mockedStorage = $this->getMockBuilder(ResourceStorage::class)->disableOriginalConstructor()->getMock();
        $file = new File(['identifier' => '/foo', 'name' => 'bar.txt', 'size' => '1048577'], $mockedStorage);
        self::assertTrue($validator->validate($file)->hasErrors());
    }

    #[Test]
    public function fileSizeValidatorHasNoErrorsIfInputIsEmptyString(): void
    {
        $options = ['minimum' => '0B', 'maximum' => '1M'];
        $validator = new FileSizeValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate('')->hasErrors());
    }

    #[Test]
    public function fileSizeValidatorHasErrorsIfInputIsNoFileResource(): void
    {
        $options = ['minimum' => '0B', 'maximum' => '1M'];
        $validator = new FileSizeValidator();
        $validator->setOptions($options);
        self::assertTrue($validator->validate('string')->hasErrors());
    }
}
