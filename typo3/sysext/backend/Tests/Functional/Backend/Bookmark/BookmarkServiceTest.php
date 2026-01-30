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

namespace TYPO3\CMS\Backend\Tests\Functional\Backend\Bookmark;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Backend\Bookmark\BookmarkService;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Routing\RequestContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BookmarkServiceTest extends FunctionalTestCase
{
    private BookmarkService $subject;

    protected array $coreExtensionsToLoad = ['filelist'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ShortcutsBase.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $normalizedParams = $this->createMock(NormalizedParams::class);
        $normalizedParams->method('getSitePath')
            ->willReturn('/');
        $request = (new ServerRequest('https://localhost/typo3/'))
            ->withAttribute('normalizedParams', $normalizedParams);
        $requestContextFactory = $this->get(RequestContextFactory::class);
        $uriBuilder = $this->get(UriBuilder::class);
        $uriBuilder->setRequestContext($requestContextFactory->fromBackendRequest($request));
        $this->subject = $this->get(BookmarkService::class);
    }

    #[DataProvider('bookmarkExistsTestDataProvider')]
    #[Test]
    public function bookmarkExistsTest(string $routeIdentifier, array $arguments, int $userid, bool $exists): void
    {
        $GLOBALS['BE_USER']->user['uid'] = $userid;
        self::assertEquals($exists, $this->subject->hasBookmark($routeIdentifier, json_encode($arguments)));
    }

    public static function bookmarkExistsTestDataProvider(): \Generator
    {
        yield 'Bookmark exists' => [
            'records',
            ['id' => 123, 'GET' => ['clipBoard' => 1]],
            1,
            true,
        ];
        yield 'Not this user' => [
            'records',
            ['id' => 123, 'GET' => ['clipBoard' => 1]],
            2,
            false,
        ];
        yield 'Wrong route identifer' => [
            'web_layout',
            ['id' => 123, 'GET' => ['clipBoard' => 1]],
            1,
            false,
        ];
        yield 'Wrong arguments' => [
            'records',
            ['id' => 321, 'GET' => ['clipBoard' => 1]],
            1,
            false,
        ];
    }

    #[Test]
    public function addBookmarkTest(): void
    {
        foreach ($this->getBookmarksToAdd() as $bookmark) {
            $this->subject->createBookmark(
                $bookmark['routeIdentifier'],
                json_encode($bookmark['arguments']),
                $bookmark['title']
            );
        }

        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/BookmarksAddedResult.csv');
    }

    public function getBookmarksToAdd(): array
    {
        return [
            'Basic bookmark with all information' => [
                'routeIdentifier' => 'records',
                'arguments' => ['id' => 111, 'GET' => ['clipBoard' => 1]],
                'title' => 'Recordlist of id 111',
            ],
            'Bookmark with empty title' => [
                'routeIdentifier' => 'record_edit',
                'arguments' => ['edit' => ['pages' => [112 => 'edit']]],
                'title' => '',
            ],
            'Bookmark with invalid route' => [
                'routeIdentifier' => 'invalid_route',
                'arguments' => ['edit' => ['pages' => [112 => 'edit']]],
                'title' => 'Some title',
            ],
        ];
    }

    /**
     * This effectively also tests BookmarkService::initBookmarks()
     */
    #[Test]
    public function getBookmarksByGroupTest(): void
    {
        $expected = [
            1 => [
                'table' => null,
                'recordid' => null,
                'groupLabel' => 'Pages',
                'type' => 'other',
                'iconIdentifier' => 'module-list',
                'label' => 'Recordlist',
                'href' => '/typo3/module/content/records?token=%s&id=123&GET%5BclipBoard%5D=1',
            ],
            2 => [
                'table' => 'tt_content',
                'recordid' => '113',
                'groupLabel' => null,
                'type' => 'edit',
                'label' => 'Edit Content',
                'iconIdentifier' => 'mimetypes-x-content-text',
                'href' => '/typo3/record/edit?token=%s&edit%5Btt_content%5D%5B113%5D=edit',
            ],
            3 => [
                'table' => 'tt_content',
                'recordid' => '117',
                'groupLabel' => null,
                'type' => 'new',
                'label' => 'Create Content',
                'iconIdentifier' => 'mimetypes-x-content-text',
                'href' => '/typo3/record/edit?token=%s&edit%5Btt_content%5D%5B117%5D=new',
            ],
            7 => [
                'table' => null,
                'recordid' => null,
                'groupLabel' => null,
                'type' => 'other',
                'label' => 'Bookmark', // This is a fallback to not display bookmarks without title
                'iconIdentifier' => 'module-page',
                'href' => '/typo3/module/web/layout?token=%s&id=123',
            ],
        ];

        // Filter bookmarks by group 1
        $bookmarks = array_filter(
            $this->subject->getBookmarks(),
            static fn($bookmark) => $bookmark->groupId === 1
        );

        self::assertCount(count($expected), $bookmarks);

        foreach ($bookmarks as $bookmark) {
            $id = $bookmark->id;
            self::assertEquals(1, $bookmark->groupId);
            self::assertEquals($expected[$id]['label'], $bookmark->title);
            self::assertEquals($expected[$id]['iconIdentifier'], $bookmark->iconIdentifier);
            self::assertStringMatchesFormat($expected[$id]['href'], $bookmark->href);
        }
    }

    public static function invalidBookmarkArgumentsAreIgnoredDataProvider(): \Generator
    {
        yield 'record_edit invalid JSON' => [
            'record_edit',
            '$INVALID/JSON$',
        ];
        yield 'record_edit invalid edit data' => [
            'record_edit',
            json_encode(['edit' => [9, 8, 7]]),
        ];
        yield 'record_edit incomplete edit data' => [
            'record_edit',
            json_encode(['edit' => ['invalid' => ['987' => 'edit']]]),
        ];
        yield 'media_management invalid path' => [
            'media_management',
            json_encode(['id' => '1:any/../../thing']),
        ];
        yield 'media_management non-existing path' => [
            'media_management',
            json_encode(['id' => '1:any/thing']),
        ];
    }

    #[Test]
    #[DataProvider('invalidBookmarkArgumentsAreIgnoredDataProvider')]
    public function invalidBookmarkArgumentsAreIgnored($routIdentifier, string $arguments): void
    {
        $this->expectNotToPerformAssertions();
        $this->subject->createBookmark($routIdentifier, $arguments, 'Test');
    }
}
