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

namespace TYPO3\CMS\Core\Tests\Unit\FormProtection;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\FormProtection\BackendFormProtection;
use TYPO3\CMS\Core\FormProtection\DisabledFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FormProtectionFactoryTest extends UnitTestCase
{
    protected FormProtectionFactory $subject;

    protected function setUp(): void
    {
        $this->subject = new FormProtectionFactory(
            new FlashMessageService(),
            new LanguageServiceFactory(
                new Locales(),
                $this->createMock(LocalizationFactory::class),
                new NullFrontend('null')
            ),
            new Registry()
        );
        parent::setUp();
    }

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
    public function getForNotExistingClassThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1285352962);

        FormProtectionFactory::get('noSuchClass');
    }

    /**
     * @test
     */
    public function getForClassThatIsNoFormProtectionSubclassThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1285353026);

        FormProtectionFactory::get(self::class);
    }

    /**
     * @test
     */
    public function getForTypeBackEndWithExistingBackEndReturnsBackEndFormProtection(): void
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
    public function getForTypeBackEndCalledTwoTimesReturnsTheSameInstance(): void
    {
        $userMock = $this->createMock(BackendUserAuthentication::class);
        $userMock->user = ['uid' => 4711];
        $arguments = [
            BackendFormProtection::class,
            $userMock,
            $this->createMock(Registry::class),
        ];
        self::assertSame(
            FormProtectionFactory::get(...$arguments),
            FormProtectionFactory::get(...$arguments)
        );
    }

    /**
     * @test
     */
    public function getForTypeInstallToolReturnsInstallToolFormProtection(): void
    {
        self::assertInstanceOf(
            InstallToolFormProtection::class,
            FormProtectionFactory::get(InstallToolFormProtection::class)
        );
    }

    /**
     * @test
     */
    public function getForTypeInstallToolCalledTwoTimesReturnsTheSameInstance(): void
    {
        self::assertSame(FormProtectionFactory::get(InstallToolFormProtection::class), FormProtectionFactory::get(InstallToolFormProtection::class));
    }

    /**
     * @test
     */
    public function getForTypesInstallToolAndDisabledReturnsDifferentInstances(): void
    {
        self::assertNotSame(FormProtectionFactory::get(InstallToolFormProtection::class), FormProtectionFactory::get(DisabledFormProtection::class));
    }

    /**
     * @test
     */
    public function createForTypeReturnsDisabledIfInvalidTypeIsGiven(): void
    {
        $formProtection = $this->subject->createForType('invalid-type');
        self::assertInstanceOf(DisabledFormProtection::class, $formProtection);
    }

    /**
     * @test
     */
    public function createForTypeReturnsDisabledIfInvalidTypeIsGivenAndSameInstanceIfDisabledIsGivenLaterOn(): void
    {
        $formProtection = $this->subject->createForType('invalid-type');
        self::assertInstanceOf(DisabledFormProtection::class, $formProtection);
        $formProtectionDisabled = $this->subject->createForType('disabled');
        self::assertInstanceOf(DisabledFormProtection::class, $formProtectionDisabled);
        self::assertSame($formProtectionDisabled, $formProtection);
    }

    /**
     * @test
     */
    public function createForTypeReturnsDisabledForValidTypeButWithoutValidGlobalArguments(): void
    {
        $formProtection = $this->subject->createForType('frontend');
        self::assertInstanceOf(DisabledFormProtection::class, $formProtection);
        $formProtection = $this->subject->createForType('backend');
        self::assertInstanceOf(DisabledFormProtection::class, $formProtection);
    }

    /**
     * @test
     */
    public function createForTypeAlwaysReturnsInstallToolRegardlessOfRequirementsIfRequested(): void
    {
        $formProtection = $this->subject->createForType('installtool');
        self::assertInstanceOf(InstallToolFormProtection::class, $formProtection);
    }

    /**
     * @test
     */
    public function createForTypeReturnsDisabledIfBackendUserIsNotAvailable(): void
    {
        $user = new BackendUserAuthentication();
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://example.com/backend/login/');
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute('backend.user', $user);
        $formProtection = $this->subject->createForType('backend');
        // User is not logged in
        self::assertInstanceOf(DisabledFormProtection::class, $formProtection);
    }

    /**
     * @test
     */
    public function createForTypeReturnsBackendIfBackendUserIsLoggedIn(): void
    {
        $user = new BackendUserAuthentication();
        $user->user = ['uid' => 13];
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://example.com/backend/login/');
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute('backend.user', $user);
        $formProtection = $this->subject->createForType('backend');
        // User is now logged in
        self::assertInstanceOf(BackendFormProtection::class, $formProtection);
    }

    /**
     * @test
     */
    public function createForTypeReturnsTheSameInstanceEvenThoughUserWasLoggedInLaterOn(): void
    {
        $user = new BackendUserAuthentication();
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://example.com/backend/login/');
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute('backend.user', $user);
        $formProtection = $this->subject->createForType('backend');
        // User is not logged in
        self::assertInstanceOf(DisabledFormProtection::class, $formProtection);
        $user->user = ['uid' => 13];
        $formProtection = $this->subject->createForType('backend');
        // User is now logged in, but we still get the disabled form protection due to the "singleton" concept
        self::assertInstanceOf(DisabledFormProtection::class, $formProtection);
        $this->subject->clearInstances();
        $formProtection = $this->subject->createForType('backend');
        // User is now logged in, now we get a backend form protection, due to the "purge instance" concept.
        self::assertInstanceOf(BackendFormProtection::class, $formProtection);
    }
}
