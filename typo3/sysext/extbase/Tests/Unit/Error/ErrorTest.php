<?php

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

namespace TYPO3\CMS\Extbase\Tests\Unit\Error;

use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ErrorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function theConstructorSetsTheErrorMessageCorrectly()
    {
        $errorMessage = 'The message';
        $error = new Error($errorMessage, 0);
        self::assertEquals($errorMessage, $error->getMessage());
    }

    /**
     * @test
     */
    public function theConstructorSetsTheErrorCodeCorrectly()
    {
        $errorCode = 123456789;
        $error = new Error('', $errorCode);
        self::assertEquals($errorCode, $error->getCode());
    }
}
