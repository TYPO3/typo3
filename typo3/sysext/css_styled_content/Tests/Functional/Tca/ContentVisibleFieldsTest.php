<?php
namespace TYPO3\CMS\CssStyledContent\Tests\Functional\Tca;

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

class ContentVisibleFieldsTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['css_styled_content'];

    /**
     * @var array
     */
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

    /**
     * @var array
     */
    protected static $headerFields = [
        'header',
        'header_layout',
        'header_position',
        'date',
        'header_link',
    ];

    /**
     * @var array
     */
    protected static $imageFields = [
        'image',
        'image_zoom',
        'imagewidth',
        'imageheight',
        'imageborder',
        'imageorient',
        'imagecols',
    ];

    /**
     * @var array
     */
    protected static $contentFieldsByType = [
        'header' => [
            'additionalFields' => ['subheader'],
        ],
        'text' => [
            'additionalFields' => ['bodytext'],
        ],
        'textpic' => [
            'additionalFields' => ['bodytext'],
        ],
        'image' => [
            'useImageFields' => true,
        ],
        'bullets' => [
            'additionalFields' => ['bodytext'],
        ],
        'table' => [
            'additionalFields' => [
                'cols',
                'bodytext',
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
                $this->assertNotFalse(
                    $formEngineTestService->formHtmlContainsField($expectedField, $formResult['html']),
                    'The field ' . $expectedField . ' is not in the HTML'
                );
            }
        }
    }
}
