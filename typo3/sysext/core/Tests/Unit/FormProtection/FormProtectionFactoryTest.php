<?php
namespace TYPO3\CMS\Core\Tests\Unit\FormProtection;

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

use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Registry;

/**
 * Testcase
 */
class FormProtectionFactoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    protected function setUp()
    {
    }

    protected function tearDown()
    {
        FormProtectionFactory::purgeInstances();
        parent::tearDown();
    }

    /////////////////////////
    // Tests concerning get
    /////////////////////////
    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function getForNotExistingClassThrowsException()
    {
        FormProtectionFactory::get('noSuchClass');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function getForClassThatIsNoFormProtectionSubclassThrowsException()
    {
        FormProtectionFactory::get(\TYPO3\CMS\Core\Tests\Unit\FormProtection\FormProtectionFactoryTest::class);
    }

    /**
     * @test
     */
    public function getForTypeBackEndWithExistingBackEndReturnsBackEndFormProtection()
    {
        $userMock = $this->getMock(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class, [], [], '', false);
        $userMock->user = ['uid' => $this->getUniqueId()];
        $this->assertInstanceOf(
            \TYPO3\CMS\Core\FormProtection\BackendFormProtection::class,
            FormProtectionFactory::get(
                \TYPO3\CMS\Core\FormProtection\BackendFormProtection::class,
                $userMock,
                $this->getMock(Registry::class)
            )
        );
    }

    /**
     * @test
     */
    public function getForTypeBackEndCalledTwoTimesReturnsTheSameInstance()
    {
        $userMock = $this->getMock(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class, [], [], '', false);
        $userMock->user = ['uid' => $this->getUniqueId()];
        $arguments = [
            \TYPO3\CMS\Core\FormProtection\BackendFormProtection::class,
            $userMock,
            $this->getMock(Registry::class)
        ];
        $this->assertSame(
            call_user_func_array([FormProtectionFactory::class, 'get'], $arguments),
            call_user_func_array([FormProtectionFactory::class, 'get'], $arguments)
        );
    }

    /**
     * @test
     */
    public function getForTypeInstallToolReturnsInstallToolFormProtection()
    {
        $this->assertTrue(FormProtectionFactory::get(\TYPO3\CMS\Core\FormProtection\InstallToolFormProtection::class) instanceof \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection);
    }

    /**
     * @test
     */
    public function getForTypeInstallToolCalledTwoTimesReturnsTheSameInstance()
    {
        $this->assertSame(FormProtectionFactory::get(\TYPO3\CMS\Core\FormProtection\InstallToolFormProtection::class), FormProtectionFactory::get(\TYPO3\CMS\Core\FormProtection\InstallToolFormProtection::class));
    }

    /**
     * @test
     */
    public function getForTypesInstallToolAndDisabledReturnsDifferentInstances()
    {
        $this->assertNotSame(FormProtectionFactory::get(\TYPO3\CMS\Core\FormProtection\InstallToolFormProtection::class), FormProtectionFactory::get(\TYPO3\CMS\Core\FormProtection\DisabledFormProtection::class));
    }

    /////////////////////////
    // Tests concerning set
    /////////////////////////
    /**
     * @test
     */
    public function setSetsInstanceForType()
    {
        $instance = new \TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting();
        FormProtectionFactory::set(\TYPO3\CMS\Core\FormProtection\BackendFormProtection::class, $instance);
        $this->assertSame($instance, FormProtectionFactory::get(\TYPO3\CMS\Core\FormProtection\BackendFormProtection::class));
    }

    /**
     * @test
     */
    public function setNotSetsInstanceForOtherType()
    {
        $instance = new \TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting();
        FormProtectionFactory::set(\TYPO3\CMS\Core\FormProtection\BackendFormProtection::class, $instance);
        $this->assertNotSame($instance, FormProtectionFactory::get(\TYPO3\CMS\Core\FormProtection\InstallToolFormProtection::class));
    }

    /**
     * @test
     */
    public function createValidationErrorMessageAddsErrorFlashMessageButNotInSessionInAjaxRequest()
    {
        $flashMessageQueueMock = $this->getMock(
            \TYPO3\CMS\Core\Messaging\FlashMessageQueue::class,
            [],
            [],
            '',
            false
        );
        $flashMessageQueueMock
            ->expects($this->once())
            ->method('enqueue')
            ->with($this->isInstanceOf(\TYPO3\CMS\Core\Messaging\FlashMessage::class))
            ->will($this->returnCallback([$this, 'enqueueAjaxFlashMessageCallback']));
        $languageServiceMock = $this->getMock(\TYPO3\CMS\Lang\LanguageService::class, [], [], '', false);
        $languageServiceMock->expects($this->once())->method('sL')->will($this->returnValue('foo'));

        FormProtectionFactory::getMessageClosure($languageServiceMock, $flashMessageQueueMock, true)->__invoke();
    }

    /**
     * @param \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage
     */
    public function enqueueAjaxFlashMessageCallback(\TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage)
    {
        $this->assertFalse($flashMessage->isSessionMessage());
    }
}
