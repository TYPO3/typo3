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

namespace TYPO3\CMS\Workspaces\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Workspaces\Controller\WorkspacesAjaxController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class WorkspacesAjaxControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_workspace.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
    }

    #[Test]
    public function previewPublishesTheCollectionOfAPage(): void
    {
        $this->setUpWorkspaceUser(1);

        $response = $this->get(WorkspacesAjaxController::class)->preview($this->createPublishRequest());

        $result = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(200, $response->getStatusCode(), (string)$response->getBody());
        self::assertSame(['success' => true], $result[0]['result'] ?? null);
        self::assertSame('Dummy WS 91', BackendUtility::getRecord('pages', 2)['title'] ?? null);
    }

    #[Test]
    public function previewDoesNotPublishForAMemberWithoutPublishAccess(): void
    {
        // A member of the workspace without access to live is no owner and may not publish
        $this->importCSVDataSet(__DIR__ . '/Fixtures/workspace_member.csv');
        $this->get(ConnectionPool::class)->getConnectionForTable('sys_workspace')->update('sys_workspace', ['members' => 'be_users_2'], ['uid' => 91]);
        $this->setUpWorkspaceUser(2);

        $this->get(WorkspacesAjaxController::class)->preview($this->createPublishRequest());

        self::assertSame('Dummy 1-2', BackendUtility::getRecord('pages', 2)['title'] ?? null);
        self::assertSame(91, (int)(BackendUtility::getRecord('pages', 102)['t3ver_wsid'] ?? 0));
    }

    #[Test]
    public function moduleRouteDoesNotPublishTheCollectionOfAPage(): void
    {
        $this->setUpWorkspaceUser(1);

        $response = $this->get(WorkspacesAjaxController::class)->dispatch($this->createPublishRequest());

        self::assertSame(500, $response->getStatusCode());
        self::assertSame('Dummy 1-2', BackendUtility::getRecord('pages', 2)['title'] ?? null);
    }

    private function setUpWorkspaceUser(int $uid): void
    {
        $backendUser = $this->setUpBackendUser($uid);
        $backendUser->setWorkspace(91);
        self::assertSame(91, $backendUser->workspace);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    private function createPublishRequest(): ServerRequest
    {
        $body = $this->get(StreamFactory::class)->createStream(json_encode([
            [
                'action' => 'RemoteServer',
                'method' => 'publishPageCollectionExecute',
                'data' => [['affects' => ['pages' => [['uid' => 102]]], 'stageId' => -20, 'comments' => '']],
                'type' => 'rpc',
                'tid' => 1,
            ],
        ], JSON_THROW_ON_ERROR));
        $body->rewind();
        return new ServerRequest('https://example.com/typo3/ajax/workspace/preview', 'POST')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withBody($body);
    }
}
