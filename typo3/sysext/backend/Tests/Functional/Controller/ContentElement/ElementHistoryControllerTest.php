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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller\ContentElement;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\ContentElement\ElementHistoryController;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ElementHistoryControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ElementHistoryUserTypes.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->writeSiteConfiguration('main', $this->buildSiteConfiguration(1, '/'));
    }

    private function renderHistoryOfRootPage(): string
    {
        $request = (new ServerRequest('https://example.com/typo3/record/history', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $this->get(Router::class)->getRoute('record_history'))
            ->withQueryParams(['element' => 'pages:1']);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        return (string)$this->get(ElementHistoryController::class)->mainAction($request)->getBody();
    }

    #[Test]
    public function backendUserIsShownForBackendEntries(): void
    {
        self::assertStringContainsString('Administrator', $this->renderHistoryOfRootPage());
    }

    #[Test]
    public function frontendUserIsNotShownAsBackendUser(): void
    {
        $body = $this->renderHistoryOfRootPage();

        // The frontend user uid 5 must not be attributed to backend user uid 5
        self::assertStringNotContainsString('editorbob', $body);
        self::assertStringNotContainsString('Editor Bob', $body);
        self::assertStringContainsString('Frontend user', $body);
    }
}
