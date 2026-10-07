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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\LinkBrowserController;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LinkBrowserControllerTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->getConnectionPool()->getConnectionForTable('pages')->update(
            'pages',
            [
                'TSconfig' => implode("\n", [
                    'TCEMAIN.linkHandler.properties.cssClass.items.10.value = global-class',
                    'TCEMAIN.linkHandler.url.cssClass.items.10.value = url-class',
                ]),
            ],
            ['uid' => 1]
        );
    }

    public static function cssClassItemsOfDisplayedLinkHandlerAreRenderedDataProvider(): \Generator
    {
        yield 'new link opens page tab' => [
            '',
            [],
            'global-class',
            'url-class',
        ];
        yield 'existing external link opens url tab' => [
            'https://typo3.org',
            [],
            'url-class',
            'global-class',
        ];
        yield 'url tab selected explicitly' => [
            '',
            ['act' => 'url'],
            'url-class',
            'global-class',
        ];
    }

    #[DataProvider('cssClassItemsOfDisplayedLinkHandlerAreRenderedDataProvider')]
    #[Test]
    public function cssClassItemsOfDisplayedLinkHandlerAreRendered(string $currentValue, array $queryParams, string $expectedClass, string $unexpectedClass): void
    {
        $request = new ServerRequest('https://www.example.com/typo3/wizard/link/browse', 'GET')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', new NormalizedParams([], [], '', ''))
            ->withAttribute('route', new Route('/wizard/link/browse', ['_identifier' => 'wizard_link']))
            ->withQueryParams(array_merge($queryParams, [
                'P' => [
                    'table' => 'tt_content',
                    'uid' => 1,
                    'pid' => 1,
                    'field' => 'header_link',
                    'formName' => 'editform',
                    'itemName' => 'data[tt_content][1][header_link]',
                    'currentValue' => $currentValue,
                ],
            ]));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $content = (string)$this->get(LinkBrowserController::class)->mainAction($request)->getBody();

        self::assertStringContainsString('<typo3-backend-combobox-choice value="' . $expectedClass . '">', $content);
        self::assertStringNotContainsString('<typo3-backend-combobox-choice value="' . $unexpectedClass . '">', $content);
    }
}
