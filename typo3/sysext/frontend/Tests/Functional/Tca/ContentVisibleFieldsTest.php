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

namespace TYPO3\CMS\Frontend\Tests\Functional\Tca;

use TYPO3\CMS\Backend\Tests\Functional\Form\FormTestService;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ContentVisibleFieldsTest extends FunctionalTestCase
{
    protected static $commonContentFields = [
        'CType',
        'colPos',
        'sys_language_uid',
        'layout',
        'hidden',
        'sectionIndex',
        'linkToTop',
        'starttime',
        'endtime',
        'fe_group',
        'editlock',
        'rowDescription',
        'categories',
    ];

    protected static $headerFields = [
        'header',
        'header_layout',
        'date',
        'header_link',
    ];

    protected static $imageFields = [
        'image',
        'image_zoom',
        'imagewidth',
        'imageheight',
        'imageborder',
        'imageorient',
        'imagecols',
    ];

    protected static $contentFieldsByType = [
        'header' => [
            'additionalFields' => ['subheader'],
        ],
        'bullets' => [
            'additionalFields' => ['bodytext'],
        ],
        'table' => [
            'additionalFields' => [
                'bodytext',
                'table_delimiter',
                'table_enclosure',
                'table_caption',
                'table_caption',
                'cols',
                'table_header_position',
                'table_tfoot',
            ],
        ],
        'uploads' => [
            'additionalFields' => [
                'media',
                'file_collections',
                'filelink_sorting',
                'target',
            ],
        ],
        'shortcut' => [
            'additionalFields' => [
                'header',
                'records'
            ],
            'disableHeaderFields' => true,
        ],
        'list' => [
            'additionalFields' => [
                'list_type',
                'pages',
                'recursive',
            ],
        ],
        'div' => [
            'additionalFields' => [
                'header',
            ],
            'disableHeaderFields' => true,
        ],
        'html' => [
            'additionalFields' => [
                'bodytext',
            ],
            'disableHeaderFields' => true,
        ],
    ];

    /**
     * @test
     */
    public function contentFormContainsExpectedFields()
    {
        $this->setUpBackendUserFromFixture(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $formEngineTestService = GeneralUtility::makeInstance(FormTestService::class);

        foreach (static::$contentFieldsByType as $contentType => $fieldConfig) {
            $expectedFields = static::$commonContentFields;

            if (empty($fieldConfig['disableHeaderFields'])) {
                $expectedFields = array_merge($expectedFields, static::$headerFields);
            }

            if (!empty($fieldConfig['useImageFields'])) {
                $expectedFields = array_merge($expectedFields, static::$imageFields);
            }

            if (!empty($fieldConfig['additionalFields'])) {
                $expectedFields = array_merge($expectedFields, $fieldConfig['additionalFields']);
            }

            $formResult = $formEngineTestService->createNewRecordForm('tt_content', ['CType' => $contentType]);
            foreach ($expectedFields as $expectedField) {
                self::assertNotFalse(
                    $formEngineTestService->formHtmlContainsField($expectedField, $formResult['html']),
                    'The field ' . $expectedField . ' is not in the form HTML'
                );
            }
        }
    }
}
