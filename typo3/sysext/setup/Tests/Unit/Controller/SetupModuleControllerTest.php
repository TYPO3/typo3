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

namespace TYPO3\CMS\Setup\Tests\Unit\Controller;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Setup\Controller\SetupModuleController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class SetupModuleControllerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function addFlashMessagesAddsMessagesIfSetupIsUpdated()
    {
        $setupModuleControllerMock = $this->getAccessibleMock(
            SetupModuleController::class,
            ['getFlashMessage', 'enqueueFlashMessages'],
            [],
            '',
            false
        );
        $setupModuleControllerMock->_set('setupIsUpdated', true);

        $setupModuleControllerMock
            ->expects(self::atMost(2))
            ->method('getFlashMessage')
            ->withConsecutive(
                ['setupWasUpdated', 'UserSettings'],
                ['activateChanges', '']
            );

        $setupModuleControllerMock->_call('addFlashMessages');
    }

    /**
     * @test
     */
    public function addFlashMessagesAddsMessageIfSettingsAreResetToDefault()
    {
        $setupModuleControllerMock = $this->getAccessibleMock(
            SetupModuleController::class,
            ['getFlashMessage', 'enqueueFlashMessages'],
            [],
            '',
            false
        );
        $setupModuleControllerMock->_set('settingsAreResetToDefault', true);

        $setupModuleControllerMock
            ->expects(self::atMost(2))
            ->method('getFlashMessage')
            ->withConsecutive(
                ['settingsAreReset', 'resetConfiguration'],
                ['activateChanges', '']
            );

        $setupModuleControllerMock->_call('addFlashMessages');
    }

    /**
     * @test
     */
    public function addFlashMessagesAddsMessageIfPasswordWasSuccessfullyUpdated()
    {
        $setupModuleControllerMock = $this->getAccessibleMock(
            SetupModuleController::class,
            ['getFlashMessage', 'enqueueFlashMessages'],
            [],
            '',
            false
        );
        $setupModuleControllerMock->_set('passwordIsSubmitted', true);
        $setupModuleControllerMock->_set('passwordIsUpdated', SetupModuleController::PASSWORD_UPDATED);

        $setupModuleControllerMock
            ->expects(self::once())
            ->method('getFlashMessage')
            ->withConsecutive(
                ['newPassword_ok', 'newPassword']
            );

        $setupModuleControllerMock->_call('addFlashMessages');
    }

    /**
     * @test
     */
    public function addFlashMessagesAddsMessageIfOldPasswordWasWrong()
    {
        $setupModuleControllerMock = $this->getAccessibleMock(
            SetupModuleController::class,
            ['getFlashMessage', 'enqueueFlashMessages'],
            [],
            '',
            false
        );
        $setupModuleControllerMock->_set('passwordIsSubmitted', true);
        $setupModuleControllerMock->_set('passwordIsUpdated', SetupModuleController::PASSWORD_OLD_WRONG);

        $setupModuleControllerMock
            ->expects(self::once())
            ->method('getFlashMessage')
            ->withConsecutive(
                ['oldPassword_failed', 'newPassword']
            );

        $setupModuleControllerMock->_call('addFlashMessages');
    }

    /**
     * @test
     */
    public function addFlashMessagesAddsMessageIfPasswordsNotTheSame()
    {
        $setupModuleControllerMock = $this->getAccessibleMock(
            SetupModuleController::class,
            ['getFlashMessage', 'enqueueFlashMessages'],
            [],
            '',
            false
        );
        $setupModuleControllerMock->_set('passwordIsSubmitted', true);
        $setupModuleControllerMock->_set('passwordIsUpdated', SetupModuleController::PASSWORD_NOT_THE_SAME);

        $setupModuleControllerMock
            ->expects(self::once())
            ->method('getFlashMessage')
            ->withConsecutive(
                ['newPassword_failed', 'newPassword']
            );

        $setupModuleControllerMock->_call('addFlashMessages');
    }

    /**
     * @test
     */
    public function addFlashMessagesAddsMessagesToQueue()
    {
        $setupModuleControllerMock = $this->getAccessibleMock(
            SetupModuleController::class,
            ['getFlashMessage', 'enqueueFlashMessages'],
            [],
            '',
            false
        );

        $setupModuleControllerMock->_set('settingsAreResetToDefault', true);
        $flashMessage1 = new FlashMessage('foo', 'bar');
        $flashMessage2 = new FlashMessage('bar', 'foo');
        $setupModuleControllerMock
            ->expects(self::atMost(2))
            ->method('getFlashMessage')
            ->withConsecutive(
                ['settingsAreReset', 'resetConfiguration'],
                ['activateChanges', '']
            )->willReturnOnConsecutiveCalls(
                $flashMessage1,
                $flashMessage2
            );

        $setupModuleControllerMock
            ->expects(self::once())
            ->method('enqueueFlashMessages')
            ->with([$flashMessage1, $flashMessage2]);
        $setupModuleControllerMock->_call('addFlashMessages');
    }
}
