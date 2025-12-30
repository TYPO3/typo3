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

namespace TYPO3\CMS\Backend\Tests\Unit\Domain\Repository;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\Domain\Model\OpenDocument;
use TYPO3\CMS\Backend\Domain\Repository\OpenDocumentRepository;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class OpenDocumentRepositoryTest extends UnitTestCase
{
    private BackendUserAuthentication&MockObject $backendUser;
    private OpenDocumentRepository $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backendUser = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subject = new OpenDocumentRepository();
    }

    #[Test]
    public function findOpenDocumentsReturnsEmptyArrayWhenNoData(): void
    {
        $this->backendUser->method('getModuleData')->willReturn(null);

        $result = $this->subject->findOpenDocumentsForUser($this->backendUser);

        self::assertSame([], $result);
    }

    #[Test]
    public function findOpenDocumentsReturnsDocumentsFromNewFormat(): void
    {
        $this->backendUser->method('getModuleData')->willReturn([
            'pages:123' => [
                'table' => 'pages',
                'uid' => '123',
                'title' => 'Test Page',
                'parameters' => ['edit' => ['pages' => [123 => 'edit']]],
                'pid' => 1,
                'returnUrl' => '/typo3/list',
            ],
            'tt_content:456' => [
                'table' => 'tt_content',
                'uid' => '456',
                'title' => 'Test Content',
                'parameters' => ['edit' => ['tt_content' => [456 => 'edit']]],
                'pid' => 123,
                'returnUrl' => '',
            ],
        ]);

        $result = $this->subject->findOpenDocumentsForUser($this->backendUser);

        self::assertCount(2, $result);
        self::assertArrayHasKey('pages:123', $result);
        self::assertArrayHasKey('tt_content:456', $result);
        self::assertSame('pages', $result['pages:123']->table);
        self::assertSame('123', $result['pages:123']->uid);
        self::assertSame('Test Page', $result['pages:123']->title);
    }

    #[Test]
    public function findOpenDocumentsSkipsMalformedEntries(): void
    {
        $this->backendUser->method('getModuleData')->willReturn([
            'pages:123' => [
                'table' => 'pages',
                'uid' => '123',
                'title' => 'Valid Entry',
                'parameters' => [],
                'pid' => 1,
            ],
            'invalid' => 'not an array',
            'pages:456' => [
                'invalid' => 'structure',
            ],
        ]);

        $result = $this->subject->findOpenDocumentsForUser($this->backendUser);

        self::assertCount(1, $result);
        self::assertArrayHasKey('pages:123', $result);
    }

    #[Test]
    public function findOpenDocumentsMigratesFromLegacyFormat(): void
    {
        // First call returns legacy format, subsequent calls return null (already migrated)
        $this->backendUser->method('getModuleData')->willReturnCallback(
            function ($key, $type = '') {
                if ($key === 'opendocs::open') {
                    return null; // Not yet migrated
                }
                if ($key === 'FormEngine' && $type === 'ses') {
                    // Legacy format: [docHandler, currentDocHash]
                    return [
                        [
                            'hash1' => [
                                'Test Page',
                                ['edit' => ['pages' => [123 => 'edit']]],
                                'edit[pages][123]=edit',
                                ['table' => 'pages', 'uid' => '123', 'pid' => 1],
                                '/typo3/list',
                            ],
                            'hash2' => [
                                'Test Content',
                                ['edit' => ['tt_content' => [456 => 'edit']]],
                                'edit[tt_content][456]=edit',
                                ['table' => 'tt_content', 'uid' => '456', 'pid' => 123],
                                '',
                            ],
                        ],
                        'hash1',
                    ];
                }
                return null;
            }
        );

        // Expect migration to save in new format
        $savedData = null;
        $this->backendUser->expects($this->once())
            ->method('pushModuleData')
            ->with('opendocs::open', self::isArray())
            ->willReturnCallback(function ($key, $data) use (&$savedData) {
                $savedData = $data;
            });

        $this->subject->findOpenDocumentsForUser($this->backendUser);

        // Verify migrated data was saved in new format
        self::assertNotNull($savedData);
        self::assertArrayHasKey('pages:123', $savedData);
        self::assertArrayHasKey('tt_content:456', $savedData);
    }

    #[Test]
    public function findRecentDocumentsReturnsEmptyArrayWhenNoData(): void
    {
        $this->backendUser->method('getModuleData')->willReturn(null);

        $result = $this->subject->findRecentDocumentsForUser($this->backendUser);

        self::assertSame([], $result);
    }

    #[Test]
    public function findRecentDocumentsReturnsDocumentsFromNewFormat(): void
    {
        $this->backendUser->method('getModuleData')->willReturn([
            'pages:1' => [
                'table' => 'pages',
                'uid' => '1',
                'title' => 'Recent 1',
                'parameters' => [],
                'pid' => 0,
                'returnUrl' => '',
            ],
            'pages:2' => [
                'table' => 'pages',
                'uid' => '2',
                'title' => 'Recent 2',
                'parameters' => [],
                'pid' => 0,
                'returnUrl' => '',
            ],
        ]);

        $result = $this->subject->findRecentDocumentsForUser($this->backendUser);

        self::assertCount(2, $result);
        self::assertArrayHasKey('pages:1', $result);
        self::assertArrayHasKey('pages:2', $result);
    }

    #[Test]
    public function findRecentDocumentsLimitsToMaximum(): void
    {
        // Create 10 recent documents
        $recentDocs = [];
        for ($i = 1; $i <= 10; $i++) {
            $recentDocs["pages:$i"] = [
                'table' => 'pages',
                'uid' => (string)$i,
                'title' => "Recent $i",
                'parameters' => [],
                'pid' => 0,
                'returnUrl' => '',
            ];
        }

        $this->backendUser->method('getModuleData')->willReturn($recentDocs);

        $result = $this->subject->findRecentDocumentsForUser($this->backendUser);

        // Should limit to 8 (MAX_RECENT_DOCUMENTS)
        self::assertCount(8, $result);
    }

    #[Test]
    public function addOrUpdateOpenDocumentCreatesNewEntry(): void
    {
        $this->backendUser->method('getModuleData')->willReturn([]);

        $document = new OpenDocument(
            table: 'pages',
            uid: '123',
            title: 'Test Page',
            parameters: ['edit' => ['pages' => [123 => 'edit']]],
            pid: 1,
            returnUrl: '/typo3/list'
        );

        $savedData = null;
        $this->backendUser->expects($this->once())
            ->method('pushModuleData')
            ->with('opendocs::open', self::isType('array'))
            ->willReturnCallback(function ($key, $data) use (&$savedData) {
                $savedData = $data;
            });

        $this->subject->addOrUpdateOpenDocument($document, $this->backendUser);

        self::assertArrayHasKey('pages:123', $savedData);
        self::assertSame('Test Page', $savedData['pages:123']['title']);
    }

    #[Test]
    public function addOrUpdateOpenDocumentUpdatesExistingEntry(): void
    {
        $existingData = [
            'pages:123' => [
                'table' => 'pages',
                'uid' => '123',
                'title' => 'Old Title',
                'parameters' => [],
                'pid' => 1,
                'returnUrl' => '',
            ],
        ];

        $this->backendUser->method('getModuleData')->willReturn($existingData);

        $document = new OpenDocument(
            table: 'pages',
            uid: '123',
            title: 'Updated Title',
            parameters: ['edit' => ['pages' => [123 => 'edit']]],
            pid: 1,
            returnUrl: '/typo3/list'
        );

        $savedData = null;
        $this->backendUser->expects($this->once())
            ->method('pushModuleData')
            ->with('opendocs::open', self::isType('array'))
            ->willReturnCallback(function ($key, $data) use (&$savedData) {
                $savedData = $data;
            });

        $this->subject->addOrUpdateOpenDocument($document, $this->backendUser);

        self::assertArrayHasKey('pages:123', $savedData);
        self::assertSame('Updated Title', $savedData['pages:123']['title']);
    }

    #[Test]
    public function closeDocumentMovesToRecentDocuments(): void
    {
        $openDocs = [
            'pages:123' => [
                'table' => 'pages',
                'uid' => '123',
                'title' => 'Test Page',
                'parameters' => [],
                'pid' => 1,
                'returnUrl' => '',
            ],
            'pages:456' => [
                'table' => 'pages',
                'uid' => '456',
                'title' => 'Another Page',
                'parameters' => [],
                'pid' => 1,
                'returnUrl' => '',
            ],
        ];

        $recentDocs = [
            'pages:789' => [
                'table' => 'pages',
                'uid' => '789',
                'title' => 'Old Recent',
                'parameters' => [],
                'pid' => 1,
                'returnUrl' => '',
            ],
        ];

        $this->backendUser->method('getModuleData')
            ->willReturnCallback(function ($key) use ($openDocs, $recentDocs) {
                return match ($key) {
                    'opendocs::open' => $openDocs,
                    'opendocs::recent' => $recentDocs,
                    default => null,
                };
            });

        $savedData = [];
        $this->backendUser->expects($this->exactly(2))
            ->method('pushModuleData')
            ->willReturnCallback(function ($key, $data) use (&$savedData) {
                $savedData[$key] = $data;
            });

        $this->subject->closeDocument('pages', '123', $this->backendUser);

        // Verify document was removed from open
        self::assertArrayNotHasKey('pages:123', $savedData['opendocs::open']);
        self::assertArrayHasKey('pages:456', $savedData['opendocs::open']);

        // Verify document was added to recent (at the beginning)
        self::assertArrayHasKey('pages:123', $savedData['opendocs::recent']);
        $keys = array_keys($savedData['opendocs::recent']);
        self::assertSame('pages:123', $keys[0]); // First element
    }

    #[Test]
    public function closeDocumentLimitsRecentDocuments(): void
    {
        $openDocs = [
            'pages:new' => [
                'table' => 'pages',
                'uid' => 'new',
                'title' => 'New Page',
                'parameters' => [],
                'pid' => 1,
                'returnUrl' => '',
            ],
        ];

        // Create 8 existing recent documents (at the limit)
        $recentDocs = [];
        for ($i = 1; $i <= 8; $i++) {
            $recentDocs["pages:$i"] = [
                'table' => 'pages',
                'uid' => (string)$i,
                'title' => "Recent $i",
                'parameters' => [],
                'pid' => 1,
                'returnUrl' => '',
            ];
        }

        $this->backendUser->method('getModuleData')
            ->willReturnCallback(function ($key) use ($openDocs, $recentDocs) {
                return match ($key) {
                    'opendocs::open' => $openDocs,
                    'opendocs::recent' => $recentDocs,
                    default => null,
                };
            });

        $savedData = [];
        $this->backendUser->expects($this->exactly(2))
            ->method('pushModuleData')
            ->willReturnCallback(function ($key, $data) use (&$savedData) {
                $savedData[$key] = $data;
            });

        $this->subject->closeDocument('pages', 'new', $this->backendUser);

        // Should still be limited to 8
        self::assertCount(8, $savedData['opendocs::recent']);
        // New document should be first
        $keys = array_keys($savedData['opendocs::recent']);
        self::assertSame('pages:new', $keys[0]);
        // Last document should be removed
        self::assertArrayNotHasKey('pages:8', $savedData['opendocs::recent']);
    }

    #[Test]
    public function closeAllDocumentsMovesAllToRecent(): void
    {
        $openDocs = [
            'pages:1' => [
                'table' => 'pages',
                'uid' => '1',
                'title' => 'Page 1',
                'parameters' => [],
                'pid' => 0,
                'returnUrl' => '',
            ],
            'pages:2' => [
                'table' => 'pages',
                'uid' => '2',
                'title' => 'Page 2',
                'parameters' => [],
                'pid' => 0,
                'returnUrl' => '',
            ],
        ];

        $recentDocs = [
            'pages:3' => [
                'table' => 'pages',
                'uid' => '3',
                'title' => 'Page 3',
                'parameters' => [],
                'pid' => 0,
                'returnUrl' => '',
            ],
        ];

        $this->backendUser->method('getModuleData')
            ->willReturnCallback(function ($key) use ($openDocs, $recentDocs) {
                return match ($key) {
                    'opendocs::open' => $openDocs,
                    'opendocs::recent' => $recentDocs,
                    default => null,
                };
            });

        $savedData = [];
        $this->backendUser->expects($this->exactly(2))
            ->method('pushModuleData')
            ->willReturnCallback(function ($key, $data) use (&$savedData) {
                $savedData[$key] = $data;
            });

        $this->subject->closeAllDocuments($this->backendUser);

        // Open documents should be empty
        self::assertSame([], $savedData['opendocs::open']);

        // All documents should be in recent
        self::assertCount(3, $savedData['opendocs::recent']);
        self::assertArrayHasKey('pages:1', $savedData['opendocs::recent']);
        self::assertArrayHasKey('pages:2', $savedData['opendocs::recent']);
        self::assertArrayHasKey('pages:3', $savedData['opendocs::recent']);
    }

    #[Test]
    public function closeAllDocumentsLimitsRecentToMaximum(): void
    {
        // Create 10 open documents
        $openDocs = [];
        for ($i = 1; $i <= 10; $i++) {
            $openDocs["pages:$i"] = [
                'table' => 'pages',
                'uid' => (string)$i,
                'title' => "Page $i",
                'parameters' => [],
                'pid' => 0,
                'returnUrl' => '',
            ];
        }

        $this->backendUser->method('getModuleData')
            ->willReturnCallback(function ($key) use ($openDocs) {
                return match ($key) {
                    'opendocs::open' => $openDocs,
                    'opendocs::recent' => [],
                    default => null,
                };
            });

        $savedData = [];
        $this->backendUser->expects($this->exactly(2))
            ->method('pushModuleData')
            ->willReturnCallback(function ($key, $data) use (&$savedData) {
                $savedData[$key] = $data;
            });

        $this->subject->closeAllDocuments($this->backendUser);

        // Recent should be limited to 8
        self::assertCount(8, $savedData['opendocs::recent']);
    }
}
