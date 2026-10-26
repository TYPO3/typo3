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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller\Page;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\Page\NewMultiplePagesController;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class NewMultiplePagesControllerTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function creatingPagesRedirectsSoThatReloadingTheResultDoesNotCreatePagesAgain(): void
    {
        $subject = $this->get(NewMultiplePagesController::class);
        $postRequest = $this->createRequest('POST', ['id' => 1])
            ->withParsedBody(['pages' => ['NEW1' => ['title' => 'Toto1', 'doktype' => 1]]]);

        $response = $subject->mainAction($postRequest);

        self::assertSame(303, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        self::assertStringEndsWith('/pages/new', (string)parse_url($location, PHP_URL_PATH));
        parse_str((string)parse_url($location, PHP_URL_QUERY), $redirectQueryParams);
        self::assertSame('1', $redirectQueryParams['id'] ?? null);
        self::assertSame('1', $redirectQueryParams['created'] ?? null);
        self::assertSame(1, $this->countSubPagesWithTitle(1, 'Toto1'));

        // The redirect target renders the result view and does not create the pages again
        $getResponse = $subject->mainAction($this->createRequest('GET', $redirectQueryParams));

        self::assertSame(200, $getResponse->getStatusCode());
        $body = (string)$getResponse->getBody();
        self::assertStringContainsString($this->translate('pagesCreated.title'), $body);
        self::assertStringContainsString('Toto1', $body);
        self::assertSame(1, $this->countSubPagesWithTitle(1, 'Toto1'));
    }

    #[Test]
    public function submittingOnlyEmptyTitlesRedirectsToNoPagesCreatedResult(): void
    {
        $subject = $this->get(NewMultiplePagesController::class);
        $postRequest = $this->createRequest('POST', ['id' => 1])
            ->withParsedBody(['pages' => ['NEW1' => ['title' => '  ', 'doktype' => 1]]]);

        $response = $subject->mainAction($postRequest);

        self::assertSame(303, $response->getStatusCode());
        parse_str((string)parse_url($response->getHeaderLine('Location'), PHP_URL_QUERY), $redirectQueryParams);
        self::assertSame('0', $redirectQueryParams['created'] ?? null);

        $getResponse = $subject->mainAction($this->createRequest('GET', $redirectQueryParams));

        self::assertSame(200, $getResponse->getStatusCode());
        $body = (string)$getResponse->getBody();
        self::assertStringContainsString($this->translate('noPagesCreated.title'), $body);
        self::assertStringNotContainsString($this->translate('pagesCreated.title'), $body);
    }

    private function translate(string $key): string
    {
        return $GLOBALS['LANG']->sL('LLL:EXT:backend/Resources/Private/Language/locallang_pages_new.xlf:' . $key);
    }

    private function createRequest(string $method, array $queryParams): ServerRequest
    {
        $request = (new ServerRequest('https://example.com/typo3/pages/new', $method))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $this->get(Router::class)->getRoute('pages_new'))
            ->withQueryParams($queryParams);

        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }

    private function countSubPagesWithTitle(int $parentPageUid, string $title): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder
            ->count('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($parentPageUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'title',
                    $queryBuilder->createNamedParameter($title)
                ),
                $queryBuilder->expr()->eq('deleted', 0),
            )
            ->executeQuery()
            ->fetchOne();
    }
}
