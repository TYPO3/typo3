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
use TYPO3\CMS\Core\DataHandling\DataHandler;
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ElementHistoryCategories.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->writeSiteConfiguration('main', $this->buildSiteConfiguration(1, '/'));
    }

    private function renderHistoryOfRootPage(): string
    {
        return $this->renderHistoryOf('pages:1');
    }

    private function renderHistoryOf(string $element): string
    {
        $request = (new ServerRequest('https://example.com/typo3/record/history', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $this->get(Router::class)->getRoute('record_history'))
            ->withQueryParams(['element' => $element]);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        return (string)$this->get(ElementHistoryController::class)->mainAction($request)->getBody();
    }

    #[Test]
    public function mmRelationChangeShowsTheStoredRelationsInsteadOfTheCurrentOnes(): void
    {
        $this->setRootPageCategories('1');
        $this->setRootPageCategories('1,2');

        $body = $this->renderHistoryOfRootPage();

        self::assertMatchesRegularExpression('#<ins>[^<]*Category B#', $body);
    }

    private function setRootPageCategories(string $categories): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['pages' => [1 => ['categories' => $categories]]], []);
        $dataHandler->process_datamap();
    }
}
