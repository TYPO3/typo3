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

namespace TYPO3\CMS\Backend\Tests\Functional\LinkHandler;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\LinkBrowserController;
use TYPO3\CMS\Backend\LinkHandler\RecordLinkHandler;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RecordLinkHandlerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8'],
        'FR-CA' => ['id' => 2, 'title' => 'French (CA)', 'locale' => 'fr_CA.UTF8'],
        'ES' => ['id' => 3, 'title' => 'Spanish', 'locale' => 'es_ES.UTF8'],
    ];
    protected array $coreExtensionsToLoad = ['workspaces'];
    private BackendUserAuthentication $backendUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withDatabaseSnapshot(
            function () {
                $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
                $this->backendUser = $this->setUpBackendUser(1);
                $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
                $factory = DataHandlerFactory::fromYamlFile(__DIR__ . '/../Fixtures/CommonScenario.yaml');
                $writer = DataHandlerWriter::withBackendUser($this->backendUser);
                $writer->invokeFactory($factory);
                RecordLinkHandlerTest::failIfArrayIsNotEmpty($writer->getErrors());
                $this->getConnectionPool()->getConnectionForTable('pages')->update(
                    'pages',
                    [
                        'TSconfig' => implode("\n", [
                            'TCEMAIN.linkHandler.content.handler = ' . RecordLinkHandler::class,
                            'TCEMAIN.linkHandler.content.label = Content',
                            'TCEMAIN.linkHandler.content.configuration.table = tt_content',
                        ]),
                    ],
                    ['uid' => 1000]
                );
                $this->get(CacheManager::class)->getCache('runtime')->flush();
            },
            function () {
                $this->backendUser = $this->setUpBackendUser(1);
                $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
            }
        );

        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1000, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', '/fr'),
                $this->buildLanguageConfiguration('FR-CA', '/fr-ca'),
                $this->buildLanguageConfiguration('ES', '/es'),
            ]
        );
    }

    #[Test]
    public function recordLinkHandlerIgnoresStaleRecordsModuleLanguageFilterAndShowsAllLanguages(): void
    {
        // Simulate a backend user who previously restricted the Web > Records module
        // to a single non-default language. Both the Records module and the record
        // tab of the link browser read their state from the same "records" module
        // identifier.
        $this->backendUser->pushModuleData('records', ['languages' => [1]]);

        $request = new ServerRequest('https://www.example.com/typo3/wizard/link/browse', 'GET')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', new NormalizedParams([], [], '', ''))
            ->withAttribute('route', new Route('/wizard/link/browse', ['_identifier' => 'wizard_link']))
            ->withQueryParams([
                'act' => 'content',
                'expandPage' => 1100,
                'P' => [
                    'table' => 'tt_content',
                    'uid' => 1,
                    'pid' => 1100,
                    'field' => 'header_link',
                    'formName' => 'editform',
                    'itemName' => 'data[tt_content][1][header_link]',
                    'currentValue' => '',
                ],
            ]);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $content = (string)$this->get(LinkBrowserController::class)->mainAction($request)->getBody();

        // Content from the default language and BOTH non-default languages must be
        // shown, even though the "records" module data above only allows language 1.
        self::assertStringContainsString('EN: Content Element #1', $content);
        self::assertStringContainsString('EN: Content Element #2', $content);
        self::assertStringContainsString('FR: Content Element #1', $content);
        self::assertStringContainsString('FR-CA: Content Element #1', $content);
    }
}
