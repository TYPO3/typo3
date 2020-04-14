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

class FileCollectionVisibleFieldsTest extends FunctionalTestCase
{
    protected static $fileCollectionFields = [
        'title',
        'sys_language_uid',
        'type',
        'starttime',
        'endtime',
    ];

    protected static $fileCollectionTypeFields = [
        '0' => ['files'],
        'category' => ['category'],
        'folder' => [
            'storage',
            'folder',
            'recursive',
        ],
        'static' => ['files'],
    ];

    /**
     * @test
     */
    public function fileCollectionFormContainsExpectedFields()
    {
        $this->setUpBackendUserFromFixture(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $formEngineTestService = GeneralUtility::makeInstance(FormTestService::class);

        foreach (static::$fileCollectionTypeFields as $type => $additionalFields) {
            $formResult = $formEngineTestService->createNewRecordForm('sys_file_collection', ['type' => $type]);
            $expectedFields = array_merge(static::$fileCollectionFields, $additionalFields);
            foreach ($expectedFields as $expectedField) {
                self::assertNotFalse(
                    $formEngineTestService->formHtmlContainsField($expectedField, $formResult['html']),
                    'The field ' . $expectedField . ' is not in the form HTML'
                );
            }
        }
    }
}
