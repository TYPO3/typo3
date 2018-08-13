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

use TYPO3\CMS\Core\Crypto\PasswordHashing\BcryptPasswordHash;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class BcryptPasswordHashTest extends UnitTestCase
{
    /**
     * @var BcryptPasswordHash
     */
    protected $subject;

    /**
     * Sets up the fixtures for this testcase.
     */
    protected function setUp()
    {
        // Set a low cost to speed up tests
        $options = [
            'cost' => 10,
        ];
        $this->subject = new BcryptPasswordHash($options);
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
}
