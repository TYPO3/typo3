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

namespace TYPO3\CMS\Core\Tests\UnitDeprecated\FormProtection;

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
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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

    /////////////////////////
    // Tests concerning get
    /////////////////////////
    /**
     * @test
     */
    public function getForNotExistingClassThrowsException(): void
    {
        GeneralUtility::addInstance(FormProtectionFactory::class, $this->subject);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1285352962);

        FormProtectionFactory::get('noSuchClass');
    }

    /**
     * @test
     */
    public function getForClassThatIsNoFormProtectionSubclassThrowsException(): void
    {
        GeneralUtility::addInstance(FormProtectionFactory::class, $this->subject);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1285353026);

        FormProtectionFactory::get(self::class);
    }

    /**
     * @test
     */
    public function getForTypeBackEndWithExistingBackEndReturnsBackEndFormProtection(): void
    {
        GeneralUtility::addInstance(FormProtectionFactory::class, $this->subject);
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
        GeneralUtility::addInstance(FormProtectionFactory::class, $this->subject);
        GeneralUtility::addInstance(FormProtectionFactory::class, $this->subject);
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
        GeneralUtility::addInstance(FormProtectionFactory::class, $this->subject);
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
        GeneralUtility::addInstance(FormProtectionFactory::class, $this->subject);
        GeneralUtility::addInstance(FormProtectionFactory::class, $this->subject);
        self::assertSame(
            FormProtectionFactory::get(InstallToolFormProtection::class),
            FormProtectionFactory::get(InstallToolFormProtection::class)
        );
    }

    /**
     * @test
     */
    public function getForTypesInstallToolAndDisabledReturnsDifferentInstances(): void
    {
        GeneralUtility::addInstance(FormProtectionFactory::class, $this->subject);
        GeneralUtility::addInstance(FormProtectionFactory::class, $this->subject);
        self::assertNotSame(
            FormProtectionFactory::get(InstallToolFormProtection::class),
            FormProtectionFactory::get(DisabledFormProtection::class)
        );
    }
}
