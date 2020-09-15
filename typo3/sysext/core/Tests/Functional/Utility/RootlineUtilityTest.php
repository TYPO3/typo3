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

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Exception\Page\PageNotFoundException;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class RootlineUtilityTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-FR', 'direction' => ''],
        'FR-CA' => ['id' => 2, 'title' => 'Franco-Canadian', 'locale' => 'fr_CA.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-CA', 'direction' => ''],
        'ES' => ['id' => 3, 'title' => 'Spanish', 'locale' => 'es_ES.UTF8', 'iso' => 'es', 'hrefLang' => 'es-ES', 'direction' => ''],
    ];

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        RootlineUtility::purgeCaches();

        $this->writeSiteConfiguration(
            'main',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', '/fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', '/fr-ca/', ['FR', 'EN']),
            ]
        );
        $this->withDatabaseSnapshot(function () {
            $this->setUpDatabase();
        });
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::initializeDatabaseSnapshot();
    }

    public static function tearDownAfterClass(): void
    {
        static::destroyDatabaseSnapshot();
        parent::tearDownAfterClass();
    }

    protected function setUpDatabase()
    {
        $backendUser = $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $factory = DataHandlerFactory::fromYamlFile(__DIR__ . '/Fixtures/RootlineScenario.yaml');
        $writer = DataHandlerWriter::withBackendUser($backendUser);
        $writer->invokeFactory($factory);
        static::failIfArrayIsNotEmpty(
            $writer->getErrors()
        );
    }

    /**
     * @test
     */
    public function resolveLivePagesAndSkipWorkspacedVersions()
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('workspace', new WorkspaceAspect(0));
        $subject = new RootlineUtility(1330, '', $context);
        $result = $subject->get();

        $expected = [
            2 => [
                'pid' => 1300,
                'uid' => 1330,
                't3ver_oid' => 0,
                't3ver_wsid' => 0,
                't3ver_state' =>0,
                'title' => 'EN: Board Games'
            ],
            1 => [
                'pid' => 1000,
                'uid' => 1300,
                't3ver_oid' => 0,
                't3ver_wsid' => 0,
                't3ver_state' => 0,
                'title' => 'EN: Products'
            ],
            0 => [
                'pid' => 0,
                'uid' => 1000,
                't3ver_oid' => 0,
                't3ver_wsid' => 0,
                't3ver_state' => 0,
                'title' => 'ACME Global'
            ],
        ];
        self::assertSame($expected, $this->filterExpectedValues($result, ['pid', 'uid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 't3ver_move_id', 'title']));
    }

    /**
     * @test
     */
    public function resolveWorkspaceOverlaysOfNewPageInWorkspace()
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('workspace', new WorkspaceAspect(1));
        $subject = new RootlineUtility(1400, '', $context);
        $result = $subject->get();

        $expected = [
            1 => [
                'pid' => 1000,
                'uid' => 1400,
                't3ver_oid' => 1400,
                't3ver_wsid' => 1,
                't3ver_state' => -1,
                'title' => 'EN: A new page in workspace',
                '_ORIG_pid' => 1000,
                '_ORIG_uid' => 10002,
            ],
            0 => [
                'pid' => 0,
                'uid' => 1000,
                't3ver_oid' => 1000,
                't3ver_wsid' => 1,
                't3ver_state' => 0,
                'title' => 'ACME Global modified in Workspace 1',
                '_ORIG_pid' => 0,
                '_ORIG_uid' => 10000,
            ],
        ];
        self::assertSame($expected, $this->filterExpectedValues($result, ['pid', 'uid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 't3ver_move_id', 'title', '_ORIG_uid', '_ORIG_pid']));
        // Now explicitly requesting the versioned ID, which DOES not hold the same information
        // it explicitly removes the _ORIG_uid, and does not rewrite the uid
        $subject = new RootlineUtility(10002, '', $context);
        $result = $subject->get();
        $expected[1]['uid'] = 10002;
        unset($expected[1]['_ORIG_uid']);
        self::assertSame($expected, $this->filterExpectedValues($result, ['pid', 'uid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 't3ver_move_id', 'title', '_ORIG_uid', '_ORIG_pid']));
    }

    /**
     * @test
     */
    public function resolveLiveRootLineForMovedPage()
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('workspace', new WorkspaceAspect(0));
        $subject = new RootlineUtility(1333, '', $context);
        $result = $subject->get();

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
                'title' => 'EN: Products'
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
        self::assertSame($expected, $this->filterExpectedValues($result, ['pid', 'uid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 't3ver_move_id', 'title', '_ORIG_uid', '_ORIG_pid']));
    }

    /**
     * @test
     */
    public function resolveWorkspaceOverlaysOfMovedPage()
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('workspace', new WorkspaceAspect(1));
        $subject = new RootlineUtility(1333, '', $context);
        $result = $subject->get();

        $expected = [
            3 => [
                'pid' => 1320,
                'uid' => 1333,
                't3ver_oid' => 1333,
                't3ver_wsid' => 1,
                't3ver_state' => 4,
                'title' => 'EN: Risk',
                '_ORIG_pid' => 1330, // Pointing to the LIVE pid! WHY? All others point to the same PID!
                '_ORIG_uid' => 10001,
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
                'title' => 'EN: Products'
            ],
            0 => [
                'pid' => 0,
                'uid' => 1000,
                't3ver_oid' => 1000,
                't3ver_wsid' => 1,
                't3ver_state' => 0,
                'title' => 'ACME Global modified in Workspace 1',
                '_ORIG_pid' => 0,
                '_ORIG_uid' => 10000,
            ],
        ];
        self::assertSame($expected, $this->filterExpectedValues($result, ['pid', 'uid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 't3ver_move_id', 'title', '_ORIG_uid', '_ORIG_pid']));

        // Now explicitly requesting the versioned ID, which holds the same result
        $subject = new RootlineUtility(10001, '', $context);
        $result = $subject->get();
        self::assertSame($expected, $this->filterExpectedValues($result, ['pid', 'uid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 't3ver_move_id', 'title', '_ORIG_uid', '_ORIG_pid']));

        // Now explicitly requesting the move placeholder, which gets resolved to the live ID, which holds the same result
        $subject = new RootlineUtility(10004, '', $context);
        $result = $subject->get();
        self::assertSame($expected, $this->filterExpectedValues($result, ['pid', 'uid', 't3ver_oid', 't3ver_wsid', 't3ver_state', 't3ver_move_id', 'title', '_ORIG_uid', '_ORIG_pid']));
    }

    /**
     * @test
     */
    public function rootlineFailsForDeletedParentPageInWorkspace()
    {
        self::expectException(PageNotFoundException::class);
        self::expectExceptionCode(1343464101);
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('workspace', new WorkspaceAspect(2));
        $subject = new RootlineUtility(1310, '', $context);
        $subject->get();
    }

    protected function filterExpectedValues(array $incomingData, array $fields): array
    {
        $result = [];
        foreach ($incomingData as $pos => $values) {
            array_walk($values, function (&$val) {
                if (is_numeric($val)) {
                    $val = (int)$val;
                }
            });
            $result[$pos] = array_filter($values, function ($fieldName) use ($fields) {
                return in_array($fieldName, $fields, true);
            }, ARRAY_FILTER_USE_KEY);
        }
        return $result;
    }
}
