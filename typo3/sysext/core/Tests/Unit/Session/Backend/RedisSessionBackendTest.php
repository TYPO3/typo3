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

namespace TYPO3\CMS\Core\Tests\Unit\Session\Backend;

use TYPO3\CMS\Core\Session\Backend\RedisSessionBackend;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RedisSessionBackendTest extends UnitTestCase
{
    protected function setUp(): void
    {
        if (!class_exists(\Redis::class)) {
            self::markTestSkipped('Redis class needs to be available to test RedisSessionBackend');
        }
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';
    }

    /**
     * @test
     */
    public function databaseConfigurationMustBeInteger()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1481270871);
        $subject = new RedisSessionBackend();
        $subject->initialize(
            'default',
            [
                'database' => 'numberZero'
            ]
        );
        $subject->validateConfiguration();
    }

    /**
     * @test
     */
    public function databaseConfigurationMustBeZeroOrGreater()
    {
        $subject = new RedisSessionBackend();
        $subject->initialize(
            'default',
            [
                'database' => -1
            ]
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1481270923);
        $subject->validateConfiguration();
    }
}
