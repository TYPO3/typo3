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

use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
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
    protected FrontendInterface $runtimeCacheMock;

    protected function setUp(): void
    {
        $this->runtimeCacheMock = new VariableFrontend('null', new TransientMemoryBackend('null', ['logger' => new NullLogger()]));
        $this->subject = new FormProtectionFactory(
            new FlashMessageService(),
            new LanguageServiceFactory(
                new Locales(),
                $this->createMock(LocalizationFactory::class),
                new NullFrontend('null')
            ),
            new Registry(),
            $this->runtimeCacheMock
        );
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->runtimeCacheMock->flush();
        parent::tearDown();
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
        // we need to manually flush this here, aas next test should expect a cleared state.
        $this->runtimeCacheMock->flush();
        $formProtection = $this->subject->createForType('backend');
        // User is now logged in, now we get a backend form protection, due to the "purge instance" concept.
        self::assertInstanceOf(BackendFormProtection::class, $formProtection);
    }
}
