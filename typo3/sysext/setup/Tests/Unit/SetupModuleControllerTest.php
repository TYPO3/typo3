<?php
namespace TYPO3\CMS\Setup\Tests\Unit\Controller;

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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Setup\Controller\SetupModuleController;

/**
 * Class SetupModuleControllerTest
 */
class SetupModuleControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{

    /**
     * @test
     * @return void
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
            ->expects($this->atMost(2))
            ->method('getFlashMessage')
            ->withConsecutive(
                ['setupWasUpdated', 'UserSettings'],
                ['activateChanges', '']
            );

        $setupModuleControllerMock->_call('addFlashMessages');
    }

    /**
     * @test
     * @return void
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
            ->expects($this->atMost(2))
            ->method('getFlashMessage')
            ->withConsecutive(
                ['settingsAreReset', 'resetConfiguration'],
                ['activateChanges', '']
            );

        $setupModuleControllerMock->_call('addFlashMessages');
    }

    /**
     * @test
     * @return void
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
            ->expects($this->once())
            ->method('getFlashMessage')
            ->withConsecutive(
                ['newPassword_ok', 'newPassword']
            );

        $setupModuleControllerMock->_call('addFlashMessages');
    }

    /**
     * @test
     * @return void
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
            ->expects($this->once())
            ->method('getFlashMessage')
            ->withConsecutive(
                ['oldPassword_failed', 'newPassword']
            );

        $setupModuleControllerMock->_call('addFlashMessages');
    }

    /**
     * @test
     * @return void
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
            ->expects($this->once())
            ->method('getFlashMessage')
            ->withConsecutive(
                ['newPassword_failed', 'newPassword']
            );

        $setupModuleControllerMock->_call('addFlashMessages');
    }

    /**
     * @test
     * @return void
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
            ->expects($this->atMost(2))
            ->method('getFlashMessage')
            ->withConsecutive(
                ['settingsAreReset', 'resetConfiguration'],
                ['activateChanges', '']
            )->willReturnOnConsecutiveCalls(
                $flashMessage1,
                $flashMessage2
            );

        $setupModuleControllerMock
            ->expects($this->once())
            ->method('enqueueFlashMessages')
            ->with([$flashMessage1, $flashMessage2]);
        $setupModuleControllerMock->_call('addFlashMessages');
    }
}
