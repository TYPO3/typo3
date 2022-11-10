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

namespace TYPO3\CMS\Core\Tests\Functional\Resource;

use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Resource\DefaultUploadFolderResolver;
use TYPO3\CMS\Core\Resource\Event\AfterDefaultUploadFolderWasResolvedEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class DefaultUploadFolderResolverTest extends FunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DefaultUploadFolderResolver/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DefaultUploadFolderResolver/pages.csv');
    }

    public function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/fileadmin/admin_upload/', true);
        GeneralUtility::rmdir($this->instancePath . '/fileadmin/page_upload/', true);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function resolveWithUserAndPageConfigTest(): void
    {
        GeneralUtility::mkdir($this->instancePath . '/fileadmin/page_upload/');
        $backendUser = $this->setUpBackendUser(1);

        $subject = GeneralUtility::makeInstance(DefaultUploadFolderResolver::class);

        self::assertEquals(
            '1:/page_upload/',
            $subject->resolve($backendUser, 1)->getCombinedIdentifier()
        );
    }

    /**
     * @test
     */
    public function resolveWithUserConfigTest(): void
    {
        GeneralUtility::mkdir($this->instancePath . '/fileadmin/admin_upload/');
        $backendUser = $this->setUpBackendUser(1);

        $subject = GeneralUtility::makeInstance(DefaultUploadFolderResolver::class);

        self::assertEquals(
            '1:/admin_upload/',
            $subject->resolve($backendUser)->getCombinedIdentifier()
        );
    }

    /**
     * @test
     */
    public function resolveWithoutConfigTest(): void
    {
        $backendUser = $this->setUpBackendUser(2);

        $subject = GeneralUtility::makeInstance(DefaultUploadFolderResolver::class);

        self::assertEquals(
            '1:/user_upload/',
            $subject->resolve($backendUser)->getCombinedIdentifier()
        );
    }

    /**
     * @test
     */
    public function getDefaultUploadFolderForUserTest(): void
    {
        GeneralUtility::mkdir($this->instancePath . '/fileadmin/admin_upload/');
        $backendUser = $this->setUpBackendUser(1);

        $subject = GeneralUtility::makeInstance(DefaultUploadFolderResolver::class);

        self::assertEquals('1:/admin_upload/', $subject->getDefaultUploadFolderForUser($backendUser)->getCombinedIdentifier());
    }

    /**
     * @test
     */
    public function getDefaultUploadFolderForUserWithoutConfigTest(): void
    {
        $backendUser = $this->setUpBackendUser(2);

        $subject = GeneralUtility::makeInstance(DefaultUploadFolderResolver::class);

        self::assertNull($subject->getDefaultUploadFolderForUser($backendUser));
    }

    /**
     * @test
     */
    public function getDefaultUploadFolderForUserWithoutExistingFolderTest(): void
    {
        $backendUser = $this->setUpBackendUser(1);

        $subject = GeneralUtility::makeInstance(DefaultUploadFolderResolver::class);

        self::assertNull($subject->getDefaultUploadFolderForUser($backendUser));
    }

    /**
     * @test
     */
    public function getDefaultUploadFolderForPageTest(): void
    {
        GeneralUtility::mkdir($this->instancePath . '/fileadmin/page_upload/');

        $subject = GeneralUtility::makeInstance(DefaultUploadFolderResolver::class);

        self::assertEquals('1:/page_upload/', $subject->getDefaultUploadFolderForPage(1)->getCombinedIdentifier());
    }

    /**
     * @test
     */
    public function getDefaultUploadFolderForPageWithoutExistingFolderTest(): void
    {
        $subject = GeneralUtility::makeInstance(DefaultUploadFolderResolver::class);

        self::assertNull($subject->getDefaultUploadFolderForPage(1));
    }

    /**
     * @test
     */
    public function afterDefaultUploadFolderWasResolvedEventIsDispatched(): void
    {
        GeneralUtility::mkdir($this->instancePath . '/fileadmin/admin_upload/');
        $backendUser = $this->setUpBackendUser(1);

        $subject = GeneralUtility::makeInstance(DefaultUploadFolderResolver::class);

        /** @var Container $container */
        $container = $this->getContainer();
        $container->set(
            'after-default-upload-folder-was-resolved-listener',
            static function (AfterDefaultUploadFolderWasResolvedEvent $event) use (&$afterDefaultUploadFolderWasResolvedEvent) {
                $afterDefaultUploadFolderWasResolvedEvent = $event;
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AfterDefaultUploadFolderWasResolvedEvent::class, 'after-default-upload-folder-was-resolved-listener');

        $result = $subject->resolve($backendUser);
        self::assertEquals($result, $afterDefaultUploadFolderWasResolvedEvent->getUploadFolder());
    }

    /**
     * @test
     */
    public function afterDefaultUploadFolderWasResolvedEventChangedResult(): void
    {
        GeneralUtility::mkdir($this->instancePath . '/fileadmin/admin_upload/');
        $backendUser = $this->setUpBackendUser(1);

        $subject = GeneralUtility::makeInstance(DefaultUploadFolderResolver::class);

        /** @var Container $container */
        $container = $this->getContainer();
        $container->set(
            'after-default-upload-folder-was-resolved-listener',
            static function (AfterDefaultUploadFolderWasResolvedEvent $event) {
                $event->setUploadFolder($event->getUploadFolder()->getStorage()->getFolder('/'));
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AfterDefaultUploadFolderWasResolvedEvent::class, 'after-default-upload-folder-was-resolved-listener');

        $result = $subject->resolve($backendUser);
        self::assertEquals('1:/', $result->getCombinedIdentifier());
    }
}
