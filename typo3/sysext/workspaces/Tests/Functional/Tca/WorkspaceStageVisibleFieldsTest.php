<?php
namespace TYPO3\CMS\Workspaces\Tests\Unit\Tca;

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

use TYPO3\CMS\Backend\Tests\Functional\Form\FormTestService;
use TYPO3\CMS\Core\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

class WorkspaceStageVisibleFieldsTest extends FunctionalTestCase
{
    protected $coreExtensionsToLoad = ['workspaces'];

    protected static $workspaceStageFields = [
        'title',
        'responsible_persons',
        'allow_notificaton_settings',
        'notification_preselection',
        'notification_defaults',
        'default_mailcomment',
    ];

    /**
     * Sets up this test case.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->setUpBackendUserFromFixture(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);
    }

    /**
     * @test
     */
    public function workspaceStageFormContainsExpectedFields()
    {
        $formEngineTestService = GeneralUtility::makeInstance(FormTestService::class);
        $formResult = $formEngineTestService->createNewRecordForm('sys_workspace_stage');

        foreach (static::$workspaceStageFields as $expectedField) {
            $this->assertNotFalse(
                $formEngineTestService->formHtmlContainsField($expectedField, $formResult['html']),
                'The field ' . $expectedField . ' is not in the form HTML'
            );
        }
    }
}
