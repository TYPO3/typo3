<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Saltedpasswords\Tests\Unit\Salt;

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

use TYPO3\CMS\Saltedpasswords\Salt\BcryptSalt;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class BcryptSaltTest extends UnitTestCase
{
    /**
     * @var BcryptSalt
     */
    protected $subject;

    /**
     * Sets up the fixtures for this testcase.
     */
    protected function setUp()
    {
        $this->subject = new BcryptSalt();
        // Set a low cost to speed up tests
        $this->subject->setOptions([
            'cost' => 10,
        ]);
    }

    /**
     * @test
     */
    public function getOptionsReturnsPreviouslySetOptions()
    {
        $options = [
            'cost' => 11,
        ];
        $this->subject->setOptions($options);
        $this->assertSame($this->subject->getOptions(), $options);
    }

    /**
     * @test
     */
    public function setOptionsThrowsExceptionOnTooLowCostValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1526042084);
        $this->subject->setOptions(['cost' => 9]);
    }

    /**
     * @test
     */
    public function getHashedPasswordReturnsNullOnEmptyPassword()
    {
        $this->assertNull($this->subject->getHashedPassword(''));
    }

    /**
     * @test
     */
    public function getHashedPasswordReturnsString()
    {
        $hash = $this->subject->getHashedPassword('password');
        $this->assertNotNull($hash);
        $this->assertTrue(is_string($hash));
    }

    /**
     * @test
     */
    public function isValidSaltedPwValidatesHastCreatedByGetHashedPassword()
    {
        $hash = $this->subject->getHashedPassword('password');
        $this->assertTrue($this->subject->isValidSaltedPW($hash));
    }

    /**
     * Tests authentication procedure with alphabet characters.
     *
     * @test
     */
    public function checkPasswordReturnsTrueForHashedPasswordWithValidAlphaCharClassPassword()
    {
        $password = 'aEjOtY';
        $hash = $this->subject->getHashedPassword($password);
        $this->assertTrue($this->subject->checkPassword($password, $hash));
    }

    /**
     * Tests authentication procedure with numeric characters.
     *
     * @test
     */
    public function checkPasswordReturnsTrueForHashedPasswordWithValidNumericCharClassPassword()
    {
        $password = '01369';
        $hash = $this->subject->getHashedPassword($password);
        $this->assertTrue($this->subject->checkPassword($password, $hash));
    }

    /**
     * Tests authentication procedure with US-ASCII special characters.
     *
     * @test
     */
    public function checkPasswordReturnsTrueForHashedPasswordWithValidAsciiSpecialCharClassPassword()
    {
        $password = ' !"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~';
        $hash = $this->subject->getHashedPassword($password);
        $this->assertTrue($this->subject->checkPassword($password, $hash));
    }

    /**
     * Tests authentication procedure with latin1 special characters.
     *
     * @test
     */
    public function checkPasswordReturnsTrueForHashedPasswordWithValidLatin1SpecialCharClassPassword()
    {
        $password = '';
        for ($i = 160; $i <= 191; $i++) {
            $password .= chr($i);
        }
        $password .= chr(215) . chr(247);
        $hash = $this->subject->getHashedPassword($password);
        $this->assertTrue($this->subject->checkPassword($password, $hash));
    }

    /**
     * Tests authentication procedure with latin1 umlauts.
     *
     * @test
     */
    public function checkPasswordReturnsTrueForHashedPasswordWithValidLatin1UmlautCharClassPassword()
    {
        $password = '';
        for ($i = 192; $i <= 255; $i++) {
            if ($i === 215 || $i === 247) {
                // skip multiplication sign (×) and obelus (÷)
                continue;
            }
            $password .= chr($i);
        }
        $hash = $this->subject->getHashedPassword($password);
        $this->assertTrue($this->subject->checkPassword($password, $hash));
    }

    /**
     * @test
     */
    public function checkPasswordReturnsTrueForHashedPasswordWithNonValidPassword()
    {
        $password = 'password';
        $password1 = $password . 'INVALID';
        $hash = $this->subject->getHashedPassword($password);
        $this->assertFalse($this->subject->checkPassword($password1, $hash));
    }

    /**
     * @test
     */
    public function isHashUpdateNeededReturnsFalseForJustGeneratedHash()
    {
        $hash = $this->subject->getHashedPassword('password');
        $this->assertFalse($this->subject->isHashUpdateNeeded($hash));
    }

    /**
     * @test
     */
    public function isHashUpdateNeededReturnsTrueForHashGeneratedWithOldOptions()
    {
        $originalOptions = $this->subject->getOptions();
        $hash = $this->subject->getHashedPassword('password');

        $newOptions = $originalOptions;
        $newOptions['cost'] = $newOptions['cost'] + 1;
        $this->subject->setOptions($newOptions);
        $this->assertTrue($this->subject->isHashUpdateNeeded($hash));
    }

    /**
     * Bcrypt truncates on NUL characters by default
     *
     * @test
     */
    public function getHashedPasswordDoesNotTruncateOnNul()
    {
        $password1 = 'pass' . "\x00" . 'word';
        $password2 = 'pass' . "\x00" . 'phrase';
        $hash = $this->subject->getHashedPassword($password1);
        $this->assertFalse($this->subject->checkPassword($password2, $hash));
    }

    /**
     * Bcrypt truncates after 72 characters by default
     *
     * @test
     */
    public function getHashedPasswordDoesNotTruncateAfter72Chars()
    {
        $prefix = str_repeat('a', 72);
        $password1 = $prefix . 'one';
        $password2 = $prefix . 'two';
        $hash = $this->subject->getHashedPassword($password1);
        $this->assertFalse($this->subject->checkPassword($password2, $hash));
    }
}
