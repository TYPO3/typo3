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

namespace TYPO3\CMS\Core\Tests\Functional\Utility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\Page\PageNotFoundException;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RootlineUtilityTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            'main',
            $this->buildSiteConfiguration(1, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', '/fr/', ['EN']),
            ]
        );
        self::importCSVDataSet(__DIR__ . '/Fixtures/RootlineUtilityImport.csv');
    }

    private function filterExpectedValues(array $incomingData, array $fields): array
    {
        $result = [];
        foreach ($incomingData as $pos => $values) {
            $filteredValues = [];
            foreach ($fields as $field) {
                if (isset($values[$field])) {
                    $filteredValues[$field] = $values[$field];
                }
            }
            $result[$pos] = $filteredValues;
        }
        return $result;
    }

    #[Test]
    public function verifyCleanReferenceIndex()
    {
        // Fix refindex, then compare with import csv again to verify nothing changed.
        // This is to make sure the import csv is 'clean' - important for the other tests.
        $this->get(ReferenceIndex::class)->updateIndex(false);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RootlineUtilityImport.csv');
    }

    #[Test]
    public function isMountedPageWithoutMountPointsReturnsFalse(): void
    {
        $subject = new RootlineUtility(1, '', new Context());
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'isMountedPage'));
        self::assertFalse($subjectMethodReflection->invoke($subject));
    }

    #[Test]
    public function isMountedPageWithMatchingMountPointParameterReturnsTrue(): void
    {
        $subject = new RootlineUtility(1, '1-99', new Context());
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'isMountedPage'));
        self::assertTrue($subjectMethodReflection->invoke($subject));
    }

    #[Test]
    public function isMountedPageWithNonMatchingMountPointParameterReturnsFalse(): void
    {
        $subject = new RootlineUtility(1, '99-99', new Context());
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'isMountedPage'));
        self::assertFalse($subjectMethodReflection->invoke($subject));
    }

    #[Test]
    public function processMountedPageWithNonMountedPageThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1343464100);
        $subject = new RootlineUtility(1, '1-99', new Context());
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'processMountedPage'));
        $subjectMethodReflection->invoke($subject, ['uid' => 1], ['uid' => 99, 'doktype' => PageRepository::DOKTYPE_DEFAULT]);
    }

    #[Test]
    public function processMountedPageWithMountedPageNotThrowsException(): void
    {
        $subject = new RootlineUtility(1, '1-99', new Context());
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'processMountedPage'));
        $result = $subjectMethodReflection->invoke(
            $subject,
            ['uid' => 1],
            ['uid' => 99, 'doktype' => PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1]
        );
        self::assertNotEmpty($result);
    }

    #[Test]
    public function processMountedPageWithMountedPageAddsMountedFromParameter(): void
    {
        $subject = new RootlineUtility(1, '1-99', new Context());
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'processMountedPage'));
        $result = $subjectMethodReflection->invoke(
            $subject,
            ['uid' => 1],
            ['uid' => 99, 'doktype' => PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1]
        );
        self::assertTrue(isset($result['_MOUNTED_FROM']));
        self::assertSame(1, $result['_MOUNTED_FROM']);
    }

    #[Test]
    public function processMountedPageWithMountedPageAddsMountPointParameterToReturnValue(): void
    {
        $subject = new RootlineUtility(1, '1-99', new Context());
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'processMountedPage'));
        $result = $subjectMethodReflection->invoke(
            $subject,
            ['uid' => 1],
            ['uid' => 99, 'doktype' => PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1]
        );
        self::assertTrue(isset($result['_MP_PARAM']));
        self::assertSame('1-99', $result['_MP_PARAM']);
    }

    #[Test]
    public function processMountedPageForMountPageIsOverlayAddsMountOLParameter(): void
    {
        $subject = new RootlineUtility(1, '1-99', new Context());
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'processMountedPage'));
        $result = $subjectMethodReflection->invoke(
            $subject,
            ['uid' => 1],
            ['uid' => 99, 'doktype' => PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1, 'mount_pid_ol' => 1]
        );
        self::assertTrue(isset($result['_MOUNT_OL']));
        self::assertTrue($result['_MOUNT_OL']);
    }

    #[Test]
    public function processMountedPageForMountPageIsOverlayAddsDataInformationAboutMountPage(): void
    {
        $subject = new RootlineUtility(1, '1-99', new Context());
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'processMountedPage'));
        $result = $subjectMethodReflection->invoke(
            $subject,
            ['uid' => 1],
            [
                'uid' => 99,
                'doktype' => PageRepository::DOKTYPE_MOUNTPOINT,
                'mount_pid' => 1,
                'mount_pid_ol' => 1,
                'pid' => 5,
                'title' => 'TestCase',
            ]
        );
        self::assertTrue(isset($result['_MOUNT_PAGE']));
        self::assertSame(['uid' => 99, 'pid' => 5, 'title' => 'TestCase'], $result['_MOUNT_PAGE']);
    }

    #[Test]
    public function processMountedPageForMountPageWithoutOverlayReplacesMountedPageWithMountPage(): void
    {
        $subject = new RootlineUtility(1, '1-99', new Context());
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'processMountedPage'));
        $mountPointPageData = [
            'uid' => 99,
            'doktype' => PageRepository::DOKTYPE_MOUNTPOINT,
            'mount_pid' => 1,
            'mount_pid_ol' => 0,
        ];
        $result = $subjectMethodReflection->invoke(
            $subject,
            ['uid' => 1],
            $mountPointPageData
        );
        // Tests that $mountPointPageData is completely part of $result and keys match.
        self::assertSame($mountPointPageData, array_intersect_assoc($mountPointPageData, $result));
    }

    #[Test]
    public function columnHasRelationToResolveDetectsGroupFieldAsLocal(): void
    {
        $subject = new RootlineUtility(1, '1-99', new Context());
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'columnHasRelationToResolve'));
        self::assertFalse($subjectMethodReflection->invoke(
            $subject,
            [
                'config' => [
                    'type' => 'group',
                ],
            ]
        ));
    }

    #[Test]
    public function columnHasRelationToResolveDetectsGroupFieldWithMMAsRemote(): void
    {
        $subject = new RootlineUtility(1, '1-99', new Context());
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'columnHasRelationToResolve'));
        self::assertTrue($subjectMethodReflection->invoke(
            $subject,
            [
                'config' => [
                    'type' => 'group',
                    'MM' => 'tx_xyz',
                ],
            ]
        ));
    }

    #[Test]
    public function columnHasRelationToResolveDetectsInlineFieldAsLocal(): void
    {
        $subject = new RootlineUtility(1, '1-99', new Context());
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'columnHasRelationToResolve'));
        self::assertFalse($subjectMethodReflection->invoke(
            $subject,
            [
                'config' => [
                    'type' => 'inline',
                ],
            ]
        ));
    }

    #[Test]
    public function columnHasRelationToResolveDetectsInlineFieldWithForeignKeyAsRemote(): void
    {
        $subject = new RootlineUtility(1, '1-99', new Context());
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'columnHasRelationToResolve'));
        self::assertTrue($subjectMethodReflection->invoke(
            $subject,
            [
                'config' => [
                    'type' => 'inline',
                    'foreign_field' => 'xyz',
                ],
            ]
        ));
    }

    #[Test]
    public function columnHasRelationToResolveDetectsInlineFieldWithFMMAsRemote(): void
    {
        $subject = new RootlineUtility(1, '1-99', new Context());
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'columnHasRelationToResolve'));
        self::assertTrue($subjectMethodReflection->invoke(
            $subject,
            [
                'config' => [
                    'type' => 'inline',
                    'MM' => 'xyz',
                ],
            ]
        ));
    }

    #[Test]
    public function columnHasRelationToResolveDetectsSelectFieldAsLocal(): void
    {
        $subject = new RootlineUtility(1, '1-99', new Context());
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'columnHasRelationToResolve'));
        self::assertFalse($subjectMethodReflection->invoke(
            $subject,
            [
                'config' => [
                    'type' => 'select',
                ],
            ]
        ));
    }

    #[Test]
    public function columnHasRelationToResolveDetectsSelectFieldWithMMAsRemote(): void
    {
        $subject = new RootlineUtility(1, '1-99', new Context());
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'columnHasRelationToResolve'));
        self::assertTrue($subjectMethodReflection->invoke(
            $subject,
            [
                'config' => [
                    'type' => 'select',
                    'MM' => 'xyz',
                ],
            ]
        ));
    }

    #[Test]
    public function getCacheIdentifierContainsAllContextParameters(): void
    {
        $cacheFrontend = new NullFrontend('some-frontend');
        $context = new Context();
        $context->setAspect('workspace', new WorkspaceAspect(15));
        $context->setAspect('visibility', new VisibilityAspect(true));
        $context->setAspect('language', new LanguageAspect(8, 8, LanguageAspect::OVERLAYS_OFF));
        $subject = new RootlineUtility(42, '47-11', $context);
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'getCacheIdentifier'));
        self::assertSame('42_47-11_8_15_0_1', $subjectMethodReflection->invoke($subject));
        self::assertTrue($cacheFrontend->isValidEntryIdentifier($subjectMethodReflection->invoke($subject)));
        $context->setAspect('workspace', new WorkspaceAspect(0));
        $subject = new RootlineUtility(42, '47-11', $context);
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'getCacheIdentifier'));
        self::assertSame('42_47-11_8_0_0_1', $subjectMethodReflection->invoke($subject));
        self::assertTrue($cacheFrontend->isValidEntryIdentifier($subjectMethodReflection->invoke($subject)));
    }

    #[Test]
    public function getWithMissingPagesColumnsTcaThrowsException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1712572738);
        unset($GLOBALS['TCA']['pages']['columns']);
        (new RootlineUtility(1))->get();
    }

    #[Test]
    public function getWithPagesColumnsTcaNonArrayThrowsException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1712572738);
        $GLOBALS['TCA']['pages']['columns'] = 'This is not an array.';
        (new RootlineUtility(1))->get();
    }

    #[Test]
    public function getForRootPageOnlyReturnsRootPageInformation(): void
    {
        $rootPageUid = 1;
        $result = (new RootlineUtility($rootPageUid))->get();
        self::assertCount(1, $result);
        self::assertSame($rootPageUid, (int)$result[0]['uid']);
    }

    #[Test]
    public function rootlineFailsForDeletedParentPageInWorkspace(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->expectExceptionCode(1343464101);
        $context = new Context();
        $context->setAspect('workspace', new WorkspaceAspect(1));
        (new RootlineUtility(1002, '', $context))->get();
    }

    public static function getResolvesCorrectlyDataProvider(): \Generator
    {
        yield 'standard nested page lang default' => [
            'uid' => 1001,
            'language' => 0,
            'workspace' => 0,
            'testFields' => ['uid', 'pid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 'title'],
            'expected' => [
                2 => [
                    'uid' => 1001,
                    'pid' => 1000,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'EN Parent 1 Sub 1',
                ],
                1 => [
                    'uid' => 1000,
                    'pid' => 1,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'EN Parent 1',
                ],
                0 => [
                    'uid' => 1,
                    'pid' => 0,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'EN Root',
                ],
            ],
        ];
        yield 'standard nested page lang FR, requesting with default lang id' => [
            'uid' => 1001,
            'language' => 1,
            'workspace' => 0,
            'testFields' => ['uid', 'pid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 'title', '_LOCALIZED_UID', '_REQUESTED_OVERLAY_LANGUAGE'],
            'expected' => [
                2 => [
                    'uid' => 1001,
                    'pid' => 1000,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'FR Parent 1 Sub 1',
                    '_LOCALIZED_UID' => 1002,
                    '_REQUESTED_OVERLAY_LANGUAGE' => 1,
                ],
                1 => [
                    'uid' => 1000,
                    'pid' => 1,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'EN Parent 1',
                ],
                0 => [
                    'uid' => 1,
                    'pid' => 0,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'EN Root',
                ],
            ],
        ];
        // @todo: Inconsistent. Compare with above set: When requesting a localized uid directly, 'uid' is the
        //        localized one, and '_LOCALIZED_UID' and '_REQUESTED_OVERLAY_LANGUAGE' are not set at all.
        yield 'standard nested page lang FR, requesting with FR lang id' => [
            'uid' => 1002,
            'language' => 1,
            'workspace' => 0,
            'testFields' => ['uid', 'pid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 'title', '_LOCALIZED_UID', '_REQUESTED_OVERLAY_LANGUAGE'],
            'expected' => [
                2 => [
                    'uid' => 1002,
                    'pid' => 1000,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'FR Parent 1 Sub 1',
                ],
                1 => [
                    'uid' => 1000,
                    'pid' => 1,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'EN Parent 1',
                ],
                0 => [
                    'uid' => 1,
                    'pid' => 0,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'EN Root',
                ],
            ],
        ];
        yield 'new page in workspaces' => [
            'uid' => 1011,
            'language' => 0,
            'workspace' => 2,
            'testFields' => ['uid', 'pid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 'title'],
            'expected' => [
                2 => [
                    'uid' => 1011,
                    'pid' => 1010,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 2,
                    't3ver_state' => 1,
                    'title' => 'EN WS2-new Parent 2 Sub 1',
                ],
                1 => [
                    'uid' => 1010,
                    'pid' => 1,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'EN Parent 2',
                ],
                0 => [
                    'uid' => 1,
                    'pid' => 0,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'EN Root',
                ],
            ],
        ];
        yield 'moved in workspaces, requesting with live id in live' => [
            'uid' => 1020,
            'language' => 0,
            'workspace' => 0,
            'testFields' => ['uid', 'pid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 'title'],
            'expected' => [
                1 => [
                    'uid' => 1020,
                    'pid' => 1,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'EN To Move in WS',
                ],
                0 => [
                    'uid' => 1,
                    'pid' => 0,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'EN Root',
                ],
            ],
        ];
        yield 'moved in workspaces, requesting with live id in workspace' => [
            'uid' => 1020,
            'language' => 0,
            'workspace' => 2,
            'testFields' => ['uid', 'pid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 'title', '_ORIG_uid', '_ORIG_pid'],
            'expected' => [
                2 => [
                    'uid' => 1020,
                    'pid' => 1021,
                    't3ver_oid' => 1020,
                    't3ver_wsid' => 2,
                    't3ver_state' => 4,
                    'title' => 'EN WS2-moved Move in WS',
                    '_ORIG_uid' => 1022,
                    '_ORIG_pid' => 1,
                ],
                1 => [
                    'uid' => 1021,
                    'pid' => 1,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'EN Move target',
                ],
                0 => [
                    'uid' => 1,
                    'pid' => 0,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'EN Root',
                ],
            ],
        ];
        yield 'moved in workspaces, requesting with workspace id in workspace' => [
            'uid' => 1022,
            'language' => 0,
            'workspace' => 2,
            'testFields' => ['uid', 'pid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 'title', '_ORIG_uid', '_ORIG_pid'],
            'expected' => [
                2 => [
                    'uid' => 1020,
                    'pid' => 1021,
                    't3ver_oid' => 1020,
                    't3ver_wsid' => 2,
                    't3ver_state' => 4,
                    'title' => 'EN WS2-moved Move in WS',
                    '_ORIG_uid' => 1022,
                    '_ORIG_pid' => 1,
                ],
                1 => [
                    'uid' => 1021,
                    'pid' => 1,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'EN Move target',
                ],
                0 => [
                    'uid' => 1,
                    'pid' => 0,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'EN Root',
                ],
            ],
        ];
        yield 'media lang default' => [
            'uid' => 1031,
            'language' => 0,
            'workspace' => 0,
            'testFields' => ['uid', 'title', 'media'],
            'expected' => [
                2 => [
                    'uid' => 1031,
                    'title' => 'EN Parent 3 Sub 1',
                    'media' => '1001,1000',
                ],
                1 => [
                    'uid' => 1030,
                    'title' => 'EN Parent 3',
                    'media' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'media' => '',
                ],
            ],
        ];
        yield 'media lang FR' => [
            'uid' => 1031,
            'language' => 1,
            'workspace' => 0,
            'testFields' => ['uid', 'title', 'media'],
            'expected' => [
                2 => [
                    'uid' => 1031,
                    'title' => 'FR Parent 3 Sub 1',
                    'media' => '1010,1011',
                ],
                1 => [
                    'uid' => 1030,
                    'title' => 'EN Parent 3',
                    'media' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'media' => '',
                ],
            ],
        ];
        yield 'media lang default, workspace new' => [
            'uid' => 1041,
            'language' => 0,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'media'],
            'expected' => [
                2 => [
                    'uid' => 1041,
                    'title' => 'EN WS2-new Parent 4 Sub 1 with media',
                    'media' => '1101,1100',
                ],
                1 => [
                    'uid' => 1040,
                    'title' => 'EN Parent 4',
                    'media' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'media' => '',
                ],
            ],
        ];
        yield 'media lang default, workspace one media deleted' => [
            'uid' => 1051,
            'language' => 0,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'media'],
            'expected' => [
                2 => [
                    'uid' => 1051,
                    'title' => 'EN WS2-changed Parent 5 Sub 1 with media deleted',
                    // It would be better if these would be the ws-overlay uids directly.
                    'media' => '1200',
                ],
                1 => [
                    'uid' => 1050,
                    'title' => 'EN Parent 5',
                    'media' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'media' => '',
                ],
            ],
        ];
    }

    #[DataProvider('getResolvesCorrectlyDataProvider')]
    #[Test]
    public function getResolvesCorrectly(int $uid, int $language, int $workspace, array $testFields, array $expected): void
    {
        $context = new Context();
        $context->setAspect('workspace', new WorkspaceAspect($workspace));
        $context->setAspect('language', new LanguageAspect($language));
        $result = (new RootlineUtility($uid, '', $context))->get();
        self::assertSame($expected, $this->filterExpectedValues($result, $testFields));
    }
}
