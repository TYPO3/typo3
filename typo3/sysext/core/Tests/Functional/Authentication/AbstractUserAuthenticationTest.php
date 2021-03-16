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

namespace TYPO3\CMS\Core\Tests\Functional\Authentication;

use TYPO3\CMS\Core\Tests\Functional\Authentication\Fixtures\AnyUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class AbstractUserAuthenticationTest extends FunctionalTestCase
{
    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var AnyUserAuthentication
     */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';
        $GLOBALS['TYPO3_CONF_VARS']['ANY']['lockIP'] = 0;
        $GLOBALS['TYPO3_CONF_VARS']['ANY']['lockIPv6'] = 0;
        $this->sessionId = bin2hex(random_bytes(20));
        $this->subject = new AnyUserAuthentication($this->sessionId);
    }

    protected function tearDown(): void
    {
        unset($this->sessionId, $this->subject);
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function pushModuleDataDoesNotRevealPlainSessionId(): void
    {
        $this->subject->pushModuleData(self::class, true);
        self::assertNotContains($this->sessionId, $this->subject->uc['moduleSessionID']);
    }

    /**
     * @test
     */
    public function getModuleDataResolvesHashedSessionId(): void
    {
        $this->subject->pushModuleData(self::class, true);
        self::assertTrue($this->subject->getModuleData(self::class));
    }

    /**
     * @test
     */
    public function getModuleDataFallsBackToPlainSessionId(): void
    {
        $this->subject->uc['moduleData'][self::class] = true;
        $this->subject->uc['moduleSessionID'][self::class] = $this->sessionId;
        self::assertTrue($this->subject->getModuleData(self::class));
    }
}
