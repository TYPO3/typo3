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

namespace TYPO3\CMS\Opendocs\Tests\Unit\Domain\Repository;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Opendocs\Domain\Repository\OpenDocumentRepository;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
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
    public function findRecentDocumentsReturnsEmptyArrayWhenNoData(): void
    {
        $this->backendUser->method('getModuleData')->willReturn(null);

        $result = $this->subject->findForUser($this->backendUser);

        self::assertSame([], $result);
    }

    #[Test]
    public function findRecentDocumentsReturnsDocumentsFromNewFormat(): void
    {
        $this->backendUser->method('getModuleData')->willReturn([
            'pages:1' => [
                'table' => 'pages',
                'uid' => 1,
                'updatedAt' => '2024-01-01T12:00:00+00:00',
            ],
            'pages:2' => [
                'table' => 'pages',
                'uid' => 2,
                'updatedAt' => '2024-01-01T13:00:00+00:00',
            ],
        ]);

        $result = $this->subject->findForUser($this->backendUser);

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
                'uid' => $i,
                'updatedAt' => '2024-01-01T12:00:00+00:00',
            ];
        }

        $this->backendUser->method('getModuleData')->willReturn($recentDocs);

        $result = $this->subject->findForUser($this->backendUser);

        // Should limit to 8 (MAX_RECENT_DOCUMENTS)
        self::assertCount(8, $result);
    }

    #[Test]
    public function findRecentDocumentsSkipsNonArrayEntries(): void
    {
        $this->backendUser->method('getModuleData')->willReturn([
            'pages:123' => [
                'table' => 'pages',
                'uid' => 123,
                'updatedAt' => '2024-01-01T12:00:00+00:00',
            ],
            'invalid' => 'not an array',
        ]);

        $result = $this->subject->findForUser($this->backendUser);

        self::assertCount(1, $result);
        self::assertArrayHasKey('pages:123', $result);
    }

    #[Test]
    public function findRecentDocumentsMigratesFromLegacyFormat(): void
    {
        // Legacy format: numeric-indexed arrays stored under opendocs::recent
        $legacyData = [
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
            'pages:3181' => [
                'table' => 'pages',
                'uid' => 3181,
                'updatedAt' => '2026-02-25T05:02:26-05:00',
            ],
            ':0' => [
                'table' => '',
                'uid' => 0,
                'updatedAt' => '2026-02-25T04:53:25-05:00',
            ],
            'tt_content:1454' => [
                'table' => 'tt_content',
                'uid' => '1454',
                'title' => '',
                'parameters' => [
                    'edit' => [
                        'tt_content' => [
                            '1454' => 'edit',
                        ],
                    ],
                ],
                'pid' => 3240,
                'returnUrl' => '/',
            ],
            'pages:3221' => [
                'table' => 'pages',
                'uid' => '3221',
                'title' => 'topscorer',
                'parameters' => [
                    'edit' => [
                        'pages' => [
                            '3221' => 'edit',
                        ],
                    ],
                    'overrideVals' => [
                        'pages' => [
                            'sys_language_uid' => '0',
                        ],
                    ],
                ],
                'pid' => 1,
                'returnUrl' => '',
            ],
            'tx_styleguide_elements_basic:28' => [
                'table' => 'tx_styleguide_elements_basic',
                'uid' => '28',
                'title' => '28',
                'parameters' => [
                    'edit' => [
                        'tx_styleguide_elements_basic' => [
                            '28' => 'edit',
                        ],
                    ],
                ],
                'pid' => 3049,
                'returnUrl' => '/some/route/28',
            ],
            'tt_content:959' => [
                'table' => 'tt_content',
                'uid' => '959',
                'title' => 'Page Layouts',
                'parameters' => [
                    'edit' => [
                        'tt_content' => [
                            '959' => 'edit',
                        ],
                    ],
                ],
                'pid' => 2606,
                'returnUrl' => '',
            ],
            'pages:3228' => [
                'table' => 'pages',
                'uid' => '3228',
                'title' => 'KAI',
                'parameters' => [
                    'edit' => [
                        'pages' => [
                            '3228' => 'edit',
                        ],
                    ],
                ],
                'pid' => 0,
                'returnUrl' => '/some/route/3228',
            ],
        ];

        $this->backendUser->method('getModuleData')->willReturn($legacyData);

        /** @var array<string, mixed>|null $savedData */
        $savedData = null;
        $this->backendUser->expects($this->once())
            ->method('pushModuleData')
            ->with('opendocs::recent', self::isArray())
            ->willReturnCallback(function ($key, $data) use (&$savedData) {
                $savedData = $data;
            });

        $this->subject->findForUser($this->backendUser);

        // Verify migrated data was saved in new format
        self::assertNotNull($savedData);
        self::assertCount(8, $savedData);
        self::assertArrayHasKey('pages:123', $savedData);
        self::assertArrayHasKey('tt_content:456', $savedData);
        self::assertArrayHasKey('pages:3181', $savedData);
        self::assertArrayHasKey('tt_content:1454', $savedData);
        self::assertArrayHasKey('pages:3221', $savedData);
        self::assertArrayHasKey('tx_styleguide_elements_basic:28', $savedData);
        self::assertArrayHasKey('tt_content:959', $savedData);
        self::assertArrayHasKey('pages:3228', $savedData);
        // ':0' entry (empty table) must be discarded during migration
        self::assertArrayNotHasKey(':0', $savedData);
        // Every record must have an updatedAt after migration (entries without one get the current time)
        foreach ($savedData as $identifier => $record) {
            self::assertArrayHasKey('updatedAt', $record, sprintf('Record "%s" is missing updatedAt', $identifier));
        }
    }

    #[Test]
    public function addCreatesNewEntry(): void
    {
        $this->backendUser->method('getModuleData')->willReturn([]);

        /** @var array<string, mixed>|null $savedData */
        $savedData = null;
        $this->backendUser->expects($this->once())
            ->method('pushModuleData')
            ->with('opendocs::recent', self::isArray())
            ->willReturnCallback(function ($key, $data) use (&$savedData) {
                $savedData = $data;
            });

        $this->subject->add('pages', 123, $this->backendUser);

        self::assertNotNull($savedData);
        self::assertArrayHasKey('pages:123', $savedData);
        self::assertSame('pages', $savedData['pages:123']['table']);
        self::assertSame(123, $savedData['pages:123']['uid']);
        self::assertNotNull($savedData['pages:123']['updatedAt']);
    }

    #[Test]
    public function addUpdatesExistingEntry(): void
    {
        $this->backendUser->method('getModuleData')->willReturn([
            'pages:123' => [
                'table' => 'pages',
                'uid' => 123,
                'updatedAt' => '2024-01-01T12:00:00+00:00',
            ],
        ]);

        /** @var array<string, mixed>|null $savedData */
        $savedData = null;
        $this->backendUser->expects($this->once())
            ->method('pushModuleData')
            ->with('opendocs::recent', self::isArray())
            ->willReturnCallback(function ($key, $data) use (&$savedData) {
                $savedData = $data;
            });

        $this->subject->add('pages', 123, $this->backendUser);

        self::assertNotNull($savedData);
        self::assertCount(1, $savedData);
        self::assertArrayHasKey('pages:123', $savedData);
        self::assertNotSame('2024-01-01T12:00:00+00:00', $savedData['pages:123']['updatedAt']);
    }

    #[Test]
    public function addLimitsToMaximum(): void
    {
        // Create 8 existing recent documents (at the limit)
        $existingDocs = [];
        for ($i = 1; $i <= 8; $i++) {
            $existingDocs["pages:$i"] = [
                'table' => 'pages',
                'uid' => $i,
                'updatedAt' => '2024-01-01T' . str_pad((string)$i, 2, '0', STR_PAD_LEFT) . ':00:00+00:00',
            ];
        }

        $this->backendUser->method('getModuleData')->willReturn($existingDocs);

        /** @var array<string, mixed>|null $savedData */
        $savedData = null;
        $this->backendUser->expects($this->once())
            ->method('pushModuleData')
            ->willReturnCallback(function ($key, $data) use (&$savedData) {
                $savedData = $data;
            });

        $this->subject->add('pages', 999, $this->backendUser);

        self::assertNotNull($savedData);
        self::assertCount(8, $savedData);
        self::assertArrayHasKey('pages:999', $savedData);
        // Oldest entry should have been removed
        self::assertArrayNotHasKey('pages:1', $savedData);
    }

    #[Test]
    public function removeRemovesEntry(): void
    {
        $this->backendUser->method('getModuleData')->willReturn([
            'pages:123' => [
                'table' => 'pages',
                'uid' => 123,
                'updatedAt' => '2024-01-01T12:00:00+00:00',
            ],
            'pages:456' => [
                'table' => 'pages',
                'uid' => 456,
                'updatedAt' => '2024-01-01T13:00:00+00:00',
            ],
        ]);

        /** @var array<string, mixed>|null $savedData */
        $savedData = null;
        $this->backendUser->expects($this->once())
            ->method('pushModuleData')
            ->with('opendocs::recent', self::isArray())
            ->willReturnCallback(function ($key, $data) use (&$savedData) {
                $savedData = $data;
            });

        $this->subject->remove('pages:123', $this->backendUser);

        self::assertNotNull($savedData);
        self::assertArrayNotHasKey('pages:123', $savedData);
        self::assertArrayHasKey('pages:456', $savedData);
    }

    #[Test]
    public function removeHandlesNonExistentIdentifier(): void
    {
        $this->backendUser->method('getModuleData')->willReturn([
            'pages:123' => [
                'table' => 'pages',
                'uid' => 123,
                'updatedAt' => '2024-01-01T12:00:00+00:00',
            ],
        ]);

        /** @var array<string, mixed>|null $savedData */
        $savedData = null;
        $this->backendUser->expects($this->once())
            ->method('pushModuleData')
            ->with('opendocs::recent', self::isArray())
            ->willReturnCallback(function ($key, $data) use (&$savedData) {
                $savedData = $data;
            });

        $this->subject->remove('pages:999', $this->backendUser);

        self::assertNotNull($savedData);
        self::assertArrayHasKey('pages:123', $savedData);
    }
}
