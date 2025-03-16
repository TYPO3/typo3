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
use TYPO3\CMS\Form\Mvc\Validation\DateRangeValidator;
use TYPO3\CMS\Form\Mvc\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DateRangeValidatorTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    #[Test]
    public function validateOptionsThrowsExceptionIfMinimumOptionIsInvalid(): void
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1521293813);
        $options = ['minimum' => '1972-01', 'maximum' => ''];
        $validator = new DateRangeValidator();
        $validator->setOptions($options);
        $validator->validate(true);
    }

    #[Test]
    public function validateOptionsThrowsExceptionIfMaximumOptionIsInvalid(): void
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->expectExceptionCode(1521293814);
        $options = ['minimum' => '', 'maximum' => '1972-01'];
        $validator = new DateRangeValidator();
        $validator->setOptions($options);
        $validator->validate(true);
    }

    #[Test]
    public function dateRangeValidatorReturnsTrueIfInputIsNoDateTime(): void
    {
        $options = ['minimum' => '2018-03-17', 'maximum' => '2018-03-17'];
        $validator = new DateRangeValidator();
        $validator->setOptions($options);
        self::assertTrue($validator->validate(true)->hasErrors());
    }

    #[Test]
    public function dateRangeValidatorReturnsTrueIfInputIsLowerThanMinimumOption(): void
    {
        $input = \DateTime::createFromFormat('Y-m-d', '2018-03-17');
        $options = ['minimum' => '2018-03-18', 'maximum' => ''];
        $validator = new DateRangeValidator();
        $validator->setOptions($options);
        $result = $validator->validate($input);
        $firstError = $result->getFirstError();
        self::assertEquals(1521293687, $firstError->getCode());
        self::assertTrue($result->hasErrors());
    }

    #[Test]
    public function dateRangeValidatorReturnsFalseIfInputIsEqualsMinimumOption(): void
    {
        $input = \DateTime::createFromFormat('Y-m-d', '2018-03-18');
        $options = ['minimum' => '2018-03-18', 'maximum' => ''];
        $validator = new DateRangeValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function dateRangeValidatorReturnsFalseIfInputIsGreaterThanMinimumOption(): void
    {
        $input = \DateTime::createFromFormat('Y-m-d', '2018-03-19');
        $options = ['minimum' => '2018-03-18', 'maximum' => ''];
        $validator = new DateRangeValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function dateRangeValidatorReturnsFalseIfInputIsLowerThanMaximumOption(): void
    {
        $input = \DateTime::createFromFormat('Y-m-d', '2018-03-17');
        $options = ['maximum' => '2018-03-18'];
        $validator = new DateRangeValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function dateRangeValidatorReturnsFalseIfInputIsEqualsMaximumOption(): void
    {
        $input = \DateTime::createFromFormat('Y-m-d', '2018-03-18');
        $options = ['maximum' => '2018-03-18'];
        $validator = new DateRangeValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function dateRangeValidatorReturnsTrueIfInputIsGreaterThanMaximumOption(): void
    {
        $input = \DateTime::createFromFormat('Y-m-d', '2018-03-19');
        $options = ['maximum' => '2018-03-18'];
        $validator = new DateRangeValidator();
        $validator->setOptions($options);
        $result = $validator->validate($input);
        $firstError = $result->getFirstError();
        self::assertEquals(1521293686, $firstError->getCode());
        self::assertTrue($result->hasErrors());
    }
}
