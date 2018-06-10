<?php
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

use TYPO3\CMS\Rsaauth\Backend\PhpBackend;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class PhpBackendTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function createNewKeyPairCreatesReadyKeyPair()
    {
        $subject = new PhpBackend();
        $keyPair = $subject->createNewKeyPair();
        $this->assertNotNull($keyPair, 'Test fails because of broken environment: PHP OpenSSL extension is not working properly.');
        $this->assertTrue($keyPair->isReady());
    }

    /**
     * @test
     */
    public function createNewKeyPairCalledTwoTimesReturnsSameKeyPairInstance()
    {
        $subject = new PhpBackend();
        $keyPair1 = $subject->createNewKeyPair();
        $this->assertNotNull($keyPair1, 'Test fails because of broken environment: PHP OpenSSL extension is not working properly.');
        $this->assertSame(
            $keyPair1,
            $subject->createNewKeyPair()
        );
    }
}
