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

namespace TYPO3\CMS\Backend\Tests\Functional\ElementBrowser;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\ElementBrowser\DatabaseBrowser;
use TYPO3\CMS\Backend\ElementBrowser\ElementBrowserRegistry;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DatabaseBrowserTest extends FunctionalTestCase
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
                $scenarioFile = __DIR__ . '/../Fixtures/CommonScenario.yaml';
                $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
                $writer = DataHandlerWriter::withBackendUser($this->backendUser);
                $writer->invokeFactory($factory);
                DatabaseBrowserTest::failIfArrayIsNotEmpty($writer->getErrors());
            },
            function () {
                $this->backendUser = $this->setUpBackendUser(1);
                $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
            }
        );

        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1100, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', '/fr'),
                $this->buildLanguageConfiguration('FR-CA', '/fr-ca'),
                $this->buildLanguageConfiguration('ES', '/es'),
            ]
        );
    }

    #[Test]
    public function recordBrowserIgnoresStaleRecordsModuleLanguageFilterAndShowsAllLanguages(): void
    {
        // Simulate a backend user who previously restricted the Web > Records module
        // to a single non-default language. Both the Records module and the "Insert
        // Records" element browser persist their state under the same "records"
        // module identifier.
        $this->backendUser->pushModuleData('records', ['languages' => [1]]);

        $request = new ServerRequest('http://localhost/wizard/record/browse')
            ->withQueryParams([
                'mode' => 'db',
                'expandPage' => 1100,
                'allowedTypes' => 'tt_content',
                'contentOnly' => 1,
            ])
            ->withAttribute('route', new Route('/wizard/record/browse', ['_identifier' => 'wizard_element_browser']))
            ->withAttribute('site', $this->get(SiteFinder::class)->getSiteByIdentifier('test'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        $GLOBALS['TYPO3_REQUEST'] = $request;

        $registry = $this->get(ElementBrowserRegistry::class);
        $browser = $registry->getElementBrowser('db');
        self::assertInstanceOf(DatabaseBrowser::class, $browser);
        $browser->setRequest($request);
        [$modData] = $browser->processSessionData([]);
        $this->backendUser->pushModuleData('browse_links.php', $modData);

        $content = $browser->render();

        // Content from the default language and BOTH non-default languages must be
        // shown, even though the "records" module data above only allows language 1.
        self::assertStringContainsString('EN: Content Element #1', $content);
        self::assertStringContainsString('EN: Content Element #2', $content);
        self::assertStringContainsString('FR: Content Element #1', $content);
        self::assertStringContainsString('FR-CA: Content Element #1', $content);
    }
}
