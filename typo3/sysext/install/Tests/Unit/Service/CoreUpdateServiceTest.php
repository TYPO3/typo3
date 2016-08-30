<?php
namespace TYPO3\CMS\Install\Service;

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

/**
 * Test case
 */
class CoreUpdateServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function getMessagesReturnsPreviouslySetMessage()
    {
        /** @var $instance \TYPO3\CMS\Install\Service\CoreUpdateService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(\TYPO3\CMS\Install\Service\CoreUpdateService::class, ['dummy'], [], '', false);
        $aMessage = $this->getUniqueId('message_');
        $instance->_set('messages', $aMessage);
        $this->assertSame($aMessage, $instance->getMessages());
    }

    /**
     * @test
     */
    public function isCoreUpdateEnabledReturnsTrueForEnvironmentVariableNotSet()
    {
        if (defined('TYPO3_COMPOSER_MODE') && TYPO3_COMPOSER_MODE) {
            $this->markTestSkipped('This test is only available in Non-Composer mode.');
        }
        /** @var $instance \TYPO3\CMS\Install\Service\CoreUpdateService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(\TYPO3\CMS\Install\Service\CoreUpdateService::class, ['dummy'], [], '', false);
        putenv('TYPO3_DISABLE_CORE_UPDATER');
        putenv('REDIRECT_TYPO3_DISABLE_CORE_UPDATER');
        $this->assertTrue($instance->isCoreUpdateEnabled());
    }

    /**
     * @test
     */
    public function isCoreUpdateEnabledReturnsFalseFor_TYPO3_DISABLE_CORE_UPDATER_EnvironmentVariableSet()
    {
        /** @var $instance \TYPO3\CMS\Install\Service\CoreUpdateService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(\TYPO3\CMS\Install\Service\CoreUpdateService::class, ['dummy'], [], '', false);
        putenv('TYPO3_DISABLE_CORE_UPDATER=1');
        putenv('REDIRECT_TYPO3_DISABLE_CORE_UPDATER');
        $this->assertFalse($instance->isCoreUpdateEnabled());
    }

    /**
     * @test
     */
    public function isCoreUpdateEnabledReturnsFalseFor_REDIRECT_TYPO3_DISABLE_CORE_UPDATER_EnvironmentVariableSet()
    {
        /** @var $instance \TYPO3\CMS\Install\Service\CoreUpdateService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(\TYPO3\CMS\Install\Service\CoreUpdateService::class, ['dummy'], [], '', false);
        putenv('TYPO3_DISABLE_CORE_UPDATER');
        putenv('REDIRECT_TYPO3_DISABLE_CORE_UPDATER=1');
        $this->assertFalse($instance->isCoreUpdateEnabled());
    }

    /**
     * @test
     */
    public function isCoreUpdateEnabledReturnsFalseFor_TYPO3_COMPOSER_MODE_EnvironmentVariableSet()
    {
        if (!defined('TYPO3_COMPOSER_MODE')) {
            putenv('TYPO3_COMPOSER_MODE=1');
        }
        /** @var $instance \TYPO3\CMS\Install\Service\CoreUpdateService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(\TYPO3\CMS\Install\Service\CoreUpdateService::class, ['dummy'], [], '', false);
        $this->assertFalse($instance->isCoreUpdateEnabled());
    }
}
