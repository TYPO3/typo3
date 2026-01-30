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
use TYPO3\CMS\Backend\Backend\Bookmark\BookmarkService;
use TYPO3\CMS\Backend\Controller\BookmarkController;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BookmarkControllerTest extends FunctionalTestCase
{
    private BookmarkController $subject;
    private ServerRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/BookmarkControllerTest.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->subject = new BookmarkController(
            $this->get(BookmarkService::class),
        );
        $this->request = (new ServerRequest('https://example.com/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', new NormalizedParams([], [], '', ''));
        $GLOBALS['TYPO3_REQUEST'] = $this->request;
    }

    private function setUpUser(int $userId): void
    {
        $backendUser = $this->setUpBackendUser($userId);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    // ========================================
    // listAction Tests
    // ========================================

    #[Test]
    public function listActionReturnsBookmarksAndGroups(): void
    {
        $response = $this->subject->listAction($this->request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertTrue($body['success']);
        self::assertArrayHasKey('bookmarks', $body);
        self::assertArrayHasKey('groups', $body);
        self::assertIsArray($body['bookmarks']);
        self::assertIsArray($body['groups']);
        self::assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function listActionReturnsOnlyAccessibleBookmarksForRegularUser(): void
    {
        $this->setUpUser(2);

        $response = $this->subject->listAction($this->request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertTrue($body['success']);
        // User 2 should only see their own bookmarks
        foreach ($body['bookmarks'] as $bookmark) {
            // User 2 owns bookmark with id 3
            self::assertContains($bookmark['id'], [3, 5], 'User should only see own bookmarks or accessible global ones');
        }
    }

    // ========================================
    // createAction Tests
    // ========================================

    #[DataProvider('createBookmarkTestDataProvider')]
    #[Test]
    public function createBookmarkTest(array $parsedBody, bool $expectedSuccess, int $expectedResponseStatus): void
    {
        $request = $this->request->withParsedBody($parsedBody);
        $response = $this->subject->createAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals($expectedSuccess, $body['success']);
        self::assertEquals($expectedResponseStatus, $response->getStatusCode());

        if ($expectedSuccess) {
            self::assertArrayHasKey('bookmark', $body);
        } else {
            self::assertArrayHasKey('error', $body);
        }
    }

    public static function createBookmarkTestDataProvider(): \Generator
    {
        yield 'Existing data as parsed body' => [
            [
                'routeIdentifier' => 'web_layout',
                'arguments' => '{"id":"123"}',
            ],
            false,
            200,
        ];
        yield 'New data as parsed body' => [
            [
                'routeIdentifier' => 'records',
                'arguments' => '{"id":"456","GET":{"clipBoard":"1"}}',
            ],
            true,
            201,
        ];
    }

    #[Test]
    public function createBookmarkWorksForRegularUser(): void
    {
        $this->setUpUser(2);

        $request = $this->request->withParsedBody([
            'routeIdentifier' => 'records',
            'arguments' => '{"id":"789"}',
        ]);
        $response = $this->subject->createAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertTrue($body['success']);
        self::assertEquals(201, $response->getStatusCode());
    }

    // ========================================
    // updateAction Tests
    // ========================================

    #[Test]
    public function updateActionUpdatesOwnBookmark(): void
    {
        $request = $this->request->withParsedBody([
            'bookmarkId' => 1,
            'bookmarkTitle' => 'Updated Title',
            'bookmarkGroup' => 0,
        ]);
        $response = $this->subject->updateAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertTrue($body['success']);
        self::assertArrayHasKey('bookmark', $body);
        self::assertEquals('Updated Title', $body['bookmark']['title']);
    }

    #[Test]
    public function updateActionDeniesAccessToOtherUsersBookmark(): void
    {
        $this->setUpUser(2);

        // Bookmark 1 belongs to user 1
        $request = $this->request->withParsedBody([
            'bookmarkId' => 1,
            'bookmarkTitle' => 'Hacked Title',
            'bookmarkGroup' => 0,
        ]);
        $response = $this->subject->updateAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertFalse($body['success']);
    }

    #[Test]
    public function updateActionAllowsAdminToUpdateGlobalBookmark(): void
    {
        // User 1 is admin - bookmark 5 is a global bookmark (sc_group = -100)
        $request = $this->request->withParsedBody([
            'bookmarkId' => 5,
            'bookmarkTitle' => 'Updated Global',
            'bookmarkGroup' => -100,
        ]);
        $response = $this->subject->updateAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertTrue($body['success']);
    }

    #[Test]
    public function updateActionDeniesRegularUserToMoveToGlobalGroup(): void
    {
        $this->setUpUser(2);

        // User 2 tries to move their bookmark to a global group
        $request = $this->request->withParsedBody([
            'bookmarkId' => 3,
            'bookmarkTitle' => 'My Bookmark',
            'bookmarkGroup' => -100,
        ]);
        $response = $this->subject->updateAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertFalse($body['success']);
    }

    // ========================================
    // deleteAction Tests
    // ========================================

    #[Test]
    public function deleteActionDeletesOwnBookmark(): void
    {
        $request = $this->request->withParsedBody([
            'bookmarkId' => 1,
        ]);
        $response = $this->subject->deleteAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertTrue($body['success']);
    }

    #[Test]
    public function deleteActionDeniesAccessToOtherUsersBookmark(): void
    {
        $this->setUpUser(2);

        // Bookmark 1 belongs to user 1
        $request = $this->request->withParsedBody([
            'bookmarkId' => 1,
        ]);
        $response = $this->subject->deleteAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertFalse($body['success']);
    }

    #[Test]
    public function deleteActionAllowsAdminToDeleteGlobalBookmark(): void
    {
        // Bookmark 5 is a global bookmark
        $request = $this->request->withParsedBody([
            'bookmarkId' => 5,
        ]);
        $response = $this->subject->deleteAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertTrue($body['success']);
    }

    // ========================================
    // reorderAction Tests
    // ========================================

    #[Test]
    public function reorderActionReordersOwnBookmarks(): void
    {
        $request = $this->request->withParsedBody([
            'bookmarkIds' => [2, 1],
        ]);
        $response = $this->subject->reorderAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertTrue($body['success']);
    }

    #[Test]
    public function reorderActionIgnoresOtherUsersBookmarks(): void
    {
        $this->setUpUser(2);

        // Try to reorder bookmarks including one from user 1
        $request = $this->request->withParsedBody([
            'bookmarkIds' => [1, 3],
        ]);
        $response = $this->subject->reorderAction($request);
        $body = json_decode((string)$response->getBody(), true);

        // Returns success but only reorders accessible bookmarks
        self::assertTrue($body['success']);
    }

    // ========================================
    // deleteMultipleAction Tests
    // ========================================

    #[Test]
    public function deleteMultipleActionDeletesOwnBookmarks(): void
    {
        $request = $this->request->withParsedBody([
            'bookmarkIds' => [1, 2],
        ]);
        $response = $this->subject->deleteMultipleAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertTrue($body['success']);
    }

    #[Test]
    public function deleteMultipleActionOnlyDeletesAccessibleBookmarks(): void
    {
        $this->setUpUser(2);

        // Try to delete bookmarks including one from user 1
        $request = $this->request->withParsedBody([
            'bookmarkIds' => [1, 3],
        ]);
        $response = $this->subject->deleteMultipleAction($request);
        $body = json_decode((string)$response->getBody(), true);

        // Returns success but only deletes user's own bookmarks
        self::assertTrue($body['success']);
    }

    // ========================================
    // moveAction Tests
    // ========================================

    #[Test]
    public function moveActionMovesOwnBookmarks(): void
    {
        $request = $this->request->withParsedBody([
            'bookmarkIds' => [1],
            'groupId' => 0,
        ]);
        $response = $this->subject->moveAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertTrue($body['success']);
    }

    #[Test]
    public function moveActionDeniesMovingOtherUsersBookmarks(): void
    {
        $this->setUpUser(2);

        // Try to move bookmark 1 which belongs to user 1
        $request = $this->request->withParsedBody([
            'bookmarkIds' => [1],
            'groupId' => 0,
        ]);
        $response = $this->subject->moveAction($request);
        $body = json_decode((string)$response->getBody(), true);

        // Returns false because no bookmarks could be moved
        self::assertFalse($body['success']);
    }

    // ========================================
    // createGroupAction Tests
    // ========================================

    #[Test]
    public function createGroupActionCreatesGroup(): void
    {
        $request = $this->request->withParsedBody([
            'label' => 'My New Group',
        ]);
        $response = $this->subject->createGroupAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertTrue($body['success']);
        self::assertArrayHasKey('group', $body);
        self::assertEquals('My New Group', $body['group']['label']);
        self::assertEquals(201, $response->getStatusCode());
    }

    #[Test]
    public function createGroupActionWorksForRegularUser(): void
    {
        $this->setUpUser(2);

        $request = $this->request->withParsedBody([
            'label' => 'User 2 Group',
        ]);
        $response = $this->subject->createGroupAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertTrue($body['success']);
        self::assertEquals(201, $response->getStatusCode());
    }

    // ========================================
    // updateGroupAction Tests
    // ========================================

    #[Test]
    public function updateGroupActionUpdatesOwnGroup(): void
    {
        $request = $this->request->withParsedBody([
            'uuid' => 'ef75846e-59e0-40d0-95ac-33cdc9da8c2b',
            'label' => 'Updated Group Name',
        ]);
        $response = $this->subject->updateGroupAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertTrue($body['success']);
        self::assertArrayHasKey('groups', $body);
    }

    #[Test]
    public function updateGroupActionDeniesAccessToOtherUsersGroup(): void
    {
        $this->setUpUser(2);

        // ef75846e-59e0-40d0-95ac-33cdc9da8c2b belongs to user 1
        $request = $this->request->withParsedBody([
            'uuid' => 'ef75846e-59e0-40d0-95ac-33cdc9da8c2b',
            'label' => 'Hacked Group',
        ]);
        $response = $this->subject->updateGroupAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertFalse($body['success']);
    }

    // ========================================
    // deleteGroupAction Tests
    // ========================================

    #[Test]
    public function deleteGroupActionDeletesOwnGroup(): void
    {
        $request = $this->request->withParsedBody([
            'uuid' => 'ef75846e-59e0-40d0-95ac-33cdc9da8c2b',
        ]);
        $response = $this->subject->deleteGroupAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertTrue($body['success']);
        self::assertArrayHasKey('groups', $body);
    }

    #[Test]
    public function deleteGroupActionDeniesAccessToOtherUsersGroup(): void
    {
        $this->setUpUser(2);

        // ef75846e-59e0-40d0-95ac-33cdc9da8c2b belongs to user 1
        $request = $this->request->withParsedBody([
            'uuid' => 'ef75846e-59e0-40d0-95ac-33cdc9da8c2b',
        ]);
        $response = $this->subject->deleteGroupAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertFalse($body['success']);
    }

    // ========================================
    // reorderGroupsAction Tests
    // ========================================

    #[Test]
    public function reorderGroupsActionReordersOwnGroups(): void
    {
        $request = $this->request->withParsedBody([
            'uuids' => ['ef75846e-59e0-40d0-95ac-33cdc9da8c2b'],
        ]);
        $response = $this->subject->reorderGroupsAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertTrue($body['success']);
        self::assertArrayHasKey('groups', $body);
    }

    #[Test]
    public function reorderGroupsActionDeniesReorderingOtherUsersGroups(): void
    {
        $this->setUpUser(2);

        // ef75846e-59e0-40d0-95ac-33cdc9da8c2b belongs to user 1
        $request = $this->request->withParsedBody([
            'uuids' => ['ef75846e-59e0-40d0-95ac-33cdc9da8c2b'],
        ]);
        $response = $this->subject->reorderGroupsAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertFalse($body['success']);
    }

    #[Test]
    public function reorderGroupsActionWorksForRegularUserWithOwnGroups(): void
    {
        $this->setUpUser(2);

        // d663f18b-3330-4939-b9af-8bb117ce2dc4 belongs to user 2
        $request = $this->request->withParsedBody([
            'uuids' => ['d663f18b-3330-4939-b9af-8bb117ce2dc4'],
        ]);
        $response = $this->subject->reorderGroupsAction($request);
        $body = json_decode((string)$response->getBody(), true);

        self::assertTrue($body['success']);
    }
}
