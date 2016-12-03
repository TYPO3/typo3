<?php
namespace TYPO3\CMS\SysAction\Tests\Functional\Tca;

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

class ActionVisibleFieldsTest extends FunctionalTestCase
{
    protected $coreExtensionsToLoad = ['sys_action'];

    protected static $actionCommonFields = [
        'type',
        'title',
        'description',
        'hidden',
        'assign_to_groups',
    ];

    protected static $actionFieldsByType = [
        '0' => [],
        '1' => [
            't1_userprefix',
            't1_copy_of_user',
            't1_allowed_groups',
            't1_create_user_dir',
        ],
        '2' => [],
        '3' => [
            't3_listPid',
            't3_tables',
        ],
        '4' => [
            't4_recordsToEdit',
        ],
        '5' => [
            't3_listPid',
            't3_tables',
        ],
    ];

    /**
     * @test
     */
    public function actionFormContainsExpectedFields()
    {
        $this->setUpBackendUserFromFixture(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $formEngineTestService = GeneralUtility::makeInstance(FormTestService::class);

        foreach (static::$actionFieldsByType as $type => $additionalFields) {
            $expectedFields = array_merge(static::$actionCommonFields, $additionalFields);
            $formResult = $formEngineTestService->createNewRecordForm('sys_action', ['type' => $type]);

            foreach ($expectedFields as $expectedField) {
                $this->assertNotFalse(
                    $formEngineTestService->formHtmlContainsField($expectedField, $formResult['html']),
                    'The field ' . $expectedField . ' is not in the form HTML'
                );
            }
        }
    }
}
