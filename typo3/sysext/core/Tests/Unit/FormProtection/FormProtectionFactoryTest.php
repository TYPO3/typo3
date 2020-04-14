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

namespace TYPO3\CMS\Core\Tests\Unit\FormProtection;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\FormProtection\BackendFormProtection;
use TYPO3\CMS\Core\FormProtection\DisabledFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase
 */
class FormProtectionFactoryTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        FormProtectionFactory::purgeInstances();
        parent::tearDown();
    }

    /////////////////////////
    // Tests concerning get
    /////////////////////////
    /**
     * @test
     */
    public function getForNotExistingClassThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1285352962);

        FormProtectionFactory::get('noSuchClass');
    }

    /**
     * @test
     */
    public function getForClassThatIsNoFormProtectionSubclassThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1285353026);

        FormProtectionFactory::get(self::class);
    }

    /**
     * @test
     */
    public function getForTypeBackEndWithExistingBackEndReturnsBackEndFormProtection()
    {
        $userMock = $this->createMock(BackendUserAuthentication::class);
        $userMock->user = ['uid' => 4711];
        self::assertInstanceOf(
            BackendFormProtection::class,
            FormProtectionFactory::get(
                BackendFormProtection::class,
                $userMock,
                $this->createMock(Registry::class)
            )
        );
    }

    /**
     * @test
     */
    public function getForTypeBackEndCalledTwoTimesReturnsTheSameInstance()
    {
        $userMock = $this->createMock(BackendUserAuthentication::class);
        $userMock->user = ['uid' => 4711];
        $arguments = [
            BackendFormProtection::class,
            $userMock,
            $this->createMock(Registry::class)
        ];
        self::assertSame(
            call_user_func_array([FormProtectionFactory::class, 'get'], $arguments),
            call_user_func_array([FormProtectionFactory::class, 'get'], $arguments)
        );
    }

    /**
     * @test
     */
    public function getForTypeInstallToolReturnsInstallToolFormProtection()
    {
        self::assertTrue(FormProtectionFactory::get(InstallToolFormProtection::class) instanceof InstallToolFormProtection);
    }

    /**
     * @test
     */
    public function getForTypeInstallToolCalledTwoTimesReturnsTheSameInstance()
    {
        self::assertSame(FormProtectionFactory::get(InstallToolFormProtection::class), FormProtectionFactory::get(InstallToolFormProtection::class));
    }

    /**
     * @test
     */
    public function getForTypesInstallToolAndDisabledReturnsDifferentInstances()
    {
        self::assertNotSame(FormProtectionFactory::get(InstallToolFormProtection::class), FormProtectionFactory::get(DisabledFormProtection::class));
    }

    /////////////////////////
    // Tests concerning set
    /////////////////////////
    /**
     * @test
     */
    public function setSetsInstanceForType()
    {
        $instance = new FormProtectionTesting();
        FormProtectionFactory::set(BackendFormProtection::class, $instance);
        self::assertSame($instance, FormProtectionFactory::get(BackendFormProtection::class));
    }

    /**
     * @test
     */
    public function setNotSetsInstanceForOtherType()
    {
        $instance = new FormProtectionTesting();
        FormProtectionFactory::set(BackendFormProtection::class, $instance);
        self::assertNotSame($instance, FormProtectionFactory::get(InstallToolFormProtection::class));
    }
}
