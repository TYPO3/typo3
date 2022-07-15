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

namespace TYPO3\CMS\Workspaces\Tests\Functional\EventListener;

use TYPO3\CMS\Backend\Controller\Event\AfterPageTreeItemsPreparedEvent;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\EventListener\PageTreeItemsHighlighter;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PageTreeItemsHighlighterTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        Bootstrap::initializeLanguageObject();
    }

    /**
     * @test
     */
    public function classesAreAppliedToPageItems(): void
    {
        $this->setWorkspaceId(91);
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tt_content.csv');

        $input = [
            // root
            0 => [
                'stateIdentifier' => '0_0',
                'identifier' => '0',
                '_page' => [
                    'uid' => 0,
                    'pid' => 0,
                    't3ver_oid' => 0,
                    't3ver_state' => 0,
                    't3ver_wsid' => 0,
                ],
            ],
            // Page contains version records
            1 => [
                'stateIdentifier' => '0_1',
                'identifier' => '1',
                '_page' => [
                    'uid' => 1,
                    'pid' => 0,
                    't3ver_oid' => 0,
                    't3ver_state' => 0,
                    't3ver_wsid' => 0,
                ],
            ],
            // Standard page without versions
            2 => [
                'stateIdentifier' => '0_2',
                'identifier' => '2',
                '_page' => [
                    'uid' => 2,
                    'pid' => 1,
                    't3ver_oid' => 0,
                    't3ver_state' => 0,
                    't3ver_wsid' => 0,
                ],
            ],
            // Page missing the page record array
            3 => [
                'stateIdentifier' => '0_3',
                'identifier' => '3',
            ],
            // Versioned page
            4 => [
                'stateIdentifier' => '0_102',
                'identifier' => '102',
                '_page' => [
                    'uid' => 102,
                    'pid' => 1,
                    't3ver_oid' => 2,
                    't3ver_state' => 0,
                    't3ver_wsid' => 91,
                ],
            ],
            // Versioned page in different workspace
            5 => [
                'stateIdentifier' => '0_202',
                'identifier' => '202',
                '_page' => [
                    'uid' => 202,
                    'pid' => 1,
                    't3ver_oid' => 2,
                    't3ver_state' => 0,
                    't3ver_wsid' => 92,
                ],
            ],
            // new placeholder
            6 => [
                'stateIdentifier' => '0_103',
                'identifier' => '103',
                '_page' => [
                    'uid' => 103,
                    'pid' => 2,
                    't3ver_oid' => 0,
                    't3ver_state' => 1,
                    't3ver_wsid' => 91,
                ],
            ],
        ];

        $afterPageTreeItemsPreparedEvent = new AfterPageTreeItemsPreparedEvent(
            new ServerRequest(new Uri('https://example.com')),
            $input
        );

        (new PageTreeItemsHighlighter(new WorkspaceService()))($afterPageTreeItemsPreparedEvent);

        $expected = $input;
        $expected[1]['class'] = 'ver-versions';
        $expected[4]['class'] = 'ver-element ver-versions';
        $expected[6]['class'] = 'ver-element ver-versions';

        self::assertEquals($expected, $afterPageTreeItemsPreparedEvent->getItems());
    }

    protected function setWorkspaceId(int $workspaceId): void
    {
        $GLOBALS['BE_USER']->workspace = $workspaceId;
        GeneralUtility::makeInstance(Context::class)->setAspect('workspace', new WorkspaceAspect($workspaceId));
    }
}
