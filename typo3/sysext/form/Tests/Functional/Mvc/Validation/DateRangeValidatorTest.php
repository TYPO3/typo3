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
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Form\Mvc\Validation\DateRangeValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DateRangeValidatorTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'form',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('en');
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    private function createValidator(array $options = []): DateRangeValidator
    {
        $validator = new DateRangeValidator();
        $validator->setLogger(new NullLogger());
        $validator->setOptions($options);
        return $validator;
    }

    #[Test]
    public function invalidMinimumOptionReturnsValidationError(): void
    {
        $validator = $this->createValidator(['minimum' => 'not-a-date', 'maximum' => '']);
        $result = $validator->validate(new \DateTime());
        self::assertTrue($result->hasErrors());
        self::assertEquals(1748345955, $result->getFirstError()->getCode());
    }

    #[Test]
    public function invalidMaximumOptionReturnsValidationError(): void
    {
        $validator = $this->createValidator(['minimum' => '', 'maximum' => 'not-a-date']);
        $result = $validator->validate(new \DateTime());
        self::assertTrue($result->hasErrors());
        self::assertEquals(1748345955, $result->getFirstError()->getCode());
    }

    #[Test]
    public function dateRangeValidatorReturnsTrueIfInputIsNoDateTime(): void
    {
        $validator = $this->createValidator(['minimum' => '2018-03-17', 'maximum' => '2018-03-17']);
        self::assertTrue($validator->validate(true)->hasErrors());
    }

    #[Test]
    public function dateRangeValidatorReturnsTrueIfInputIsLowerThanMinimumOption(): void
    {
        $input = \DateTime::createFromFormat('Y-m-d', '2018-03-17');
        $validator = $this->createValidator(['minimum' => '2018-03-18', 'maximum' => '']);
        $result = $validator->validate($input);
        $firstError = $result->getFirstError();
        self::assertEquals(1521293687, $firstError->getCode());
        self::assertTrue($result->hasErrors());
    }

    #[Test]
    public function dateRangeValidatorReturnsFalseIfInputIsEqualsMinimumOption(): void
    {
        $input = \DateTime::createFromFormat('Y-m-d', '2018-03-18');
        $validator = $this->createValidator(['minimum' => '2018-03-18', 'maximum' => '']);
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function dateRangeValidatorReturnsFalseIfInputIsGreaterThanMinimumOption(): void
    {
        $input = \DateTime::createFromFormat('Y-m-d', '2018-03-19');
        $validator = $this->createValidator(['minimum' => '2018-03-18', 'maximum' => '']);
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function dateRangeValidatorReturnsFalseIfInputIsLowerThanMaximumOption(): void
    {
        $input = \DateTime::createFromFormat('Y-m-d', '2018-03-17');
        $validator = $this->createValidator(['maximum' => '2018-03-18']);
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function dateRangeValidatorReturnsFalseIfInputIsEqualsMaximumOption(): void
    {
        $input = \DateTime::createFromFormat('Y-m-d', '2018-03-18');
        $validator = $this->createValidator(['maximum' => '2018-03-18']);
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function dateRangeValidatorReturnsTrueIfInputIsGreaterThanMaximumOption(): void
    {
        $input = \DateTime::createFromFormat('Y-m-d', '2018-03-19');
        $validator = $this->createValidator(['maximum' => '2018-03-18']);
        $result = $validator->validate($input);
        $firstError = $result->getFirstError();
        self::assertEquals(1521293686, $firstError->getCode());
        self::assertTrue($result->hasErrors());
    }

    #[Test]
    public function relativeMinimumTodayRejectsPastDate(): void
    {
        $input = new \DateTime('yesterday');
        $validator = $this->createValidator(['minimum' => 'today']);
        $result = $validator->validate($input);
        self::assertTrue($result->hasErrors());
        self::assertEquals(1521293687, $result->getFirstError()->getCode());
    }

    #[Test]
    public function relativeMinimumTodayAcceptsTodayDate(): void
    {
        $input = new \DateTime('today');
        $validator = $this->createValidator(['minimum' => 'today']);
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function relativeMaximumTodayRejectsFutureDate(): void
    {
        $input = new \DateTime('tomorrow');
        $validator = $this->createValidator(['maximum' => 'today']);
        $result = $validator->validate($input);
        self::assertTrue($result->hasErrors());
        self::assertEquals(1521293686, $result->getFirstError()->getCode());
    }

    #[Test]
    public function relativeMaximumTodayAcceptsTodayDate(): void
    {
        $input = new \DateTime('today');
        $validator = $this->createValidator(['maximum' => 'today']);
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function relativeMaximumMinus18YearsRejectsRecentDate(): void
    {
        $input = new \DateTime('-1 year');
        $validator = $this->createValidator(['maximum' => '-18 years']);
        $result = $validator->validate($input);
        self::assertTrue($result->hasErrors());
    }

    #[Test]
    public function relativeMaximumMinus18YearsAcceptsOldEnoughDate(): void
    {
        $input = new \DateTime('-20 years');
        $validator = $this->createValidator(['maximum' => '-18 years']);
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function relativeMinimumWithPositiveOffsetAcceptsFutureDate(): void
    {
        $input = new \DateTime('+2 months');
        $validator = $this->createValidator(['minimum' => '+1 month']);
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function mixedAbsoluteMinimumAndRelativeMaximumWorks(): void
    {
        $input = new \DateTime('today');
        $validator = $this->createValidator(['minimum' => '2020-01-01', 'maximum' => 'today']);
        self::assertFalse($validator->validate($input)->hasErrors());
    }

    #[Test]
    public function nonsenseRelativeExpressionReturnsValidationError(): void
    {
        $validator = $this->createValidator(['minimum' => 'foobar', 'maximum' => '']);
        $result = $validator->validate(new \DateTime());
        self::assertTrue($result->hasErrors());
        self::assertEquals(1748345955, $result->getFirstError()->getCode());
    }

    #[Test]
    public function relativeYesterdayExpressionIsAccepted(): void
    {
        $input = new \DateTime('-2 days');
        $validator = $this->createValidator(['minimum' => 'yesterday']);
        $result = $validator->validate($input);
        self::assertTrue($result->hasErrors());
    }

    #[Test]
    public function relativeTomorrowExpressionIsAccepted(): void
    {
        $input = new \DateTime('+2 days');
        $validator = $this->createValidator(['maximum' => 'tomorrow']);
        $result = $validator->validate($input);
        self::assertTrue($result->hasErrors());
    }

    #[Test]
    public function relativeFirstSundayOfNextMonthExpressionIsAccepted(): void
    {
        $input = new \DateTime('+2 months');
        $validator = $this->createValidator(['maximum' => 'first sunday of next month']);
        $result = $validator->validate($input);
        self::assertTrue($result->hasErrors());
    }
}
