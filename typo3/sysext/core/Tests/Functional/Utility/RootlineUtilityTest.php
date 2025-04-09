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
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Domain\DateTimeFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\Page\CircularRootLineException;
use TYPO3\CMS\Core\Exception\Page\PageNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
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
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_rootlineutility',
    ];

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
        $this->writeSiteConfiguration(
            'second',
            $this->buildSiteConfiguration(2, 'https://other.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
        self::importCSVDataSet(__DIR__ . '/Fixtures/RootlineUtilityImport.csv');
        $this->setUpFrontendRootPage(1, [], ['config' => '# rootpage 1']);
        $this->setUpFrontendRootPage(2, [], ['config' => '# rootpage 2']);
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
        self::assertFalse($subjectMethodReflection->invoke($subject, 1));
    }

    #[Test]
    public function isMountedPageWithMatchingMountPointParameterReturnsTrue(): void
    {
        $subject = new RootlineUtility(1, '1-99', new Context());
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'isMountedPage'));
        self::assertTrue($subjectMethodReflection->invoke($subject, 1));
    }

    #[Test]
    public function isMountedPageWithNonMatchingMountPointParameterReturnsFalse(): void
    {
        $subject = new RootlineUtility(1, '99-99', new Context());
        $subjectMethodReflection = (new \ReflectionMethod($subject, 'isMountedPage'));
        self::assertFalse($subjectMethodReflection->invoke($subject, 1));
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
                'type' => 'group',
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
                'type' => 'group',
                'MM' => 'tx_xyz',
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
                'type' => 'inline',
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
                'type' => 'inline',
                'foreign_field' => 'xyz',
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
                'type' => 'inline',
                'MM' => 'xyz',
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
                'type' => 'select',
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
                'type' => 'select',
                'MM' => 'xyz',
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
        $rootPageUid = 1;
        $result = (new RootlineUtility($rootPageUid))->get();
        self::assertCount(1, $result);
        self::assertSame($rootPageUid, (int)$result[0]['uid']);
    }

    #[Test]
    public function rootlineFailsForDeletedParentPageInWorkspace(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->expectExceptionCode(1721913589);
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
                    'title' => 'EN Parent 1000 Sub 1',
                ],
                1 => [
                    'uid' => 1000,
                    'pid' => 1,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'EN Parent 1000',
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
                    'title' => 'FR Parent 1000 Sub 1',
                    '_LOCALIZED_UID' => 1002,
                    '_REQUESTED_OVERLAY_LANGUAGE' => 1,
                ],
                1 => [
                    'uid' => 1000,
                    'pid' => 1,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'EN Parent 1000',
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
                    'title' => 'FR Parent 1000 Sub 1',
                ],
                1 => [
                    'uid' => 1000,
                    'pid' => 1,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'EN Parent 1000',
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
                    'title' => 'EN WS2-new Parent 1010 Sub 1',
                ],
                1 => [
                    'uid' => 1010,
                    'pid' => 1,
                    't3ver_oid' => 0,
                    't3ver_wsid' => 0,
                    't3ver_state' => 0,
                    'title' => 'EN Parent 1010',
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
            'uid' => 2001,
            'language' => 0,
            'workspace' => 0,
            'testFields' => ['uid', 'title', 'media'],
            'expected' => [
                2 => [
                    'uid' => 2001,
                    'title' => 'EN Parent 2000 Sub 1',
                    'media' => '1001,1000',
                ],
                1 => [
                    'uid' => 2000,
                    'title' => 'EN Parent 2000',
                    'media' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'media' => '',
                ],
            ],
        ];
        yield 'media lang FR, requesting with default lang uid' => [
            'uid' => 2001,
            'language' => 1,
            'workspace' => 0,
            'testFields' => ['uid', 'title', 'media'],
            'expected' => [
                2 => [
                    'uid' => 2001,
                    'title' => 'FR Parent 2000 Sub 1',
                    'media' => '1010,1011',
                ],
                1 => [
                    'uid' => 2000,
                    'title' => 'EN Parent 2000',
                    'media' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'media' => '',
                ],
            ],
        ];
        yield 'media lang FR, requesting with FR lang uid' => [
            'uid' => 2002,
            'language' => 1,
            'workspace' => 0,
            'testFields' => ['uid', 'title', 'media'],
            'expected' => [
                2 => [
                    'uid' => 2002,
                    'title' => 'FR Parent 2000 Sub 1',
                    'media' => '1010,1011',
                ],
                1 => [
                    'uid' => 2000,
                    'title' => 'EN Parent 2000',
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
            'uid' => 2011,
            'language' => 0,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'media'],
            'expected' => [
                2 => [
                    'uid' => 2011,
                    'title' => 'EN WS2-new Parent 2010 Sub 1 with media',
                    'media' => '1101,1100',
                ],
                1 => [
                    'uid' => 2010,
                    'title' => 'EN Parent 2010',
                    'media' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'media' => '',
                ],
            ],
        ];
        yield 'media lang FR, workspace new, requesting with default lang uid' => [
            'uid' => 2021,
            'language' => 1,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'media'],
            'expected' => [
                2 => [
                    'uid' => 2021,
                    'title' => 'FR WS2-new Parent 2020 Sub 1 with media',
                    'media' => '1201,1200',
                ],
                1 => [
                    'uid' => 2020,
                    'title' => 'EN Parent 2020',
                    'media' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'media' => '',
                ],
            ],
        ];
        yield 'media lang FR, workspace new, requesting with FR lang uid' => [
            'uid' => 2022,
            'language' => 1,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'media'],
            'expected' => [
                2 => [
                    'uid' => 2022,
                    'title' => 'FR WS2-new Parent 2020 Sub 1 with media',
                    'media' => '1201,1200',
                ],
                1 => [
                    'uid' => 2020,
                    'title' => 'EN Parent 2020',
                    'media' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'media' => '',
                ],
            ],
        ];
        yield 'media lang default, workspace media elements changed, requesting with live uid' => [
            'uid' => 2031,
            'language' => 0,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'media'],
            'expected' => [
                2 => [
                    'uid' => 2031,
                    'title' => 'EN WS2-changed Parent 2030 Sub 1 with media changed',
                    'media' => '1304,1305,1307',
                ],
                1 => [
                    'uid' => 2030,
                    'title' => 'EN Parent 2030',
                    'media' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'media' => '',
                ],
            ],
        ];
        yield 'media lang default, workspace media elements changed, requesting with workspace uid' => [
            'uid' => 2032,
            'language' => 0,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'media'],
            'expected' => [
                2 => [
                    'uid' => 2032,
                    'title' => 'EN WS2-changed Parent 2030 Sub 1 with media changed',
                    'media' => '1304,1305,1307',
                ],
                1 => [
                    'uid' => 2030,
                    'title' => 'EN Parent 2030',
                    'media' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'media' => '',
                ],
            ],
        ];
        yield 'media lang FR, workspace media elements changed, requesting with live uid and default lang' => [
            'uid' => 2041,
            'language' => 1,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'media'],
            'expected' => [
                2 => [
                    'uid' => 2041,
                    'title' => 'FR WS2-changed Parent 2040 Sub 1 with media changed',
                    'media' => '1404,1407,1405',
                ],
                1 => [
                    'uid' => 2040,
                    'title' => 'EN Parent 2040',
                    'media' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'media' => '',
                ],
            ],
        ];
        yield 'media lang FR, workspace media elements changed, requesting with live uid and FR lang' => [
            'uid' => 2042,
            'language' => 1,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'media'],
            'expected' => [
                2 => [
                    'uid' => 2042,
                    'title' => 'FR WS2-changed Parent 2040 Sub 1 with media changed',
                    'media' => '1404,1407,1405',
                ],
                1 => [
                    'uid' => 2040,
                    'title' => 'EN Parent 2040',
                    'media' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'media' => '',
                ],
            ],
        ];
        yield 'media lang FR, workspace media elements changed, requesting with workspace uid' => [
            'uid' => 2043,
            'language' => 1,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'media'],
            'expected' => [
                2 => [
                    'uid' => 2043,
                    'title' => 'FR WS2-changed Parent 2040 Sub 1 with media changed',
                    'media' => '1404,1407,1405',
                ],
                1 => [
                    'uid' => 2040,
                    'title' => 'EN Parent 2040',
                    'media' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'media' => '',
                ],
            ],
        ];

        yield 'categories lang default' => [
            'uid' => 3010,
            'language' => 0,
            'workspace' => 0,
            'testFields' => ['uid', 'title', 'categories', 'categories_other'],
            'expected' => [
                2 => [
                    'uid' => 3010,
                    'title' => 'EN Parent 3000 Sub 10',
                    'categories' => '30,10,60,90,120',
                    'categories_other' => '20,30',
                ],
                1 => [
                    'uid' => 3000,
                    'title' => 'EN Parent 3000 contains categories',
                    'categories' => '',
                    'categories_other' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'categories' => '',
                    'categories_other' => '',
                ],
            ],
        ];
        yield 'categories lang FR, requesting with default lang uid' => [
            'uid' => 3020,
            'language' => 1,
            'workspace' => 0,
            'testFields' => ['uid', 'title', 'categories', 'categories_other'],
            'expected' => [
                2 => [
                    'uid' => 3020,
                    'title' => 'FR Parent 3000 Sub 20',
                    'categories' => '30,20,60,90,120',
                    'categories_other' => '10,20',
                ],
                1 => [
                    'uid' => 3000,
                    'title' => 'FR Parent 3000 contains categories',
                    'categories' => '',
                    'categories_other' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'categories' => '',
                    'categories_other' => '',
                ],
            ],
        ];
        yield 'categories lang FR, requesting with FR lang uid' => [
            'uid' => 3021,
            'language' => 1,
            'workspace' => 0,
            'testFields' => ['uid', 'title', 'categories', 'categories_other'],
            'expected' => [
                2 => [
                    'uid' => 3021,
                    'title' => 'FR Parent 3000 Sub 20',
                    'categories' => '30,20,60,90,120',
                    'categories_other' => '10,20',
                ],
                1 => [
                    'uid' => 3000,
                    'title' => 'FR Parent 3000 contains categories',
                    'categories' => '',
                    'categories_other' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'categories' => '',
                    'categories_other' => '',
                ],
            ],
        ];
        yield 'categories lang default, workspace new' => [
            'uid' => 3030,
            'language' => 0,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'categories', 'categories_other'],
            'expected' => [
                2 => [
                    'uid' => 3030,
                    'title' => 'EN WS2-new Parent 3000 Sub 30',
                    'categories' => '30,10,60,90,120,140',
                    'categories_other' => '20,30',
                ],
                1 => [
                    'uid' => 3000,
                    'title' => 'EN Parent 3000 contains categories',
                    'categories' => '',
                    'categories_other' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'categories' => '',
                    'categories_other' => '',
                ],
            ],
        ];
        yield 'categories lang FR, workspace new, requesting with default lang uid' => [
            'uid' => 3040,
            'language' => 1,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'categories', 'categories_other'],
            'expected' => [
                2 => [
                    'uid' => 3040,
                    'title' => 'FR WS2-new Parent 3000 Sub 40',
                    'categories' => '30,10,60,90,120,140',
                    'categories_other' => '20,30',
                ],
                1 => [
                    'uid' => 3000,
                    'title' => 'FR Parent 3000 contains categories',
                    'categories' => '',
                    'categories_other' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'categories' => '',
                    'categories_other' => '',
                ],
            ],
        ];
        yield 'categories lang FR, workspace new, requesting with FR lang uid' => [
            'uid' => 3041,
            'language' => 1,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'categories', 'categories_other'],
            'expected' => [
                2 => [
                    'uid' => 3041,
                    'title' => 'FR WS2-new Parent 3000 Sub 40',
                    'categories' => '30,10,60,90,120,140',
                    'categories_other' => '20,30',
                ],
                1 => [
                    'uid' => 3000,
                    'title' => 'FR Parent 3000 contains categories',
                    'categories' => '',
                    'categories_other' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'categories' => '',
                    'categories_other' => '',
                ],
            ],
        ];
        yield 'categories lang default, workspace categories changed, requesting with live uid' => [
            'uid' => 3050,
            'language' => 0,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'categories', 'categories_other'],
            'expected' => [
                2 => [
                    'uid' => 3050,
                    'title' => 'EN WS2-changed Parent 3000 Sub 50',
                    // @todo missing cases: - a category is changed (eg. title) in ws
                    //                      - a category is unhidden, starttime, endtime enabled in ws, while it is not in live
                    //                      - this test case for FR, as with 'media lang FR, workspace media elements changed'
                    // @todo observations: While inline foreign_field localized relations point to the localized records, MM relations do not
                    //                     on the local 'category' side. Those need to be overlayed when dealing with localized
                    //                     categories. This also has the side effect that hidden,starttime and endtime restrictions
                    //                     from the default language kick in, and those of the localized overlays are ignored.
                    'categories' => '30,60,90,120,140,20',
                    'categories_other' => '20',
                ],
                1 => [
                    'uid' => 3000,
                    'title' => 'EN Parent 3000 contains categories',
                    'categories' => '',
                    'categories_other' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'categories' => '',
                    'categories_other' => '',
                ],
            ],
        ];
        yield 'categories lang default, workspace categories changed, requesting with workspace uid' => [
            'uid' => 3051,
            'language' => 0,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'categories', 'categories_other'],
            'expected' => [
                2 => [
                    'uid' => 3051,
                    'title' => 'EN WS2-changed Parent 3000 Sub 50',
                    'categories' => '30,60,90,120,140,20',
                    'categories_other' => '20',
                ],
                1 => [
                    'uid' => 3000,
                    'title' => 'EN Parent 3000 contains categories',
                    'categories' => '',
                    'categories_other' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'categories' => '',
                    'categories_other' => '',
                ],
            ],
        ];

        yield 'hotel lang default' => [
            'uid' => 4010,
            'language' => 0,
            'workspace' => 0,
            'testFields' => ['uid', 'title', 'tx_testrootlineutility_hotels'],
            'expected' => [
                2 => [
                    'uid' => 4010,
                    'title' => 'EN Parent 4000 Sub 10',
                    'tx_testrootlineutility_hotels' => '1001,1000',
                ],
                1 => [
                    'uid' => 4000,
                    'title' => 'EN Parent 4000',
                    'tx_testrootlineutility_hotels' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'tx_testrootlineutility_hotels' => '',
                ],
            ],
        ];
        yield 'hotel lang FR, requesting with default lang uid' => [
            'uid' => 4020,
            'language' => 1,
            'workspace' => 0,
            'testFields' => ['uid', 'title', 'tx_testrootlineutility_hotels'],
            'expected' => [
                2 => [
                    'uid' => 4020,
                    'title' => 'FR Parent 4000 Sub 20',
                    'tx_testrootlineutility_hotels' => '1101,1103,1112,1118,1124,1125',
                ],
                1 => [
                    'uid' => 4000,
                    'title' => 'EN Parent 4000',
                    'tx_testrootlineutility_hotels' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'tx_testrootlineutility_hotels' => '',
                ],
            ],
        ];
        yield 'hotel lang FR, requesting with FR lang uid' => [
            'uid' => 4021,
            'language' => 1,
            'workspace' => 0,
            'testFields' => ['uid', 'title', 'tx_testrootlineutility_hotels'],
            'expected' => [
                2 => [
                    'uid' => 4021,
                    'title' => 'FR Parent 4000 Sub 20',
                    'tx_testrootlineutility_hotels' => '1101,1103,1112,1118,1124,1125',
                ],
                1 => [
                    'uid' => 4000,
                    'title' => 'EN Parent 4000',
                    'tx_testrootlineutility_hotels' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'tx_testrootlineutility_hotels' => '',
                ],
            ],
        ];
        yield 'hotel lang default, workspace new' => [
            'uid' => 4030,
            'language' => 0,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'tx_testrootlineutility_hotels'],
            'expected' => [
                2 => [
                    'uid' => 4030,
                    'title' => 'EN WS2-new Parent 4000 Sub 30',
                    'tx_testrootlineutility_hotels' => '1201,1200',
                ],
                1 => [
                    'uid' => 4000,
                    'title' => 'EN Parent 4000',
                    'tx_testrootlineutility_hotels' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'tx_testrootlineutility_hotels' => '',
                ],
            ],
        ];
        yield 'hotel lang FR, workspace new, requesting with default lang uid' => [
            'uid' => 4040,
            'language' => 1,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'tx_testrootlineutility_hotels'],
            'expected' => [
                2 => [
                    'uid' => 4040,
                    'title' => 'FR WS2-new Parent 4000 Sub 40',
                    'tx_testrootlineutility_hotels' => '1301,1303,1312,1318,1324,1325',
                ],
                1 => [
                    'uid' => 4000,
                    'title' => 'EN Parent 4000',
                    'tx_testrootlineutility_hotels' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'tx_testrootlineutility_hotels' => '',
                ],
            ],
        ];
        yield 'hotel lang FR, workspace new, requesting with FR lang uid' => [
            'uid' => 4041,
            'language' => 1,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'tx_testrootlineutility_hotels'],
            'expected' => [
                2 => [
                    'uid' => 4041,
                    'title' => 'FR WS2-new Parent 4000 Sub 40',
                    'tx_testrootlineutility_hotels' => '1301,1303,1312,1318,1324,1325',
                ],
                1 => [
                    'uid' => 4000,
                    'title' => 'EN Parent 4000',
                    'tx_testrootlineutility_hotels' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'tx_testrootlineutility_hotels' => '',
                ],
            ],
        ];
        yield 'hotel lang default, workspace hotels changed, requesting with live uid' => [
            'uid' => 4050,
            'language' => 0,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'tx_testrootlineutility_hotels'],
            'expected' => [
                2 => [
                    'uid' => 4050,
                    'title' => 'EN WS2-changed Parent 4000 Sub 50',
                    'tx_testrootlineutility_hotels' => '1401,1403,1404,1412,1418,1424',
                ],
                1 => [
                    'uid' => 4000,
                    'title' => 'EN Parent 4000',
                    'tx_testrootlineutility_hotels' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'tx_testrootlineutility_hotels' => '',
                ],
            ],
        ];
        yield 'hotel lang default, workspace hotels changed, requesting with workspace uid' => [
            'uid' => 4051,
            'language' => 0,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'tx_testrootlineutility_hotels'],
            'expected' => [
                2 => [
                    'uid' => 4051,
                    'title' => 'EN WS2-changed Parent 4000 Sub 50',
                    'tx_testrootlineutility_hotels' => '1401,1403,1404,1412,1418,1424',
                ],
                1 => [
                    'uid' => 4000,
                    'title' => 'EN Parent 4000',
                    'tx_testrootlineutility_hotels' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'tx_testrootlineutility_hotels' => '',
                ],
            ],
        ];
        yield 'hotel lang FR, workspace hotels changed, requesting with live uid and default lang' => [
            'uid' => 4060,
            'language' => 1,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'tx_testrootlineutility_hotels'],
            'expected' => [
                2 => [
                    'uid' => 4060,
                    'title' => 'FR WS2-changed Parent 4000 Sub 60',
                    'tx_testrootlineutility_hotels' => '1502,1504,1505,1513,1519,1525',
                ],
                1 => [
                    'uid' => 4000,
                    'title' => 'EN Parent 4000',
                    'tx_testrootlineutility_hotels' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'tx_testrootlineutility_hotels' => '',
                ],
            ],
        ];
        yield 'hotel lang FR, workspace hotels changed, requesting with live uid and FR lang' => [
            'uid' => 4061,
            'language' => 1,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'tx_testrootlineutility_hotels'],
            'expected' => [
                2 => [
                    'uid' => 4061,
                    'title' => 'FR WS2-changed Parent 4000 Sub 60',
                    'tx_testrootlineutility_hotels' => '1502,1504,1505,1513,1519,1525',
                ],
                1 => [
                    'uid' => 4000,
                    'title' => 'EN Parent 4000',
                    'tx_testrootlineutility_hotels' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'tx_testrootlineutility_hotels' => '',
                ],
            ],
        ];
        yield 'hotel lang FR, workspace hotels changed, requesting with workspace uid' => [
            'uid' => 4062,
            'language' => 1,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'tx_testrootlineutility_hotels'],
            'expected' => [
                2 => [
                    'uid' => 4062,
                    'title' => 'FR WS2-changed Parent 4000 Sub 60',
                    'tx_testrootlineutility_hotels' => '1502,1504,1505,1513,1519,1525',
                ],
                1 => [
                    'uid' => 4000,
                    'title' => 'EN Parent 4000',
                    'tx_testrootlineutility_hotels' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'tx_testrootlineutility_hotels' => '',
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
        $context->setAspect('date', new DateTimeAspect(DateTimeFactory::createFromTimestamp(time())));
        $result = (new RootlineUtility($uid, '', $context))->get();
        self::assertSame($expected, $this->filterExpectedValues($result, $testFields));
    }

    public static function getResolvesHiddenRelationsCorrectlyDataProvider(): \Generator
    {
        yield 'do not fetch hidden records' => [
            'uid' => 5010,
            'includeHiddenRecords' => false,
            'testFields' => ['uid', 'title', 'categories', 'categories_other', 'tx_testrootlineutility_hotels'],
            'expected' => [
                2 => [
                    'uid' => 5010,
                    'title' => 'EN Parent 5000 Sub 10',
                    'categories' => '10',
                    'categories_other' => '',
                    'tx_testrootlineutility_hotels' => '1600',
                ],
                1 => [
                    'uid' => 5000,
                    'title' => 'EN Parent 5000',
                    'categories' => '',
                    'categories_other' => '',
                    'tx_testrootlineutility_hotels' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'categories' => '',
                    'categories_other' => '',
                    'tx_testrootlineutility_hotels' => '',
                ],
            ],
        ];
        yield 'fetch hidden records' => [
            'uid' => 5010,
            'includeHiddenRecords' => true,
            'testFields' => ['uid', 'title', 'categories', 'categories_other', 'tx_testrootlineutility_hotels'],
            'expected' => [
                2 => [
                    'uid' => 5010,
                    'title' => 'EN Parent 5000 Sub 10',
                    'categories' => '10,50',
                    'categories_other' => '',
                    'tx_testrootlineutility_hotels' => '1600,1601',
                ],
                1 => [
                    'uid' => 5000,
                    'title' => 'EN Parent 5000',
                    'categories' => '',
                    'categories_other' => '',
                    'tx_testrootlineutility_hotels' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'categories' => '',
                    'categories_other' => '',
                    'tx_testrootlineutility_hotels' => '',
                ],
            ],
        ];
    }

    #[DataProvider('getResolvesHiddenRelationsCorrectlyDataProvider')]
    #[Test]
    public function getResolvesHiddenRelationsCorrectly(int $uid, bool $includeHiddenRecords, array $testFields, array $expected): void
    {
        $context = new Context();
        $context->setAspect('workspace', new WorkspaceAspect(0));
        $context->setAspect('language', new LanguageAspect(0));
        $context->setAspect('date', new DateTimeAspect(DateTimeFactory::createFromTimestamp(time())));
        $context->setAspect('visibility', new VisibilityAspect(false, $includeHiddenRecords, false, false));
        $result = (new RootlineUtility($uid, '', $context))->get();
        self::assertSame($expected, $this->filterExpectedValues($result, $testFields));
    }

    public static function getResolvesStarttimeEndtimeRelationsCorrectlyDataProvider(): \Generator
    {
        yield 'include scheduled records' => [
            'uid' => 6010,
            'includeScheduledRecords' => true,
            'simulateTime' => 1713132000, // Not relevant with includeScheduledRecords being true here
            'testFields' => ['uid', 'title', 'categories', 'categories_other', 'tx_testrootlineutility_hotels'],
            'expected' => [
                2 => [
                    'uid' => 6010,
                    'title' => 'EN Parent 6000 Sub 10',
                    'categories' => '160,161,162,163',
                    'categories_other' => '',
                    'tx_testrootlineutility_hotels' => '1700,1701,1702,1703',
                ],
                1 => [
                    'uid' => 6000,
                    'title' => 'EN Parent 6000',
                    'categories' => '',
                    'categories_other' => '',
                    'tx_testrootlineutility_hotels' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'categories' => '',
                    'categories_other' => '',
                    'tx_testrootlineutility_hotels' => '',
                ],
            ],
        ];
        yield 'simulate time within starttime and endtime restrictions' => [
            'uid' => 6010,
            'includeScheduledRecords' => false,
            'simulateTime' => 1713132000, // 2024-04-15 00:00 UTC - within fixture starttime and endtime restrictions
            'testFields' => ['uid', 'title', 'categories', 'categories_other', 'tx_testrootlineutility_hotels'],
            'expected' => [
                2 => [
                    'uid' => 6010,
                    'title' => 'EN Parent 6000 Sub 10',
                    'categories' => '160,161,162,163',
                    'categories_other' => '',
                    'tx_testrootlineutility_hotels' => '1700,1701,1702,1703',
                ],
                1 => [
                    'uid' => 6000,
                    'title' => 'EN Parent 6000',
                    'categories' => '',
                    'categories_other' => '',
                    'tx_testrootlineutility_hotels' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'categories' => '',
                    'categories_other' => '',
                    'tx_testrootlineutility_hotels' => '',
                ],
            ],
        ];
        yield 'simulate time before starttime restrictions' => [
            'uid' => 6010,
            'includeScheduledRecords' => false,
            'simulateTime' => 1712268000, // 2024-04-05 00:00 UTC - before starttime restrictions
            'testFields' => ['uid', 'title', 'categories', 'categories_other', 'tx_testrootlineutility_hotels'],
            'expected' => [
                2 => [
                    'uid' => 6010,
                    'title' => 'EN Parent 6000 Sub 10',
                    'categories' => '160,162',
                    'categories_other' => '',
                    'tx_testrootlineutility_hotels' => '1700,1702',
                ],
                1 => [
                    'uid' => 6000,
                    'title' => 'EN Parent 6000',
                    'categories' => '',
                    'categories_other' => '',
                    'tx_testrootlineutility_hotels' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'categories' => '',
                    'categories_other' => '',
                    'tx_testrootlineutility_hotels' => '',
                ],
            ],
        ];
        yield 'simulate time after endtime restrictions' => [
            'uid' => 6010,
            'includeScheduledRecords' => false,
            'simulateTime' => 1713996000, // 2024-04-25 00:00 UTC - after endtime restrictions
            'testFields' => ['uid', 'title', 'categories', 'categories_other', 'tx_testrootlineutility_hotels'],
            'expected' => [
                2 => [
                    'uid' => 6010,
                    'title' => 'EN Parent 6000 Sub 10',
                    'categories' => '160,161',
                    'categories_other' => '',
                    'tx_testrootlineutility_hotels' => '1700,1701',
                ],
                1 => [
                    'uid' => 6000,
                    'title' => 'EN Parent 6000',
                    'categories' => '',
                    'categories_other' => '',
                    'tx_testrootlineutility_hotels' => '',
                ],
                0 => [
                    'uid' => 1,
                    'title' => 'EN Root',
                    'categories' => '',
                    'categories_other' => '',
                    'tx_testrootlineutility_hotels' => '',
                ],
            ],
        ];
    }

    #[DataProvider('getResolvesStarttimeEndtimeRelationsCorrectlyDataProvider')]
    #[Test]
    public function getResolvesStarttimeEndtimeRelationsCorrectly(int $uid, bool $includeScheduledRecords, int $simulateTime, array $testFields, array $expected): void
    {
        $context = new Context();
        $context->setAspect('workspace', new WorkspaceAspect(0));
        $context->setAspect('language', new LanguageAspect(0));
        $context->setAspect('date', new DateTimeAspect(DateTimeFactory::createFromTimestamp($simulateTime)));
        $context->setAspect('visibility', new VisibilityAspect(false, false, false, $includeScheduledRecords));
        $result = (new RootlineUtility($uid, '', $context))->get();
        self::assertSame($expected, $this->filterExpectedValues($result, $testFields));
    }

    #[Test]
    public function rootlineFailsForCyclingRootlineProducedByInvalidConnectionsInLiveWorkspace(): void
    {
        // Note that this test indicates/simulates a corrupted database
        $this->expectException(CircularRootLineException::class);
        $this->expectExceptionCode(1343464103);
        $context = new Context();
        (new RootlineUtility(7020, '', $context))->get();
    }

    #[Test]
    public function rootlineFailsForWorkspaceOverlayCyclingRootlineProducedByInvalidConnections(): void
    {
        // Note that this test indicates/simulates a corrupted database
        $this->expectException(CircularRootLineException::class);
        $this->expectExceptionCode(1343464103);
        $context = new Context();
        $context->setAspect('workspace', new WorkspaceAspect(2));
        (new RootlineUtility(8020, '', $context))->get();
    }

    #[Test]
    public function mountPageReplaceResolvedExpectedSiteRoot(): void
    {
        $testFields = ['uid', 'pid', 'is_siteroot', '_MOUNT_OL', '_MOUNT_PAGE', '_MOUNTED_FROM', '_MP_PARAM'];
        $expected = [
            2 => [
                'uid' => 1001,
                'pid' => 1000,
                'is_siteroot' => 0,
                '_MOUNT_OL' => true,
                '_MOUNT_PAGE' =>
                    [
                        'uid' => 9010,
                        'pid' => 9000,
                        'title' => 'RP2 Parent 9000 Sub 10',
                    ],
                '_MOUNTED_FROM' => 1001,
                '_MP_PARAM' => '1001-9010',
            ],
            1 => [
                'uid' => 9000,
                'pid' => 2,
                'is_siteroot' => 0,
            ],
            0 => [
                'uid' => 2,
                'pid' => 0,
                'is_siteroot' => 1,
            ],
        ];
        $result = (new RootlineUtility(1001, '1001-9010'))->get();
        self::assertSame($expected, $this->filterExpectedValues($result, $testFields));
        self::assertSame('second', GeneralUtility::makeInstance(SiteFinder::class)->getSiteByRootPageId($result[0]['uid'])->getIdentifier());
    }

    #[Test]
    public function mountPageResolvedExpectedSiteRoot(): void
    {
        $testFields = ['uid', 'pid', 'is_siteroot', '_MOUNT_OL', '_MOUNT_PAGE', '_MOUNTED_FROM', '_MP_PARAM'];
        $expected = [
            2 => [
                'uid' => 9020,
                'pid' => 9000,
                'is_siteroot' => 0,
                '_MOUNTED_FROM' => 1010,
                '_MP_PARAM' => '1010-9020',
            ],
            1 => [
                'uid' => 9000,
                'pid' => 2,
                'is_siteroot' => 0,
            ],
            0 => [
                'uid' => 2,
                'pid' => 0,
                'is_siteroot' => 1,
            ],
        ];
        $result = (new RootlineUtility(1010, '1010-9020'))->get();
        self::assertSame($expected, $this->filterExpectedValues($result, $testFields));
        self::assertSame('second', GeneralUtility::makeInstance(SiteFinder::class)->getSiteByRootPageId($result[0]['uid'])->getIdentifier());
    }

    #[Test]
    public function mountedPageVariant1GenerateExpectedRootline(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RootlineUtility_MountPointVariant1.csv');
        $this->writeSiteConfiguration(
            'site1',
            $this->buildSiteConfiguration(10000, 'https://site1.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
        $this->writeSiteConfiguration(
            'site2',
            $this->buildSiteConfiguration(10100, 'https://site2.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
        $result = (new RootlineUtility(10002, '10000-10100', new Context()))->get();
        $testFields = ['uid', 'pid', 'title'];
        $expected = [
            2 => [
                'uid' => 10002,
                'pid' => 10001,
                'title' => 'sub-1-1-1',
            ],
            1 => [
                'uid' => 10001,
                'pid' => 10000,
                'title' => 'sub-1-1',
            ],
            0 => [
                'uid' => 10100,
                'pid' => 0,
                'title' => 'site-2',
            ],
        ];
        self::assertSame($expected, $this->filterExpectedValues($result, $testFields));
        self::assertCount(3, $result);
        self::assertArrayHasKey(0, $result);
        self::assertIsArray($result[0]);
        self::assertSame(10000, $result[0]['_MOUNTED_FROM']);
        self::assertSame('10000-10100', $result[0]['_MP_PARAM']);
        self::assertArrayNotHasKey('_MOUNT_PAGE', $result[0]);
    }

    #[Test]
    public function mountedPageVariant2GenerateExpectedRootline(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RootlineUtility_MountPointVariant2.csv');
        $this->writeSiteConfiguration(
            'site1',
            $this->buildSiteConfiguration(10000, 'https://site1.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
        $this->writeSiteConfiguration(
            'site2',
            $this->buildSiteConfiguration(10100, 'https://site2.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
        $result = (new RootlineUtility(10002, '10000-10100', new Context()))->get();
        $testFields = ['uid', 'pid', 'title'];
        $expected = [
            2 => [
                'uid' => 10002,
                'pid' => 10001,
                'title' => 'sub-1-1-1',
            ],
            1 => [
                'uid' => 10001,
                'pid' => 10000,
                'title' => 'sub-1-1',
            ],
            0 => [
                'uid' => 10000,
                'pid' => 0,
                'title' => 'site-1',
            ],
        ];
        self::assertSame($expected, $this->filterExpectedValues($result, $testFields));
        self::assertCount(3, $result);
        self::assertArrayHasKey(0, $result);
        self::assertIsArray($result[0]);
        self::assertSame(10000, $result[0]['_MOUNTED_FROM']);
        self::assertSame('10000-10100', $result[0]['_MP_PARAM']);
        self::assertTrue($result[0]['_MOUNT_OL']);
        self::assertArrayHasKey('_MOUNT_PAGE', $result[0]);
        self::assertIsArray($result[0]['_MOUNT_PAGE']);
        self::assertSame(10100, $result[0]['_MOUNT_PAGE']['uid']);
        self::assertSame(0, $result[0]['_MOUNT_PAGE']['pid']);
        self::assertSame('site-2', $result[0]['_MOUNT_PAGE']['title']);
    }
}
