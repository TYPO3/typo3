<?php

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

namespace TYPO3\CMS\Core\Tests\Functional\Tca;

use TYPO3\CMS\Backend\Tests\Functional\Form\FormTestService;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BackendUsersVisibleFieldsTest extends FunctionalTestCase
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
        'db_mountpoints',
        'options',
        'file_mountpoints',
        'file_permissions',
        'category_perms',
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
    ];

    protected static $specialFields = [
        'mfa' => 'Multi-factor authentication',
        'lastlogin' => 'Last login'
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
            self::assertNotFalse(
                $formEngineTestService->formHtmlContainsField($expectedField, $formResult['html']),
                'The field ' . $expectedField . ' is not in the HTML'
            );
        }

        foreach (static::$specialFields as $fieldName => $searchString) {
            self::assertNotFalse(
                strpos($formResult['html'], $searchString),
                'The field ' . $fieldName . ' is not in the HTML'
            );
        }
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
            self::assertNotFalse(
                $formEngineTestService->formHtmlContainsField($expectedField, $formResult['html']),
                'The field ' . $expectedField . ' is not in the HTML'
            );
        }

        foreach (static::$adminHiddenFields as $hiddenField) {
            self::assertFalse(
                $formEngineTestService->formHtmlContainsField($hiddenField, $formResult['html']),
                'The field ' . $hiddenField . ' is in the HTML'
            );
        }

        foreach (static::$specialFields as $fieldName => $searchString) {
            self::assertNotFalse(
                strpos($formResult['html'], $searchString),
                'The field ' . $fieldName . ' is not in the HTML'
            );
        }
    }
}
