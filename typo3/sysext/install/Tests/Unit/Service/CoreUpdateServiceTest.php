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

namespace TYPO3\CMS\Install\Tests\Unit\Service;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Install\Service\CoreUpdateService;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class CoreUpdateServiceTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getMessagesReturnsPreviouslySetMessage(): void
    {
        /** @var $instance CoreUpdateService|AccessibleObjectInterface|MockObject */
        $instance = $this->getAccessibleMock(CoreUpdateService::class, ['dummy'], [], '', false);
        $aMessage = new FlashMessageQueue('install');
        $instance->_set('messages', $aMessage);
        self::assertSame($aMessage, $instance->getMessages());
    }

    /**
     * @test
     */
    public function isCoreUpdateEnabledReturnsTrueForEnvironmentVariableNotSet(): void
    {
        if (defined('TYPO3_COMPOSER_MODE') && TYPO3_COMPOSER_MODE) {
            self::markTestSkipped('This test is only available in Non-Composer mode.');
        }
        /** @var $instance CoreUpdateService|AccessibleObjectInterface|MockObject */
        $instance = $this->getAccessibleMock(CoreUpdateService::class, ['dummy'], [], '', false);
        putenv('TYPO3_DISABLE_CORE_UPDATER');
        putenv('REDIRECT_TYPO3_DISABLE_CORE_UPDATER');
        self::assertTrue($instance->isCoreUpdateEnabled());
    }

    /**
     * @test
     */
    public function isCoreUpdateEnabledReturnsFalseFor_TYPO3_DISABLE_CORE_UPDATER_EnvironmentVariableSet(): void
    {
        /** @var $instance CoreUpdateService|AccessibleObjectInterface|MockObject */
        $instance = $this->getAccessibleMock(CoreUpdateService::class, ['dummy'], [], '', false);
        putenv('TYPO3_DISABLE_CORE_UPDATER=1');
        putenv('REDIRECT_TYPO3_DISABLE_CORE_UPDATER');
        self::assertFalse($instance->isCoreUpdateEnabled());
    }

    /**
     * @test
     */
    public function isCoreUpdateEnabledReturnsFalseFor_REDIRECT_TYPO3_DISABLE_CORE_UPDATER_EnvironmentVariableSet(): void
    {
        /** @var $instance CoreUpdateService|AccessibleObjectInterface|MockObject */
        $instance = $this->getAccessibleMock(CoreUpdateService::class, ['dummy'], [], '', false);
        putenv('TYPO3_DISABLE_CORE_UPDATER');
        putenv('REDIRECT_TYPO3_DISABLE_CORE_UPDATER=1');
        self::assertFalse($instance->isCoreUpdateEnabled());
    }
}
