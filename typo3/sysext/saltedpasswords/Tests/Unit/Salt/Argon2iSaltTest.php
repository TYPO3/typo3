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

use TYPO3\CMS\Saltedpasswords\Salt\Argon2iSalt;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class Argon2iSaltTest extends UnitTestCase
{
    /**
     * @var Argon2iSalt
     */
    protected $subject;

    /**
     * Sets up the fixtures for this testcase.
     */
    protected function setUp()
    {
        $this->subject = new Argon2iSalt();
        // Set low values to speed up tests
        $this->subject->setOptions([
            'memory_cost' => 1024,
            'time_cost' => 2,
            'threads' => 2,
        ]);
    }

    /**
     * @test
     */
    public function getOptionsReturnsPreviouslySetOptions()
    {
        $options = [
            'memory_cost' => 2048,
            'time_cost' => 4,
            'threads' => 4,
        ];
        $this->subject->setOptions($options);
        $this->assertSame($this->subject->getOptions(), $options);
    }

    /**
     * @test
     */
    public function setOptionsThrowsExceptionWithTooLowMemoryCost()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1526042080);
        $this->subject->setOptions(['memory_cost' => 1]);
    }

    /**
     * @test
     */
    public function setOptionsThrowsExceptionWithTooLowTimeCost()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1526042081);
        $this->subject->setOptions(['time_cost' => 1]);
    }

    /**
     * @test
     */
    public function setOptionsThrowsExceptionWithTooLowThreads()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1526042082);
        $this->subject->setOptions(['threads' => 1]);
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
        $this->assertEquals('string', gettype($hash));
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
        $password = 'password';
        $hash = $this->subject->getHashedPassword($password);
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
        $newOptions['memory_cost'] = $newOptions['memory_cost'] + 1;
        $this->subject->setOptions($newOptions);
        $this->assertTrue($this->subject->isHashUpdateNeeded($hash));
        $this->subject->setOptions($originalOptions);

        // Change $timeCost
        $newOptions = $originalOptions;
        $newOptions['time_cost'] = $newOptions['time_cost'] + 1;
        $this->subject->setOptions($newOptions);
        $this->assertTrue($this->subject->isHashUpdateNeeded($hash));
        $this->subject->setOptions($originalOptions);

        // Change $threads
        $newOptions = $originalOptions;
        $newOptions['threads'] = $newOptions['threads'] + 1;
        $this->subject->setOptions($newOptions);
        $this->assertTrue($this->subject->isHashUpdateNeeded($hash));
        $this->subject->setOptions($originalOptions);
    }
}
