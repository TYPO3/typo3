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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RootlineUtilityTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8'],
        'FR-CA' => ['id' => 2, 'title' => 'Franco-Canadian', 'locale' => 'fr_CA.UTF8'],
        'ES' => ['id' => 3, 'title' => 'Spanish', 'locale' => 'es_ES.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            'main',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', '/fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', '/fr-ca/', ['FR', 'EN']),
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
        $referenceIndexFixResult = GeneralUtility::makeInstance(ReferenceIndex::class)->updateIndex(true);
        if (count($referenceIndexFixResult['errors']) > 0) {
            self::fail('Reference index not clean. ' . LF . implode(LF, $referenceIndexFixResult['errors']));
        }
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
    public function getForRootPageOnlyReturnsRootPageInformation(): void
    {
        $rootPageUid = 1000;
        $result = (new RootlineUtility($rootPageUid))->get();
        self::assertCount(1, $result);
        self::assertSame($rootPageUid, (int)$result[0]['uid']);
    }

    #[Test]
    public function resolveLivePagesAndSkipWorkspacedVersions(): void
    {
        $context = new Context();
        $context->setAspect('workspace', new WorkspaceAspect(0));
        $result = (new RootlineUtility(1330, '', $context))->get();
        $expected = [
            2 => [
                'pid' => 1300,
                'uid' => 1330,
                't3ver_oid' => 0,
                't3ver_wsid' => 0,
                't3ver_state' => 0,
                'title' => 'EN: Board Games',
            ],
            1 => [
                'pid' => 1000,
                'uid' => 1300,
                't3ver_oid' => 0,
                't3ver_wsid' => 0,
                't3ver_state' => 0,
                'title' => 'EN: Products',
            ],
            0 => [
                'pid' => 0,
                'uid' => 1000,
                't3ver_oid' => 0,
                't3ver_wsid' => 0,
                't3ver_state' => 0,
                'title' => 'ACME Global',
            ],
        ];
        self::assertSame($expected, $this->filterExpectedValues($result, ['pid', 'uid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 'title']));
    }

    #[Test]
    public function resolveWorkspaceOverlaysOfNewPageInWorkspace(): void
    {
        $context = new Context();
        $context->setAspect('workspace', new WorkspaceAspect(1));
        $result = (new RootlineUtility(1400, '', $context))->get();
        $expected = [
            1 => [
                'pid' => 1000,
                'uid' => 1400,
                't3ver_oid' => 0,
                't3ver_wsid' => 1,
                't3ver_state' => 1,
                'title' => 'EN: A new page in workspace',
            ],
            0 => [
                'pid' => 0,
                'uid' => 1000,
                't3ver_oid' => 1000,
                't3ver_wsid' => 1,
                't3ver_state' => 0,
                'title' => 'ACME Global modified in Workspace 1',
                '_ORIG_uid' => 10000,
            ],
        ];
        self::assertSame($expected, $this->filterExpectedValues($result, ['pid', 'uid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 'title', '_ORIG_uid', '_ORIG_pid']));
    }

    #[Test]
    public function resolveLiveRootLineForMovedPage(): void
    {
        $context = new Context();
        $context->setAspect('workspace', new WorkspaceAspect(0));
        $result = (new RootlineUtility(1333, '', $context))->get();
        $expected = [
            3 => [
                'pid' => 1330,
                'uid' => 1333,
                't3ver_oid' => 0,
                't3ver_wsid' => 0,
                't3ver_state' => 0,
                'title' => 'EN: Risk',
            ],
            2 => [
                'pid' => 1300,
                'uid' => 1330,
                't3ver_oid' => 0,
                't3ver_wsid' => 0,
                't3ver_state' => 0,
                'title' => 'EN: Board Games',
            ],
            1 => [
                'pid' => 1000,
                'uid' => 1300,
                't3ver_oid' => 0,
                't3ver_wsid' => 0,
                't3ver_state' => 0,
                'title' => 'EN: Products',
            ],
            0 => [
                'pid' => 0,
                'uid' => 1000,
                't3ver_oid' => 0,
                't3ver_wsid' => 0,
                't3ver_state' => 0,
                'title' => 'ACME Global',
            ],
        ];
        self::assertSame($expected, $this->filterExpectedValues($result, ['pid', 'uid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 'title', '_ORIG_uid', '_ORIG_pid']));
    }

    #[Test]
    public function resolveWorkspaceOverlaysOfMovedPage(): void
    {
        $context = new Context();
        $context->setAspect('workspace', new WorkspaceAspect(1));
        $result = (new RootlineUtility(1333, '', $context))->get();
        $expected = [
            3 => [
                'pid' => 1320,
                'uid' => 1333,
                't3ver_oid' => 1333,
                't3ver_wsid' => 1,
                't3ver_state' => 4,
                'title' => 'EN: Risk',
                '_ORIG_uid' => 10001,
                '_ORIG_pid' => 1330, // Pointing to the LIVE pid! WHY? All others point to the same PID! @todo
            ],
            2 => [
                'pid' => 1300,
                'uid' => 1320,
                't3ver_oid' => 0,
                't3ver_wsid' => 0,
                't3ver_state' => 0,
                'title' => 'EN: Card Games',
            ],
            1 => [
                'pid' => 1000,
                'uid' => 1300,
                't3ver_oid' => 0,
                't3ver_wsid' => 0,
                't3ver_state' => 0,
                'title' => 'EN: Products',
            ],
            0 => [
                'pid' => 0,
                'uid' => 1000,
                't3ver_oid' => 1000,
                't3ver_wsid' => 1,
                't3ver_state' => 0,
                'title' => 'ACME Global modified in Workspace 1',
                '_ORIG_uid' => 10000,
            ],
        ];
        self::assertSame($expected, $this->filterExpectedValues($result, ['pid', 'uid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 'title', '_ORIG_uid', '_ORIG_pid']));

        // Now explicitly requesting the versioned ID, which holds the same result
        $result = (new RootlineUtility(10001, '', $context))->get();
        self::assertSame($expected, $this->filterExpectedValues($result, ['pid', 'uid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 'title', '_ORIG_uid', '_ORIG_pid']));
    }

    #[Test]
    public function rootlineFailsForDeletedParentPageInWorkspace(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->expectExceptionCode(1343464101);
        $context = new Context();
        $context->setAspect('workspace', new WorkspaceAspect(2));
        (new RootlineUtility(1310, '', $context))->get();
    }
}
