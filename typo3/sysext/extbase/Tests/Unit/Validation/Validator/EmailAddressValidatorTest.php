<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

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

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class EmailAddressValidatorTest extends UnitTestCase
{
    /**
     * Data provider with valid email addresses
     *
     * @return array
     */
    public function validAddresses()
    {
        return [
            ['andreas.foerthner@netlogix.de'],
            ['user@localhost.localdomain'],
            ['info@guggenheim.museum'],
            ['just@test.invalid'],
            ['just+spam@test.de'],
        ];
    }

    /**
     * @test
     * @dataProvider validAddresses
     * @param mixed $address
     */
    public function emailAddressValidatorReturnsNoErrorsForAValidEmailAddress($address)
    {
        /** @var \TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(\TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->getMock();
        self::assertFalse($subject->validate($address)->hasErrors());
    }

    /**
     * Data provider with invalid email addresses
     *
     * @return array
     */
    public function invalidAddresses()
    {
        return [
            ['andreas.foerthner@'],
            ['@typo3.org'],
            ['someone@typo3.'],
            ['local@192.168.2'],
            ['local@192.168.270.1'],
            ['foo@bar.com' . "\0"],
            ['foo@bar.org' . chr(10)],
            ['andreas@foerthner@example.com'],
            ['some@one.net ']
        ];
    }

    /**
     * @test
     * @dataProvider invalidAddresses
     * @param mixed $address
     */
    public function emailAddressValidatorReturnsFalseForAnInvalidEmailAddress($address)
    {
        /** @var \TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(\TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->getMock();
        self::assertTrue($subject->validate($address)->hasErrors());
    }

    /**
     * @test
     */
    public function emailValidatorCreatesTheCorrectErrorForAnInvalidEmailAddress()
    {
        /** @var \TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(\TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->getMock();
        self::assertEquals(1, count($subject->validate('notAValidMail@Address')->getErrors()));
    }
}
