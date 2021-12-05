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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests for the type=checkbox options "eval" and "validation".
 * These options have themselves two further keywords: maximumRecordsChecked, maximumRecordsCheckedInPid.
 *
 * Assumed scenario
 * ================
 *
 * A checkbox field with:
 *
 * maximumRecordsChecked => 3
 * maximumRecordsCheckedInPid => 2
 *
 * LIVE: Records checked globally: 2
 * LIVE: Records checked on (other) pid 300: 1
 *
 * WORKSPACE: Records checked globally: 3
 * WORKSPACE: Records checked on (other) pid 300: 2
 */
class CheckboxValidationTest extends FunctionalTestCase
{
    protected const PAGE_ID = 200;
    protected const PAGE_ID_OTHER = 300;

    protected $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_datahandler',
    ];

    protected $coreExtensionsToLoad = ['workspaces'];

    protected BackendUserAuthentication $backendUserAuthentication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/CheckboxRecordsEval.csv');
        $this->backendUserAuthentication = $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();
    }

    /**
     * @test
     */
    public function validMaximumRecordsCheckedPermitsPersisting(): void
    {
        $actionService = new ActionService();
        $map = $actionService->createNewRecord('tt_content', self::PAGE_ID, [
            'tx_testdatahandler_checkbox_with_eval' => 1,
        ]);
        $newContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $newContentId);

        self::assertEquals(1, $newContentRecord['tx_testdatahandler_checkbox_with_eval']);
    }

    /**
     * @test
     */
    public function violationOfMaximumRecordsCheckedResetsValueToZero(): void
    {
        $actionService = new ActionService();
        $actionService->createNewRecord('tt_content', self::PAGE_ID, [
            'tx_testdatahandler_checkbox_with_eval' => 1,
        ]);
        $map = $actionService->createNewRecord('tt_content', self::PAGE_ID, [
            'tx_testdatahandler_checkbox_with_eval' => 1,
        ]);
        $newContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $newContentId);

        self::assertEquals(0, $newContentRecord['tx_testdatahandler_checkbox_with_eval']);
    }

    /**
     * @test
     */
    public function validMaximumRecordsCheckedInPidPermitsPersisting(): void
    {
        $actionService = new ActionService();
        $map = $actionService->createNewRecord('tt_content', self::PAGE_ID_OTHER, [
            'tx_testdatahandler_checkbox_with_eval' => 1,
        ]);
        $newContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $newContentId);

        self::assertEquals(1, $newContentRecord['tx_testdatahandler_checkbox_with_eval']);
    }

    /**
     * @test
     */
    public function violationOfMaximumRecordsCheckedInPidResetsValueToZero(): void
    {
        $actionService = new ActionService();
        $actionService->createNewRecord('tt_content', self::PAGE_ID_OTHER, [
            'tx_testdatahandler_checkbox_with_eval' => 1,
        ]);
        $map = $actionService->createNewRecord('tt_content', self::PAGE_ID_OTHER, [
            'tx_testdatahandler_checkbox_with_eval' => 1,
        ]);
        $newContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $newContentId);

        self::assertEquals(0, $newContentRecord['tx_testdatahandler_checkbox_with_eval']);
    }

    /**
     * @test
     */
    public function violationOfMaximumRecordsCheckedInWorkspaceResetsValueToZero(): void
    {
        $actionService = new ActionService();
        $this->backendUserAuthentication->workspace = 1;
        (new Context())->setAspect('workspace', new WorkspaceAspect(1));
        $map = $actionService->createNewRecord('tt_content', self::PAGE_ID, [
            'tx_testdatahandler_checkbox_with_eval' => 1,
        ]);
        $newContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $newContentId);

        self::assertEquals(0, $newContentRecord['tx_testdatahandler_checkbox_with_eval']);
    }

    /**
     * @test
     */
    public function violationOfMaximumRecordsCheckedInPidInWorkspaceResetsValueToZero(): void
    {
        $actionService = new ActionService();
        $this->backendUserAuthentication->workspace = 1;
        (new Context())->setAspect('workspace', new WorkspaceAspect(1));
        $map = $actionService->createNewRecord('tt_content', self::PAGE_ID_OTHER, [
            'tx_testdatahandler_checkbox_with_eval' => 1,
        ]);
        $newContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $newContentId);

        self::assertEquals(0, $newContentRecord['tx_testdatahandler_checkbox_with_eval']);
    }
}
