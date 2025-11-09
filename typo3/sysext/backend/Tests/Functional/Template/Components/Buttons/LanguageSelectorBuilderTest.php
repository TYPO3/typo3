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

namespace TYPO3\CMS\Backend\Tests\Functional\Template\Components\Buttons;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Context\PageContext;
use TYPO3\CMS\Backend\Context\PageContextFactory;
use TYPO3\CMS\Backend\Template\Components\Buttons\LanguageSelectorBuilder;
use TYPO3\CMS\Backend\Template\Components\Buttons\LanguageSelectorMode;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LanguageSelectorBuilderTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8'],
        'DE' => ['id' => 2, 'title' => 'German', 'locale' => 'de_DE.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];
    private BackendUserAuthentication $backendUser;
    private LanguageSelectorBuilder $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withDatabaseSnapshot(
            function () {
                $this->importCSVDataSet(__DIR__ . '/../../../Fixtures/be_users.csv');
                $this->backendUser = $this->setUpBackendUser(1);
                $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
                $scenarioFile = __DIR__ . '/../../../Fixtures/CommonScenario.yaml';
                $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
                $writer = DataHandlerWriter::withBackendUser($this->backendUser);
                $writer->invokeFactory($factory);
                self::failIfArrayIsNotEmpty($writer->getErrors());
            },
            function () {
                $this->backendUser = $this->setUpBackendUser(1);
                $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
            }
        );

        $this->writeSiteConfiguration(
            'test-site',
            $this->buildSiteConfiguration(1000, 'https://example.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', '/fr/', ['EN']),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN']),
            ]
        );

        $this->subject = $this->get(LanguageSelectorBuilder::class);
    }

    #[Test]
    public function singleSelectModeBuildsRadioButtons(): void
    {
        $pageContext = $this->createPageContext(1100, [0]);
        $urlBuilder = fn(array $languageIds): string => '/test?lang=' . implode(',', $languageIds);

        $button = $this->subject->build($pageContext, LanguageSelectorMode::SINGLE_SELECT, $urlBuilder);

        self::assertTrue($button->isValid());
        $html = $button->render();

        // Should contain language names as dropdown item labels
        self::assertMatchesRegularExpression('/<a[^>]*>.*English.*<\/a>/s', $html);
        self::assertMatchesRegularExpression('/<a[^>]*>.*French.*<\/a>/s', $html);
        self::assertMatchesRegularExpression('/<a[^>]*>.*German.*<\/a>/s', $html);

        // Should not contain toggle all in single-select mode
        self::assertDoesNotMatchRegularExpression('/>Check all.*<\/a>/s', $html);
        self::assertDoesNotMatchRegularExpression('/>Uncheck all.*<\/a>/s', $html);
    }

    #[Test]
    public function multiSelectModeBuildsToggles(): void
    {
        $pageContext = $this->createPageContext(1100, [0, 1]);
        $urlBuilder = fn(array $languageIds): string => '/test?lang=' . implode(',', $languageIds);

        $button = $this->subject->build($pageContext, LanguageSelectorMode::MULTI_SELECT, $urlBuilder);

        self::assertTrue($button->isValid());
        $html = $button->render();

        // Should contain language names as dropdown item labels
        self::assertMatchesRegularExpression('/<a[^>]*>.*English.*<\/a>/s', $html);
        self::assertMatchesRegularExpression('/<a[^>]*>.*French.*<\/a>/s', $html);
        self::assertMatchesRegularExpression('/<a[^>]*>.*German.*<\/a>/s', $html);
    }

    #[Test]
    public function multiSelectModeShowsToggleAllButton(): void
    {
        $pageContext = $this->createPageContext(1100, [0, 1]);
        $urlBuilder = fn(array $languageIds): string => '/test?lang=' . implode(',', $languageIds);

        $button = $this->subject->build($pageContext, LanguageSelectorMode::MULTI_SELECT, $urlBuilder);

        $html = $button->render();

        // Should contain toggle all option as link label
        self::assertMatchesRegularExpression('/<a[^>]*>.*(?:Check all|Uncheck all).*<\/a>/s', $html);
    }

    #[Test]
    public function multiSelectModeCanHideToggleAllButton(): void
    {
        $pageContext = $this->createPageContext(1100, [0, 1]);
        $urlBuilder = fn(array $languageIds): string => '/test?lang=' . implode(',', $languageIds);

        $button = $this->subject->build($pageContext, LanguageSelectorMode::MULTI_SELECT, $urlBuilder, false);

        $html = $button->render();

        // Should not contain toggle all option as link label
        self::assertDoesNotMatchRegularExpression('/<a[^>]*>.*Check all.*<\/a>/s', $html);
        self::assertDoesNotMatchRegularExpression('/<a[^>]*>.*Uncheck all.*<\/a>/s', $html);
    }

    #[Test]
    public function defaultLanguageIsMarkedAsDisabledAndShowsHelptextInMultiSelect(): void
    {
        $pageContext = $this->createPageContext(1100, [0, 1]);
        $urlBuilder = fn(array $languageIds): string => '/test?lang=' . implode(',', $languageIds);

        $button = $this->subject->build($pageContext, LanguageSelectorMode::MULTI_SELECT, $urlBuilder);

        $html = $button->render();

        // Check for a tag with disabled="disabled" attribute
        self::assertMatchesRegularExpression(
            '/<a[^>]*disabled="disabled"[^>]*>/',
            $html,
            'Default language link should have disabled attribute'
        );

        // Check for proper aria-label containing help text
        self::assertMatchesRegularExpression(
            '/<a[^>]*aria-label="English \(Default language is always shown\)"[^>]*>/',
            $html,
            'Default language link should have aria-label with help text'
        );
    }

    #[Test]
    public function selectorLabelShowsSingleLanguageWhenOnlyDefaultSelected(): void
    {
        $pageContext = $this->createPageContext(1100, [0]);
        $urlBuilder = fn(array $languageIds): string => '/test?lang=' . implode(',', $languageIds);

        $button = $this->subject->build($pageContext, LanguageSelectorMode::SINGLE_SELECT, $urlBuilder);

        $html = $button->render();

        // Button should show language name
        self::assertMatchesRegularExpression('/<button[^>]*>.*English.*<\/button>/s', $html);
        // Button should contain a flag icon
        self::assertMatchesRegularExpression('/<span[^>]*class="[^"]*icon-flags-[^"]*"[^>]*>/s', $html);
    }

    #[Test]
    public function selectorLabelShowsCountWhenMultipleLanguagesSelected(): void
    {
        $pageContext = $this->createPageContext(1100, [0, 1, 2]);
        $urlBuilder = fn(array $languageIds): string => '/test?lang=' . implode(',', $languageIds);

        $button = $this->subject->build($pageContext, LanguageSelectorMode::MULTI_SELECT, $urlBuilder);

        $html = $button->render();

        // Button should show count
        self::assertMatchesRegularExpression('/<button[^>]*>.*\(3\).*<\/button>/s', $html);
        // Button should contain multiple flags icon
        self::assertMatchesRegularExpression('/<span[^>]*class="[^"]*icon-flags-multiple[^"]*"[^>]*>/s', $html);
    }

    #[Test]
    public function accessibilityAttributesAreApplied(): void
    {
        $pageContext = $this->createPageContext(1100, [0, 1]);
        $urlBuilder = fn(array $languageIds): string => '/test?lang=' . implode(',', $languageIds);

        $button = $this->subject->build($pageContext, LanguageSelectorMode::MULTI_SELECT, $urlBuilder);

        $html = $button->render();

        // Check for toggle all button with both title and aria-label (attributes can be in any order)
        self::assertMatchesRegularExpression(
            '/<a[^>]*aria-label="(Select all available languages|Deselect all languages except default)"[^>]*title="\1"[^>]*>/',
            $html,
            'Toggle all button should have matching title and aria-label attributes'
        );
    }

    #[Test]
    public function toggleAllShowsCheckAllWhenNotAllSelected(): void
    {
        $pageContext = $this->createPageContext(1100, [0]); // Only default selected
        $urlBuilder = fn(array $languageIds): string => '/test?lang=' . implode(',', $languageIds);

        $button = $this->subject->build($pageContext, LanguageSelectorMode::MULTI_SELECT, $urlBuilder);

        $html = $button->render();

        // Check for "Check all" label in link and accessibility text in attributes
        self::assertMatchesRegularExpression('/<a[^>]*>.*Check all.*<\/a>/s', $html);
        self::assertMatchesRegularExpression('/<a[^>]*(?:title|aria-label)="Select all available languages"[^>]*>/s', $html);
    }

    #[Test]
    public function toggleAllShowsUncheckAllWhenAllSelected(): void
    {
        $pageContext = $this->createPageContext(1100, [0, 1, 2]); // All languages selected
        $urlBuilder = fn(array $languageIds): string => '/test?lang=' . implode(',', $languageIds);

        $button = $this->subject->build($pageContext, LanguageSelectorMode::MULTI_SELECT, $urlBuilder);

        $html = $button->render();

        // Check for "Uncheck all" label in link and accessibility text in attributes
        self::assertMatchesRegularExpression('/<a[^>]*>.*Uncheck all.*<\/a>/s', $html);
        self::assertMatchesRegularExpression('/<a[^>]*(?:title|aria-label)="Deselect all languages except default"[^>]*>/s', $html);
    }

    #[Test]
    public function onlyAvailableLanguagesAreShown(): void
    {
        $pageContext = $this->createPageContext(1100, [0]);
        $urlBuilder = fn(array $languageIds): string => '/test?lang=' . implode(',', $languageIds);

        $button = $this->subject->build($pageContext, LanguageSelectorMode::SINGLE_SELECT, $urlBuilder);

        self::assertTrue($button->isValid());
        $html = $button->render();

        // Should only show languages that exist or are available (EN, FR, DE from site config) as dropdown item labels
        self::assertMatchesRegularExpression('/<a[^>]*>.*English.*<\/a>/s', $html);
        self::assertMatchesRegularExpression('/<a[^>]*>.*French.*<\/a>/s', $html);
        self::assertMatchesRegularExpression('/<a[^>]*>.*German.*<\/a>/s', $html);
    }

    private function createPageContext(int $pageId, array $selectedLanguageIds): PageContext
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('test-site');

        $request = (new ServerRequest('https://example.com/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withQueryParams(['id' => $pageId, 'languages' => $selectedLanguageIds]);

        return $this->get(PageContextFactory::class)->createFromRequest($request, $pageId, $this->backendUser);
    }
}
