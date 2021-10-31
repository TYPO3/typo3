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

namespace TYPO3\CMS\Frontend\Tests\Functional\Aspect;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\AbstractTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

final class FileMetadataOverlayAspectTest extends AbstractTestCase
{
    private const VALUE_BackendUserId = 1;
    private const VALUE_WorkspaceId = 1;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8'],
        'ES' => ['id' => 3, 'title' => 'Spanish', 'locale' => 'es_ES.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://acme.us/'),
                $this->buildLanguageConfiguration('FR', 'https://acme.fr/', ['EN']),
                $this->buildLanguageConfiguration('ES', 'https://acme.es/', [], 'free'),
            ]
        );

        $this->withDatabaseSnapshot(function () {
            $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
            $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');

            $fileIdentifier = '/kasper-skarhoj1.jpg';
            copy(
                __DIR__ . '/../Fixtures/Images/kasper-skarhoj1.jpg',
                Environment::getPublicPath() . '/fileadmin/kasper-skarhoj1.jpg'
            );
            $conn = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_file');
            $conn->insert(
                'sys_file',
                [
                    'identifier' => $fileIdentifier,
                    'storage' => 1,
                    'name' => 'kasper-skarhoj1.jpg',
                    'mime_type' => 'image/jpeg',
                    'size' => 12345,
                ]
            );
            $backendUser = $this->setUpBackendUser(1);
            $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');

            $scenarioFile = __DIR__ . '/Fixtures/MetadataScenario.yaml';
            $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
            $writer = DataHandlerWriter::withBackendUser($backendUser);
            $writer->invokeFactory($factory);
            static::failIfArrayIsNotEmpty(
                $writer->getErrors()
            );

            $this->setUpFrontendRootPage(
                1000,
                [
                    'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript',
                    'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/JsonRenderer.typoscript',
                    'typo3/sysext/frontend/Tests/Functional/Aspect/Fixtures/JsonRenderer.typoscript',
                ],
                ['title' => 'ACME Root']
            );
        });
    }

    #[Test]
    public function metadataIsRenderedInDefaultLanguage(): void
    {
        $contents = $this->executeRequestAndGetContents('https://acme.us/');
        self::assertEquals(
            'EN file title',
            $contents['tt_content:10']['image']['sys_file_reference:10000']['title']
        );
    }

    #[Test]
    public function metadataIsRenderedInDefaultInWorkspace(): void
    {
        $contents = $this->executeRequestAndGetContents('https://acme.us/', $this->prepareWorkspaceRequest());
        self::assertEquals(
            'EN workspaced title',
            $contents['tt_content:10']['image']['sys_file_reference:10000']['title']
        );
    }

    #[Test]
    public function metadataIsRenderedInFrenchTranslationWithOverlay(): void
    {
        $contents = $this->executeRequestAndGetContents('https://acme.fr/');
        self::assertEquals(
            'FR file title',
            $contents['tt_content:10']['image']['sys_file_reference:10001']['title']
        );
    }

    #[Test]
    public function metadataIsRenderedInFrenchTranslationWithOverlayInWorkspace(): void
    {
        $contents = $this->executeRequestAndGetContents('https://acme.fr/', $this->prepareWorkspaceRequest());
        self::assertEquals(
            'FR workspaced title',
            $contents['tt_content:10']['image']['sys_file_reference:10001']['title']
        );
    }

    #[Test]
    public function metadataIsRenderedInSpanishTranslationInFreeMode(): void
    {
        $contents = $this->executeRequestAndGetContents('https://acme.es/');
        self::assertEquals(
            'ES file title',
            $contents['tt_content:12']['image']['sys_file_reference:10002']['title']
        );
    }

    #[Test]
    public function metadataIsRenderedInSpanishTranslationInFreeModeinWorkspace(): void
    {
        $contents = $this->executeRequestAndGetContents('https://acme.es/', $this->prepareWorkspaceRequest());
        self::assertEquals(
            'ES workspaced title',
            $contents['tt_content:12']['image']['sys_file_reference:10002']['title']
        );
    }

    protected function executeRequestAndGetContents(string $uri, ?InternalRequestContext $internalRequestContext = null): array
    {
        $response = $this->executeFrontendSubRequest((new InternalRequest($uri)), $internalRequestContext);
        $bodyData = json_decode((string)$response->getBody(), true);
        return $bodyData['Default']['structure']['pages:1000']['__contents'];
    }

    protected function prepareWorkspaceRequest(): InternalRequestContext
    {
        return (new InternalRequestContext())
            ->withBackendUserId(self::VALUE_BackendUserId)
            ->withWorkspaceId(self::VALUE_WorkspaceId);
    }
}
