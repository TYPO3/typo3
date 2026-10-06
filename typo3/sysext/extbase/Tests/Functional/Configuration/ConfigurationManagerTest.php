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

namespace TYPO3\CMS\Extbase\Tests\Functional\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ConfigurationManagerTest extends FunctionalTestCase
{
    private ConfigurationManager $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ConfigurationManagerRecursiveStoragePids.csv');
        $this->setUpAdminUser();
        $this->subject = $this->get(ConfigurationManager::class);
        $this->subject->setRequest($this->createBackendRequest());
        $this->subject->setConfiguration(['extensionName' => 'CurrentExtensionName', 'pluginName' => 'CurrentPluginName']);
    }

    #[Test]
    public function backendFrameworkConfigurationResolvesRecursiveStoragePidsOnlyOnce(): void
    {
        self::assertSame('2,3,4', $this->getStoragePid());

        // a page tree walk on each call would find the new page
        $this->addStorageSubPage();

        self::assertSame('2,3,4', $this->getStoragePid());
    }

    #[Test]
    public function backendFrameworkConfigurationIsCachedPerRequestedPlugin(): void
    {
        self::assertSame('2,3,4', $this->getStoragePid());
        // module.tx_currentextensionname_flatplugin disables the recursive lookup
        self::assertSame('2', $this->getStoragePid('CurrentExtensionName', 'FlatPlugin'));
        self::assertSame('2,3,4', $this->getStoragePid());
    }

    #[Test]
    public function setConfigurationResetsBackendConfigurationCache(): void
    {
        self::assertSame('2,3,4', $this->getStoragePid());

        $this->subject->setConfiguration(['extensionName' => 'CurrentExtensionName', 'pluginName' => 'FlatPlugin']);
        self::assertSame('2', $this->getStoragePid());

        $this->subject->setConfiguration([
            'extensionName' => 'CurrentExtensionName',
            'pluginName' => 'CurrentPluginName',
            'persistence' => ['storagePid' => '3'],
        ]);
        self::assertSame('3,4', $this->getStoragePid());
    }

    #[Test]
    public function changedRequestResetsBackendConfigurationCache(): void
    {
        self::assertSame('2,3,4', $this->getStoragePid());
        $this->addStorageSubPage();

        $this->subject->setRequest($this->createBackendRequest());

        self::assertSame('2,3,4,5', $this->getStoragePid());
    }

    #[Test]
    public function changedBackendUserResetsBackendConfigurationCache(): void
    {
        self::assertSame('2,3,4', $this->getStoragePid());
        $this->addStorageSubPage();

        $this->setUpAdminUser();

        self::assertSame('2,3,4,5', $this->getStoragePid());
    }

    private function getStoragePid(?string $extensionName = null, ?string $pluginName = null): string
    {
        $configuration = $this->subject->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, $extensionName, $pluginName);
        return (string)$configuration['persistence']['storagePid'];
    }

    private function createBackendRequest(): ServerRequest
    {
        return new ServerRequest()
            ->withQueryParams(['id' => 1])
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }

    private function setUpAdminUser(): void
    {
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->user = ['admin' => true];
    }

    private function addStorageSubPage(): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable('pages')
            ->insert('pages', ['uid' => 5, 'pid' => 2, 'title' => 'Added storage sub', 'doktype' => 254]);
    }
}
