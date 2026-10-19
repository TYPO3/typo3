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
use TYPO3\CMS\Backend\Controller\Page\SortSubPagesController;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SortSubPagesControllerTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SortSubPages.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function creationDateButtonOrdersPagesByCreationDateThenUid(): void
    {
        $subject = $this->get(SortSubPagesController::class);
        $html = (string)$subject->mainAction($this->createRequest(1))->getBody();
        $label = $GLOBALS['LANG']->sL('backend.pages_sort:sort_by_create-time.title');
        $button = \Dom\HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelector('a[data-title="' . $label . '"]');
        self::assertNotNull($button);
        parse_str((string)parse_url((string)$button->getAttribute('href'), PHP_URL_QUERY), $buttonQueryParams);

        $subject->mainAction($this->createRequest(1, $buttonQueryParams['newSortBy'] ?? null));

        // Oldest first. Equal crdate keeps the lower uid first.
        self::assertSame([5, 3, 4, 2], $this->subPageUidsInSortingOrder(1));
    }

    private function createRequest(int $parentPageUid, ?string $newSortBy = null): ServerRequest
    {
        $queryParams = ['id' => $parentPageUid];
        if ($newSortBy !== null) {
            $queryParams['newSortBy'] = $newSortBy;
        }
        $request = new ServerRequest('https://example.com/typo3/pages/sort', 'GET')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $this->get(Router::class)->getRoute('pages_sort'))
            ->withQueryParams($queryParams);

        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }

    /**
     * @return list<int>
     */
    private function subPageUidsInSortingOrder(int $parentPageUid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('pages');

        return array_map(intval(...), $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($parentPageUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('sys_language_uid', 0),
            )
            ->orderBy('sorting')
            ->executeQuery()
            ->fetchFirstColumn());
    }
}
