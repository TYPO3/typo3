<?php
namespace TYPO3\CMS\Core\Tests\Unit\Tca;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

class BackendUsersVisibleFieldsTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    protected static $backendUserFields = [
        'disable',
        'username',
        'password',
        'description',
        'avatar',
        'usergroup',
        'admin',
        'realName',
        'email',
        'lang',
        'userMods',
        'allowed_languages',
        'workspace_perms',
        'db_mountpoints',
        'options',
        'file_mountpoints',
        'file_permissions',
        'category_perms',
        'lockToDomain',
        'disableIPlock',
        'TSconfig',
        'starttime',
        'endtime',
    ];

    protected static $adminHiddenFields = [
        'userMods',
        'allowed_languages',
        'workspace_perms',
        'file_permissions',
        'category_perms',
        'lockToDomain',
    ];

    /**
     * @test
     */
    public function backendUsersFormContainsExpectedFields()
    {
        $this->setUpBackendUserFromFixture(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $formEngineTestService = GeneralUtility::makeInstance(FormTestService::class);
        $formResult = $formEngineTestService->createNewRecordForm('be_users');

        foreach (static::$backendUserFields as $expectedField) {
            $this->assertNotFalse(
                strpos($formResult['html'], '[' . $expectedField . ']'),
                'The field ' . $expectedField . ' is not in the HTML'
            );
        }

        $this->assertNotFalse(
            strpos($formResult['html'], 'Last login'),
            'The field lastlogin is not in the HTML'
        );
    }

    /**
     * @test
     */
    public function backendUsersFormContainsExpectedFieldsForAdmins()
    {
        $this->setUpBackendUserFromFixture(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $formEngineTestService = GeneralUtility::makeInstance(FormTestService::class);
        $formResult = $formEngineTestService->createNewRecordForm('be_users', ['admin' => true]);

        $expectedFields = array_diff(static::$backendUserFields, static::$adminHiddenFields);

        foreach ($expectedFields as $expectedField) {
            $this->assertNotFalse(
                $formEngineTestService->formHtmlContainsField($expectedField, $formResult['html']),
                'The field ' . $expectedField . ' is not in the HTML'
            );
        }

        foreach (static::$adminHiddenFields as $hiddenField) {
            $this->assertFalse(
                $formEngineTestService->formHtmlContainsField($hiddenField, $formResult['html']),
                'The field ' . $hiddenField . ' is in the HTML'
            );
        }

        $this->assertNotFalse(
            strpos($formResult['html'], 'Last login'),
            'The field lastlogin is not in the HTML'
        );
    }
}
