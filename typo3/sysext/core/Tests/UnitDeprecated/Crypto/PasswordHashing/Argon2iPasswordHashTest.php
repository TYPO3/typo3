<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Crypto\PasswordHashing;

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

use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class Argon2iPasswordHashTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getOptionsReturnsPreviouslySetOptions()
    {
        $options = [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 4,
        ];
        $subject = new Argon2iPasswordHash();
        $subject->setOptions($options);
        $this->assertSame($subject->getOptions(), $options);
    }

    /**
     * @test
     */
    public function setOptionsThrowsExceptionWithTooLowMemoryCost()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1526042080);
        $subject = new Argon2iPasswordHash();
        $subject->setOptions(['memory_cost' => 1]);
    }

    /**
     * @test
     */
    public function setOptionsThrowsExceptionWithTooLowTimeCost()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1526042081);
        $subject = new Argon2iPasswordHash();
        $subject->setOptions(['time_cost' => 1]);
    }

    /**
     * @test
     */
    public function setOptionsThrowsExceptionWithTooLowThreads()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1526042082);
        $subject = new Argon2iPasswordHash();
        $subject->setOptions(['threads' => 0]);
    }
}
