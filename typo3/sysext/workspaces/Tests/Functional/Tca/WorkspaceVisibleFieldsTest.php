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

class WorkspaceVisibleFieldsTest extends FunctionalTestCase
{
    protected $coreExtensionsToLoad = ['workspaces'];

    protected static $workspaceFields = [
        'title',
        'description',
        'adminusers',
        'members',
        'stagechg_notification',
        'edit_allow_notificaton_settings',
        'edit_notification_preselection',
        'edit_notification_defaults',
        'publish_allow_notificaton_settings',
        'publish_notification_preselection',
        'publish_notification_defaults',
        'execute_allow_notificaton_settings',
        'execute_notification_preselection',
        'execute_notification_defaults',
        'db_mountpoints',
        'file_mountpoints',
        'publish_time',
        'custom_stages',
        'freeze',
        'live_edit',
        'swap_modes',
        'publish_access',
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

        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/workspaces/Tests/Functional/Fixtures/sys_filemounts.xml');
    }

    /**
     * @test
     */
    public function workspaceFormContainsExpectedFields()
    {
        $formEngineTestService = GeneralUtility::makeInstance(FormTestService::class);
        $formResult = $formEngineTestService->createNewRecordForm('sys_workspace');

        foreach (static::$workspaceFields as $expectedField) {
            $this->assertNotFalse(
                $formEngineTestService->formHtmlContainsField($expectedField, $formResult['html']),
                'The field ' . $expectedField . ' is not in the form HTML'
            );
        }
    }
}
