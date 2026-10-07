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
                    'TCEMAIN.linkHandler.properties.target.default = global-target',
                    'TCEMAIN.linkHandler.properties.cssClass.default = global-class',
                    'TCEMAIN.linkHandler.url.target.default = url-target',
                    'TCEMAIN.linkHandler.url.cssClass.default = url-class',
                ]),
            ],
            ['uid' => 1]
        );
    }

    public static function defaultsOfDisplayedLinkHandlerArePrefilledDataProvider(): \Generator
    {
        yield 'page tab opened by default' => [
            [],
            [],
            'global-target',
            'global-class',
        ];
        yield 'url tab selected explicitly' => [
            ['act' => 'url'],
            [],
            'url-target',
            'url-class',
        ];
        yield 'url tab opened as the only allowed type' => [
            [],
            ['allowedTypes' => 'url'],
            'url-target',
            'url-class',
        ];
    }

    #[DataProvider('defaultsOfDisplayedLinkHandlerArePrefilledDataProvider')]
    #[Test]
    public function defaultsOfDisplayedLinkHandlerArePrefilled(array $queryParams, array $fieldParams, string $expectedTarget, string $expectedClass): void
    {
        $content = $this->renderLinkBrowser($queryParams, $fieldParams, '');

        self::assertMatchesRegularExpression('/name="ltarget"[^>]*value="' . $expectedTarget . '"/', $content);
        self::assertMatchesRegularExpression('/name="lclass"[^>]*value="' . $expectedClass . '"/', $content);
    }

    #[Test]
    public function defaultsAreNotAppliedToExistingLink(): void
    {
        $content = $this->renderLinkBrowser([], [], 'https://typo3.org');

        self::assertMatchesRegularExpression('/name="ltarget"[^>]*value=""/', $content);
        self::assertMatchesRegularExpression('/name="lclass"[^>]*value=""/', $content);
    }

    private function renderLinkBrowser(array $queryParams, array $fieldParams, string $currentValue): string
    {
        $request = (new ServerRequest('https://www.example.com/typo3/wizard/link/browse', 'GET'))
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
                    'params' => $fieldParams,
                ],
            ]));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return (string)$this->get(LinkBrowserController::class)->mainAction($request)->getBody();
    }
}
