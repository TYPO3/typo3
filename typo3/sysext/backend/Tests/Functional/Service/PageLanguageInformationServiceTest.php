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

namespace TYPO3\CMS\Backend\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Service\PageLanguageInformationService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageLanguageInformationServiceTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Authentication/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users_record_list_editor.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/orphaned_language_records.csv');
        $this->site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
                ['languageId' => 1, 'locale' => 'de-DE', 'base' => '/de', 'title' => 'German'],
                ['languageId' => 2, 'locale' => 'fr-FR', 'base' => '/fr', 'title' => 'French'],
                ['languageId' => 3, 'locale' => 'es-ES', 'base' => '/es', 'title' => 'Spanish'],
            ],
        ]);
    }

    #[Test]
    public function recordsInLanguagesWithoutPageTranslationAreOrphaned(): void
    {
        self::assertSame([2, 5, 6], $this->getOrphanedLanguageIds(1, $this->site, $this->setUpBackendUser(1)));
    }

    #[Test]
    public function recordsOfTheCurrentWorkspaceAreConsidered(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->setWorkspace(1);

        self::assertSame([2, 3, 5, 6], $this->getOrphanedLanguageIds(1, $this->site, $backendUser));
    }

    #[Test]
    public function recordsMovedAwayInTheCurrentWorkspaceAreNotOrphaned(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        self::assertSame([9], $this->getOrphanedLanguageIds(5, $this->site, $backendUser));
        self::assertSame([], $this->getOrphanedLanguageIds(6, $this->site, $backendUser));

        $backendUser->setWorkspace(1);
        self::assertSame([], $this->getOrphanedLanguageIds(5, $this->site, $backendUser));
        self::assertSame([9], $this->getOrphanedLanguageIds(6, $this->site, $backendUser));
    }

    #[Test]
    public function translationsWithoutParentOnThePageAreNotOrphaned(): void
    {
        self::assertSame([], $this->getOrphanedLanguageIds(9, $this->site, $this->setUpBackendUser(1)));
    }

    #[Test]
    public function recordsOfTablesHiddenInRecordsModuleAreIgnored(): void
    {
        self::assertSame([6], $this->getOrphanedLanguageIds(7, $this->site, $this->setUpBackendUser(1)));
    }

    #[Test]
    public function translationsHiddenInRecordsModuleAreIgnored(): void
    {
        self::assertSame([5], $this->getOrphanedLanguageIds(8, $this->site, $this->setUpBackendUser(1)));
    }

    #[Test]
    public function pageTranslationInLanguageNotConfiguredInSiteIsOrphaned(): void
    {
        self::assertSame([7], $this->getOrphanedLanguageIds(3, $this->site, $this->setUpBackendUser(1)));
    }

    #[Test]
    public function recordsOfTablesTheUserMayNotListAreIgnored(): void
    {
        self::assertSame([2, 5], $this->getOrphanedLanguageIds(1, $this->site, $this->setUpBackendUser(9)));
    }

    #[Test]
    public function recordsInLanguagesTheUserMayNotAccessAreIgnored(): void
    {
        self::assertSame([2], $this->getOrphanedLanguageIds(1, $this->site, $this->setUpBackendUser(10)));
    }

    #[Test]
    public function translatedRecordsOnRootLevelAreNotOrphaned(): void
    {
        self::assertSame([], $this->getOrphanedLanguageIds(0, new NullSite(), $this->setUpBackendUser(1)));
    }

    #[Test]
    public function orphanedLanguagesDoNotBecomeSelectable(): void
    {
        $languageInformation = $this->get(PageLanguageInformationService::class)
            ->getLanguageInformationForPage(3, $this->site, $this->setUpBackendUser(1));

        self::assertSame([0], $languageInformation->getAllExistingLanguageIds());
    }

    #[Test]
    public function orphanedLanguageMessageNamesUntranslatedSiteLanguagesAndHidesUnconfiguredIds(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $service = $this->get(PageLanguageInformationService::class);
        $languageInformation = $service->getLanguageInformationForPage(1, $this->site, $backendUser);

        $message = $service->getOrphanedLanguageMessage(
            $languageInformation,
            $service->getOrphanedLanguageIds($languageInformation, $backendUser),
            $this->get(LanguageServiceFactory::class)->create('default')
        );

        self::assertSame(
            'This page contains records in languages it has not been translated into: French.'
            . ' This page contains records in languages that are not configured for this site.',
            $message
        );
    }

    #[Test]
    public function orphanedLanguageMessageOmitsSentenceForUnconfiguredLanguagesIfThereAreNone(): void
    {
        $service = $this->get(PageLanguageInformationService::class);
        $languageInformation = $service->getLanguageInformationForPage(1, $this->site, $this->setUpBackendUser(1));

        self::assertSame(
            'This page contains records in languages it has not been translated into: French, Spanish.',
            $service->getOrphanedLanguageMessage($languageInformation, [2, 3], $this->get(LanguageServiceFactory::class)->create('default'))
        );
    }

    private function getOrphanedLanguageIds(int $pageId, SiteInterface $site, BackendUserAuthentication $backendUser): array
    {
        $service = $this->get(PageLanguageInformationService::class);
        return $service->getOrphanedLanguageIds(
            $service->getLanguageInformationForPage($pageId, $site, $backendUser),
            $backendUser
        );
    }
}
