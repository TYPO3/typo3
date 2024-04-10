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
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_rootlineutility',
    ];
    protected array $configurationToUseInTestInstance = [
        'FE' => [
            'addRootLineFields' => 'categories,categories_other,tx_testrootlineutility_hotels',
        ],
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
                    // It would be better if these would be the ws-overlay uids directly.
                    'media' => '1300,1301,1307,1303', // bug: 1303 is hidden=1 in ws and should not be there
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
                    // It would be better if these would be the ws-overlay uids directly.
                    'media' => '1300,1301,1307,1303', // bug: 1303 is hidden=1 in ws and should not be there
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
                    // It would be better if these would be the ws-overlay uids directly.
                    'media' => '1400,1407,1401,1403', // bug: 1403 is hidden
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
                    // It would be better if these would be the ws-overlay uids directly.
                    'media' => '1400,1407,1401,1403', // bug: 1403 is hidden
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
                    // It would be better if these would be the ws-overlay uids directly.
                    'media' => '1400,1407,1401,1403', // bug: 1403 is hidden
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
                    // bugs: deleted, hidden, starttime, endtime ignored
                    'categories' => '30,10,40,50,60,70,80,90,100,110,120,130',
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
                    // bugs: deleted, hidden, starttime, endtime ignored
                    'categories' => '30,20,40,50,60,70,80,90,100,110,120,130',
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
                    // bugs: deleted, hidden, starttime, endtime ignored
                    'categories' => '30,20,40,50,60,70,80,90,100,110,120,130',
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
                    'categories' => '30,10,40,50,60,70,80,90,100,110,120,130,140',
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
                    'categories' => '30,10,40,50,60,70,80,90,100,110,120,130,140',
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
                    'categories' => '30,10,40,50,60,70,80,90,100,110,120,130,140',
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
                    // @todo: 10 is kept but not connected in WS
                    //        140 not found, 20 not found
                    // @todo missing cases: - a category has a delete placeholder in ws
                    //                      - a category is changed (eg. title) in ws
                    //                      - a category is unhidden, starttime, endtime enabled in ws, while it is not in live
                    //                      - this test case for FR, as with 'media lang FR, workspace media elements changed'
                    'categories' => '30,10,40,50,60,70,80,90,100,110,120,130',
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
        yield 'categories lang default, workspace categories changed, requesting with workspace uid' => [
            'uid' => 3051,
            'language' => 0,
            'workspace' => 2,
            'testFields' => ['uid', 'title', 'categories', 'categories_other'],
            'expected' => [
                2 => [
                    'uid' => 3051,
                    'title' => 'EN WS2-changed Parent 3000 Sub 50',
                    'categories' => '30,40,50,60,70,80,90,100,110,120,130,140,20',
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
                    // starttime / endtime not respected
                    'tx_testrootlineutility_hotels' => '1001,1000,1004,1005',
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
                    // starttime / endtime not respected
                    'tx_testrootlineutility_hotels' => '1101,1103,1112,1114,1116,1118,1120,1122,1124,1125',
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
                    // starttime / endtime not respected
                    'tx_testrootlineutility_hotels' => '1101,1103,1112,1114,1116,1118,1120,1122,1124,1125',
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
                    'tx_testrootlineutility_hotels' => '1201,1200,1204,1205',
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
                    'tx_testrootlineutility_hotels' => '1301,1303,1312,1314,1316,1318,1320,1322,1324,1325',
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
                    'tx_testrootlineutility_hotels' => '1301,1303,1312,1314,1316,1318,1320,1322,1324,1325',
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
                    // hidden, starttime, endtime bugs ...
                    'tx_testrootlineutility_hotels' => '1400,1402,1404,1409,1413,1415,1417,1419,1421,1423',
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
                    // hidden, starttime, endtime bugs ...
                    'tx_testrootlineutility_hotels' => '1400,1402,1404,1409,1413,1415,1417,1419,1421,1423',
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
                    // hidden, starttime, endtime bugs ...
                    'tx_testrootlineutility_hotels' => '1501,1503,1505,1510,1514,1516,1518,1520,1522,1524',
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
                    // hidden, starttime, endtime bugs ...
                    'tx_testrootlineutility_hotels' => '1501,1503,1505,1510,1514,1516,1518,1520,1522,1524',
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
                    // hidden, starttime, endtime bugs ...
                    'tx_testrootlineutility_hotels' => '1501,1503,1505,1510,1514,1516,1518,1520,1522,1524',
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
        $result = (new RootlineUtility($uid, '', $context))->get();
        self::assertSame($expected, $this->filterExpectedValues($result, $testFields));
    }
}
