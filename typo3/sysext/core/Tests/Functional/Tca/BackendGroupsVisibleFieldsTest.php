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

class BackendGroupsVisibleFieldsTest extends FunctionalTestCase
{
    protected static $backendGroupsFields = [
        'hidden',
        'title',
        'description',
        'subgroup',
        'groupMods',
        'tables_select',
        'tables_modify',
        'pagetypes_select',
        'non_exclude_fields',
        'explicit_allowdeny',
        'allowed_languages',
        'db_mountpoints',
        'file_mountpoints',
        'file_permissions',
        'category_perms',
        'lockToDomain',
        'TSconfig',
    ];

    /**
     * @test
     */
    public function backendGroupsFormContainsExpectedFields()
    {
        $this->setUpBackendUserFromFixture(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $formEngineTestService = GeneralUtility::makeInstance(FormTestService::class);
        $formResult = $formEngineTestService->createNewRecordForm('be_groups');

        foreach (static::$backendGroupsFields as $expectedField) {
            self::assertNotFalse(
                $formEngineTestService->formHtmlContainsField($expectedField, $formResult['html']),
                'The field ' . $expectedField . ' is not in the HTML'
            );
        }
    }
}
