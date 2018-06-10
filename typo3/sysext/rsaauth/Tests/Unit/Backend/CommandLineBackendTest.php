<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Rsaauth\Tests\Unit\Backend;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Rsaauth\Backend\CommandLineBackend;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class CommandLineBackendTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * Set up
     */
    protected function setUp()
    {
        if (Environment::isWindows()) {
            $this->markTestSkipped('This test is not available on Windows as auto-detection of openssl path will fail.');
        }
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rsaauth']['temporaryDirectory'] = '';
    }

    /**
     * @test
     */
    public function createNewKeyPairCreatesReadyKeyPair()
    {
        $subject = new CommandLineBackend();
        $keyPair = $subject->createNewKeyPair();
        if ($keyPair === null) {
            $this->markTestSkipped('KeyPair could not be generated. Maybe openssl was not found.');
        }

        $this->assertTrue($keyPair->isReady());
    }

    /**
     * @test
     */
    public function createNewKeyPairCreatesKeyPairWithDefaultExponent()
    {
        $subject = new CommandLineBackend();
        $keyPair = $subject->createNewKeyPair();
        if ($keyPair === null) {
            $this->markTestSkipped('KeyPair could not be generated. Maybe openssl was not found.');
        }

        $this->assertSame(
            CommandLineBackend::DEFAULT_EXPONENT,
            $keyPair->getExponent()
        );
    }

    /**
     * @test
     */
    public function createNewKeyPairCalledTwoTimesReturnsSameKeyPairInstance()
    {
        $subject = new CommandLineBackend();
        $this->assertSame(
            $subject->createNewKeyPair(),
            $subject->createNewKeyPair()
        );
    }
}
