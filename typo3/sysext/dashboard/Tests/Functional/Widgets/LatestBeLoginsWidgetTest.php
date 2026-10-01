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

namespace TYPO3\CMS\Dashboard\Tests\Functional\Widgets;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Settings\Settings;
use TYPO3\CMS\Core\SysLog\Action\Login as SystemLogLoginAction;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\LatestBeLoginsWidget;
use TYPO3\CMS\Dashboard\Widgets\Provider\LatestBeLoginsDataProvider;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfiguration;
use TYPO3\CMS\Dashboard\Widgets\WidgetContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LatestBeLoginsWidgetTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['dashboard'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Newest first: admin (500), editor_with_one_group (400), editor_with_both_groups (300),
        // editor_no_group (200), admin (100)
        foreach ([[1, 100], [2, 200], [3, 300], [4, 400], [1, 500]] as [$userId, $timestamp]) {
            $this->get(ConnectionPool::class)->getConnectionForTable('sys_log')->insert('sys_log', [
                'tstamp' => $timestamp,
                'userid' => $userId,
                'type' => SystemLogType::LOGIN,
                'action' => SystemLogLoginAction::LOGIN,
                'details' => 'User logged in',
            ]);
        }
    }

    #[Test]
    public function firstPageShowsNewestLoginsLimitedByTheSetting(): void
    {
        $result = $this->render(['limit' => 2]);

        self::assertStringContainsString('editor_with_one_group', $result);
        self::assertStringNotContainsString('editor_with_both_groups', $result);
        self::assertStringContainsString('Page 1 of 3', $result);
        self::assertMatchesRegularExpression('/data-widget-pagination-direction="previous"[^>]*disabled/s', $result);
        self::assertStringContainsString('data-widget-page="2"', $result);
    }

    #[Test]
    public function requestedPageShowsOlderLogins(): void
    {
        $result = $this->render(['limit' => 2], ['page' => '2']);

        self::assertStringContainsString('editor_with_both_groups', $result);
        self::assertStringContainsString('editor_no_group', $result);
        self::assertStringNotContainsString('editor_with_one_group', $result);
        self::assertStringContainsString('Page 2 of 3', $result);
    }

    #[Test]
    public function lastPageDisablesNextButton(): void
    {
        $result = $this->render(['limit' => 2], ['page' => '3']);

        self::assertStringNotContainsString('editor_', $result);
        self::assertStringContainsString('admin', $result);
        self::assertMatchesRegularExpression('/data-widget-pagination-direction="next"[^>]*disabled/s', $result);
    }

    #[Test]
    public function loginsOfDeletedUsersAreNotCountedForPagination(): void
    {
        $this->addUserWithLogins(5, 'deleted_editor', ['deleted' => 1], [450, 350]);

        $result = $this->render(['limit' => 2]);

        self::assertStringNotContainsString('deleted_editor', $result);
        self::assertStringContainsString('editor_with_one_group', $result);
        self::assertStringContainsString('Page 1 of 3', $result);
    }

    #[Test]
    public function loginsOfDisabledUsersAreShown(): void
    {
        $this->addUserWithLogins(5, 'disabled_editor', ['disable' => 1], [450]);

        $result = $this->render(['limit' => 2]);

        self::assertStringContainsString('disabled_editor', $result);
        self::assertStringContainsString('Page 1 of 3', $result);
    }

    #[Test]
    public function paginationIsOmittedIfAllLoginsFitOnOnePage(): void
    {
        $result = $this->render(['limit' => 10]);

        self::assertStringContainsString('editor_no_group', $result);
        self::assertStringNotContainsString('widget-pagination', $result);
    }

    private function addUserWithLogins(int $userId, string $username, array $userFields, array $loginTimestamps): void
    {
        $connectionPool = $this->get(ConnectionPool::class);
        $connectionPool->getConnectionForTable('be_users')->insert('be_users', array_merge([
            'uid' => $userId,
            'pid' => 0,
            'username' => $username,
        ], $userFields));
        foreach ($loginTimestamps as $timestamp) {
            $connectionPool->getConnectionForTable('sys_log')->insert('sys_log', [
                'tstamp' => $timestamp,
                'userid' => $userId,
                'type' => SystemLogType::LOGIN,
                'action' => SystemLogLoginAction::LOGIN,
                'details' => 'User logged in',
            ]);
        }
    }

    private function render(array $settings, array $queryParams = []): string
    {
        $normalizedParams = self::createStub(NormalizedParams::class);
        $normalizedParams->method('getSitePath')->willReturn('/');
        $request = new ServerRequest('https://example.com/typo3/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', $normalizedParams)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-dashboard']))
            ->withQueryParams($queryParams);
        $configuration = new WidgetConfiguration('latestBeLogins', 'test', [], 'Test', 'Test', 'content-widget-list', 'small', 'small');
        $subject = new LatestBeLoginsWidget(
            $this->get(BackendViewFactory::class),
            $this->get(LatestBeLoginsDataProvider::class),
            new class implements ButtonProviderInterface {
                public function getTitle(): string
                {
                    return 'Button';
                }

                public function getLink(): string
                {
                    return '#';
                }

                public function getTarget(): string
                {
                    return '';
                }
            },
            $configuration,
        );
        return $subject->renderWidget(new WidgetContext('test', [], $configuration, new Settings($settings), $request))->content;
    }
}
