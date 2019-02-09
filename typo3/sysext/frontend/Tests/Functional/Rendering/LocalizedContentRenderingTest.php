<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Frontend\Tests\Functional\Rendering;

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

use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

/**
 * Test case checking if localized tt_content is rendered correctly with different language settings
 *
 * The following values are relevant:
 *
 * -- TypoScript --
 * config.sys_language_uid = [0,1,2,3,4...] (set via the language parameter &L=1 from the FrontendRequest in the tests)
 *      Fetch the page overlay of the current page if the value is > 0 and if not available, then
 *      "config.sys_language_mode" is evaluated.
 *      If this setting is set to "0" or empty, then no page overlay is evaluated, and no further parameters are
 *      relevant or evaluated.
 *
 * config.sys_language_mode = [strict, content_fallback;2,3, ignore]
 *      Only evaluated when sys_language_uid > 0, and the requested page translation is NOT available.
 *      Decides if "pageNotFound" (strict), "content_fallback" with a fallback chain ($TSFE->sys_language_content is set
 *      to that value) or "ignore" (just render the page and the content as this translation would exist).
 *      When set to "0" or not set "", this means that the page request is using the default language for content
 *      and page properties.
 *      Content fallback is evaluated on page level, not on the CE level. So it only makes a difference when the page translation
 *      for the requested language does not exist.
 *
 * config.sys_language_overlay = [0, 1, hideNonTranslated]
 *      Only relevant if $TSFE->sys_language_content is > 0.
 *      Sets the property $TSFE->sys_language_contentOL at a request. Further calls via $TSFE->sys_page->getRecordOverlay
 *      receive this value to see if an overlay should happen.
 *      0:
 *          Just fetch records from selected ($TSFE->sys_language_content) language, no overlay will happen,
 *          no fetching of the records from the default language. This boils down to "free mode" language handling.
 *
 *      1:
 *          Fetch records from the default language and overlay them with translations. If some record is not translated
 *          default language version will be shown.
 *
 *      hideNotTranslated:
 *          Fetch records from the default language and overlay them with translations. If some record is not translated
 *          it will not be shown.
 *
 * -- Frontend / TypoScriptFrontendController --
 *
 * $TSFE->sys_language_uid
 *      Defines in which language the current page was requested, this is relevant when building menus or links to other
 *      pages.
 * $TSFE->sys_language_content
 *      Contains the language UID of the content records that should be overlaid to would be fetched.
 *      This is especially useful when a page requested with language=4 should fall back to showing content of language=2 (see config.sys_language_mode=content_fallback)
 * $TSFE->sys_language_contentOL
 *      Contains the info if and how record overlays (when fetching content) should be handled, either "0" (no overlays done)
 *      or "1" (do overlays with possible mixed content, or "hideNonTranslated". see "config.sys_language_overlay"
 *      This is used in conjunction with $TSFE->sys_language_content.
 * $TSFE->sys_language_mode
 *      Contains the config.sys_language_mode parameter, which is either "", "strict", "content_fallback" or "ignore"
 *      Only used within $TSFE->settingLanguage() and in Extbase.
 */
class LocalizedContentRenderingTest extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase
{
    const VALUE_PageId = 89;
    const TABLE_Content = 'tt_content';
    const TABLE_Pages = 'pages';

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/frontend/Tests/Functional/Rendering/DataSet/';

    /**
     * Custom 404 handler returning valid json is registered so the $this->getFrontendResponse()
     * does not fail on 404 pages
     *
     * @var array
     */
    protected $configurationToUseInTestInstance = [
        'FE' => [
            'pageNotFound_handling' => 'READFILE:typo3/sysext/frontend/Tests/Functional/Rendering/DataSet/404Template.html'
        ]
    ];

    /**
     * @var array
     */
    protected $pathsToLinkInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/AdditionalConfiguration.php' => 'typo3conf/AdditionalConfiguration.php',
        'typo3/sysext/frontend/Tests/Functional/Fixtures/Images' => 'fileadmin/user_upload'
    ];

    protected function setUp()
    {
        parent::setUp();
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_file_storage.xml');
        $this->importScenarioDataSet('LiveDefaultPages');
        $this->importScenarioDataSet('LiveDefaultElements');
        $this->setUpFrontendRootPage(1, [
            'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript',
        ]);
    }

    public function defaultLanguageConfigurationDataProvider(): array
    {
        return [
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode =',
                'sys_language_mode' => '',
                'sys_language_contentOL' => 0
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = content_fallback',
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 0
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = content_fallback;1,0',
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 0
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = strict',
                'sys_language_mode' => 'strict',
                'sys_language_contentOL' => 0
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = ignore',
                'sys_language_mode' => 'ignore',
                'sys_language_contentOL' => 0
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode =',
                'sys_language_mode' => '',
                'sys_language_contentOL' => 1
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = content_fallback',
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 1
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = content_fallback;1,0',
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 1
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = strict',
                'sys_language_mode' => 'strict',
                'sys_language_contentOL' => 1
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = ignore',
                'sys_language_mode' => 'ignore',
                'sys_language_contentOL' => 1
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode =',
                'sys_language_mode' => '',
                'sys_language_contentOL' => 'hideNonTranslated'
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = content_fallback',
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 'hideNonTranslated'
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = content_fallback;1,0',
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 'hideNonTranslated'
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = strict',
                'sys_language_mode' => 'strict',
                'sys_language_contentOL' => 'hideNonTranslated'
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = ignore',
                'sys_language_mode' => 'ignore',
                'sys_language_contentOL' => 'hideNonTranslated'
            ],
        ];
    }

    /**
     * For the default language all combination of language settings should give the same result,
     * regardless of TypoScript settings, if the requested language is "0" then no TypoScript settings apply.
     *
     * @test
     * @dataProvider defaultLanguageConfigurationDataProvider
     *
     * @param string $typoScript
     * @param string $sysLanguageMode
     * @param string $sysLanguageContentOL
     */
    public function onlyEnglishContentIsRenderedForDefaultLanguage(string $typoScript, string $sysLanguageMode, string $sysLanguageContentOL)
    {
        $this->addTypoScriptToTemplateRecord(1, $typoScript);

        $frontendResponse = $this->getFrontendResponse(self::VALUE_PageId, 0);
        $responseSections = $frontendResponse->getResponseSections();
        $visibleHeaders = ['Regular Element #1', 'Regular Element #2', 'Regular Element #3'];
        $this->assertThat(
            $responseSections,
            $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)
            ->setField('header')
            ->setValues(...$visibleHeaders)
        );
        $this->assertThat(
            $responseSections,
            $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)
            ->setField('header')
            ->setValues(...$this->getNonVisibleHeaders($visibleHeaders))
        );

        //assert FAL relations
        $visibleFiles = ['T3BOARD'];
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':297')->setRecordField('image')
            ->setTable('sys_file_reference')->setField('title')->setValues(...$visibleFiles));

        $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':297')->setRecordField('image')
            ->setTable('sys_file_reference')->setField('title')->setValues(...$this->getNonVisibleFileTitles($visibleFiles)));

        $visibleFiles = ['Kasper2'];
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':298')->setRecordField('image')
            ->setTable('sys_file_reference')->setField('title')->setValues(...$visibleFiles));

        $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':298')->setRecordField('image')
            ->setTable('sys_file_reference')->setField('title')->setValues(...$this->getNonVisibleFileTitles($visibleFiles)));

        //Assert TSFE and page record title
        $content = json_decode($frontendResponse->getContent());
        $this->assertEquals('Default language Page', $content->Scope->page->title);
        $this->assertEquals(0, $content->Scope->tsfe->sys_language_uid, 'sys_language_uid doesn\'t match');
        $this->assertEquals(0, $content->Scope->tsfe->sys_language_content, 'sys_language_content doesn\'t match');
        $this->assertEquals($sysLanguageMode, $content->Scope->tsfe->sys_language_mode, 'sys_language_mode doesn\t match');
        $this->assertEquals($sysLanguageContentOL, $content->Scope->tsfe->sys_language_contentOL, 'sys_language_contentOL doesn\t match');
    }

    /**
     * Dutch language has page translation record and some content elements are translated
     *
     * @return array
     */
    public function dutchDataProvider(): array
    {
        //Expected behaviour:
        //Page is translated to Dutch, so changing sys_language_mode does NOT change the results
        //Page title is always [DK]Page, and both sys_language_content and sys_language_uid are always 1
        return [
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode =',
                'visibleRecords' => [
                    300 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                    301 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language']
                    ],
                    308 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => []
                    ],
                ],
                'sys_language_mode' => '',
                'sys_language_contentOL' => 0
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = content_fallback',
                'visibleRecords' => [
                    300 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                    301 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language']
                    ],
                    308 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => []
                    ],
                ],
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 0
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = content_fallback;1,0',
                'visibleRecords' => [
                    300 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                    301 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language']
                    ],
                    308 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => []
                    ],
                ],
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 0
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = strict',
                'visibleRecords' => [
                    300 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                    301 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language']
                    ],
                    308 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => []
                    ],
                ],
                'sys_language_mode' => 'strict',
                'sys_language_contentOL' => 0
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = ignore',
                'visibleRecords' => [
                    300 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                    301 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language']
                    ],
                    308 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => []
                    ],
                ],
                'sys_language_mode' => 'ignore',
                'sys_language_contentOL' => 0
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode =',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                ],
                'sys_language_mode' => '',
                'sys_language_contentOL' => 1
            ],
            // Expected behaviour:
            // Not translated element #2 is shown because sys_language_overlay = 1 (with sys_language_overlay = hideNonTranslated, it would be hidden)
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = content_fallback',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                ],
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 1
            ],
            // Expected behaviour:
            // Same as config.sys_language_mode = content_fallback because we're requesting language 1, so no additional fallback possible
            //
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = content_fallback;1,0',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                ],
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 1
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = strict',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                ],
                'sys_language_mode' => 'strict',
                'sys_language_contentOL' => 1
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = ignore',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                ],
                'sys_language_mode' => 'ignore',
                'sys_language_contentOL' => 1
            ],
            // Expected behaviour:
            // Non translated default language elements are not shown, because of hideNonTranslated
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode =',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                ],
                'sys_language_mode' => '',
                'sys_language_contentOL' => 'hideNonTranslated'
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = content_fallback',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                ],
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 'hideNonTranslated'
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = content_fallback;1,0',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                ],
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 'hideNonTranslated'
            ],
            //Setting sys_language_mode = strict has the same effect as previous data sets, because the translation of the page exists
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = strict',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                ],
                'sys_language_mode' => 'strict',
                'sys_language_contentOL' => 'hideNonTranslated'
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = ignore',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                ],
                'sys_language_mode' => 'ignore',
                'sys_language_contentOL' => 'hideNonTranslated'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dutchDataProvider
     *
     * @param string $typoScript
     * @param array $visibleRecords
     * @param string $sysLanguageMode
     * @param string $sysLanguageContentOL
     */
    public function renderingOfDutchLanguage(string $typoScript, array $visibleRecords, string $sysLanguageMode, string $sysLanguageContentOL)
    {
        $this->addTypoScriptToTemplateRecord(1, $typoScript);
        $frontendResponse = $this->getFrontendResponse(self::VALUE_PageId, 1);
        $responseSections = $frontendResponse->getResponseSections();
        $visibleHeaders = array_map(function ($element) {
            return $element['header'];
        }, $visibleRecords);

        $this->assertThat(
            $responseSections,
            $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)
            ->setField('header')
            ->setValues(...$visibleHeaders)
        );
        $this->assertThat(
            $responseSections,
            $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)
            ->setField('header')
            ->setValues(...$this->getNonVisibleHeaders($visibleHeaders))
        );

        foreach ($visibleRecords as $ttContentUid => $properties) {
            $visibleFileTitles = $properties['image'];
            if (!empty($visibleFileTitles)) {
                $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
                    ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('image')
                    ->setTable('sys_file_reference')->setField('title')->setValues(...$visibleFileTitles));
            }
            $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
                ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('image')
                ->setTable('sys_file_reference')->setField('title')->setValues(...$this->getNonVisibleFileTitles($visibleFileTitles)));
        }

        $content = json_decode($frontendResponse->getContent());
        $this->assertEquals('[DK]Page', $content->Scope->page->title);
        $this->assertEquals(1, $content->Scope->tsfe->sys_language_uid, 'sys_language_uid doesn\'t match');
        $this->assertEquals(1, $content->Scope->tsfe->sys_language_content, 'sys_language_content doesn\'t match');
        $this->assertEquals($sysLanguageMode, $content->Scope->tsfe->sys_language_mode, 'sys_language_mode doesn\t match');
        $this->assertEquals($sysLanguageContentOL, $content->Scope->tsfe->sys_language_contentOL, 'sys_language_contentOL doesn\t match');
    }

    public function contentOnNonTranslatedPageDataProvider(): array
    {
        //Expected behaviour:
        //the page is NOT translated so setting sys_language_mode to different values changes the results
        //- setting sys_language_mode to empty value makes TYPO3 return default language records
        //- setting it to strict throws 404, independently from other settings
        //Setting config.sys_language_overlay = 0
        return [
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode =',
                'visibleRecords' => [
                    297 => [
                        'header' => 'Regular Element #1',
                        'image' => ['T3BOARD'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => ['Kasper'],
                    ],
                ],
                'pageTitle' => 'Default language Page',
                'sys_language_uid' => 0,
                'sys_language_content' => 0,
                'sys_language_mode' => '',
                'sys_language_contentOL' => 0,
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = content_fallback',
                'visibleRecords' => [
                    297 => [
                        'header' => 'Regular Element #1',
                        'image' => ['T3BOARD'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => ['Kasper'],
                    ],
                ],
                'pageTitle' => 'Default language Page',
                'sys_language_uid' => 2,
                'sys_language_content' => 0,
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 0,
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = content_fallback;1,0',
                'visibleRecords' => [
                    300 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                    301 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                    308 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => [],
                    ],
                ],
                'pageTitle' => '[DK]Page',
                'sys_language_uid' => 2,
                'sys_language_content' => 1,
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 0,
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = strict',
                'visibleRecords' => [],
                'pageTitle' => '',
                'sys_language_uid' => 2,
                'sys_language_content' => 2,
                'sys_language_mode' => 'strict',
                'sys_language_contentOL' => 0,
                'statusCode' => 404,
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = ignore',
                'visibleRecords' => [
                    302 => [
                        'header' => '[Translate to Deutsch:] [Translate to Dansk:] Regular Element #1',
                        'image' => ['[T3BOARD] image translated to DE from DK'],
                    ],
                    304 => [
                        'header' => '[DE] Without default language',
                        'image' => [],
                    ],
                ],
                'pageTitle' => 'Default language Page',
                'sys_language_uid' => 2,
                'sys_language_content' => 2,
                'sys_language_mode' => 'ignore',
                'sys_language_contentOL' => 0,
            ],
            5 => [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode =',
                'visibleRecords' => [
                    297 => [
                        'header' => 'Regular Element #1',
                        'image' => ['T3BOARD'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => ['Kasper'],
                    ],
                ],
                'pageTitle' => 'Default language Page',
                'sys_language_uid' => 0,
                'sys_language_content' => 0,
                'sys_language_mode' => '',
                'sys_language_contentOL' => 1,
            ],
            //falling back to default language
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = content_fallback',
                'visibleRecords' => [
                    297 => [
                        'header' => 'Regular Element #1',
                        'image' => ['T3BOARD'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => ['Kasper'],
                    ],
                ],
                'pageTitle' => 'Default language Page',
                'sys_language_uid' => 2,
                'sys_language_content' => 0,
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 1,
            ],
            //Dutch elements are shown because of the content fallback 1,0 - first Dutch, then default language
            //note that '[DK] Without default language' is NOT shown - due to overlays (fetch default language and overlay it with translations)
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = content_fallback;1,0',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                ],
                'pageTitle' => '[DK]Page',
                'sys_language_uid' => 2,
                'sys_language_content' => 1,
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 1,
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = strict',
                'visibleRecords' => [],
                'pageTitle' => '',
                'sys_language_uid' => 2,
                'sys_language_content' => 2,
                'sys_language_mode' => 'strict',
                'sys_language_contentOL' => 1,
                'statusCode' => 404
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = ignore',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Deutsch:] [Translate to Dansk:] Regular Element #1',
                        'image' => ['[T3BOARD] image translated to DE from DK'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => ['Kasper'],
                    ],
                ],
                'pageTitle' => 'Default language Page',
                'sys_language_uid' => 2,
                'sys_language_content' => 2,
                'sys_language_mode' => 'ignore',
                'sys_language_contentOL' => 1,
            ],
            10 => [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode =',
                'visibleRecords' => [
                    297 => [
                        'header' => 'Regular Element #1',
                        'image' => ['T3BOARD'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => ['Kasper'],
                    ],
                ],
                'pageTitle' => 'Default language Page',
                'sys_language_uid' => 0,
                'sys_language_content' => 0,
                'sys_language_mode' => '',
                'sys_language_contentOL' => 'hideNonTranslated',
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = content_fallback',
                'visibleRecords' => [
                    297 => [
                        'header' => 'Regular Element #1',
                        'image' => ['T3BOARD'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => ['Kasper'],
                    ],
                ],
                'pageTitle' => 'Default language Page',
                'sys_language_uid' => 2,
                'sys_language_content' => 0,
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 'hideNonTranslated',
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = content_fallback;1,0',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                ],
                'pageTitle' => '[DK]Page',
                'sys_language_uid' => 2,
                'sys_language_content' => 1,
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 'hideNonTranslated',
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = strict',
                'visibleRecords' => [],
                'pageTitle' => '',
                'sys_language_uid' => 2,
                'sys_language_content' => 2,
                'sys_language_mode' => 'strict',
                'sys_language_contentOL' => 'hideNonTranslated',
                'statusCode' => 404,
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = ignore',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Deutsch:] [Translate to Dansk:] Regular Element #1',
                        'image' => ['[T3BOARD] image translated to DE from DK'],
                    ],
                ],
                'pageTitle' => 'Default language Page',
                'sys_language_uid' => 2,
                'sys_language_content' => 2,
                'sys_language_mode' => 'ignore',
                'sys_language_contentOL' => 'hideNonTranslated',
            ],
        ];
    }

    /**
     * Page uid 89 is NOT translated to german
     *
     * @test
     * @dataProvider contentOnNonTranslatedPageDataProvider
     *
     * @param string $typoScript
     * @param array $visibleRecords
     * @param string $pageTitle
     * @param int $sysLanguageUid
     * @param int $sysLanguageContent
     * @param string $sysLanguageMode
     * @param string $sysLanguageContentOL
     * @param string $statusCode 200 or 404
     */
    public function contentOnNonTranslatedPageGerman(string $typoScript, array $visibleRecords, string $pageTitle, int $sysLanguageUid, int $sysLanguageContent, string $sysLanguageMode, string $sysLanguageContentOL, int $statusCode = 200)
    {
        $this->addTypoScriptToTemplateRecord(1, $typoScript);
        $visibleHeaders = array_column($visibleRecords, 'header');

        $response = $this->executeFrontendRequest(
            (new InternalRequest())
                ->withPageId(self::VALUE_PageId)
                ->withLanguageId(2)
        );

        if ($statusCode === 200) {
            $responseStructure = ResponseContent::fromString((string)$response->getBody());
            $responseSections = $responseStructure->getSections();

            $this->assertThat(
                $responseSections,
                $this->getRequestSectionHasRecordConstraint()
                ->setTable(self::TABLE_Content)
                ->setField('header')
                ->setValues(...$visibleHeaders)
            );
            $this->assertThat(
                $responseSections,
                $this->getRequestSectionDoesNotHaveRecordConstraint()
                ->setTable(self::TABLE_Content)
                ->setField('header')
                ->setValues(...$this->getNonVisibleHeaders($visibleHeaders))
            );

            foreach ($visibleRecords as $ttContentUid => $properties) {
                $visibleFileTitles = $properties['image'];
                if (!empty($visibleFileTitles)) {
                    $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
                        ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('image')
                        ->setTable('sys_file_reference')->setField('title')->setValues(...$visibleFileTitles));
                }
                $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
                    ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('image')
                    ->setTable('sys_file_reference')->setField('title')->setValues(...$this->getNonVisibleFileTitles($visibleFileTitles)));
            }

            $this->assertEquals($pageTitle, $responseStructure->getScopePath('page/title'));
            $this->assertEquals($sysLanguageUid, $responseStructure->getScopePath('tsfe/sys_language_uid'), 'sys_language_uid doesn\'t match');
            $this->assertEquals($sysLanguageContent, $responseStructure->getScopePath('tsfe/sys_language_content'), 'sys_language_content doesn\'t match');
            $this->assertEquals($sysLanguageMode, $responseStructure->getScopePath('tsfe/sys_language_mode'), 'sys_language_mode doesn\t match');
            $this->assertEquals($sysLanguageContentOL, $responseStructure->getScopePath('tsfe/sys_language_contentOL'), 'sys_language_contentOL doesn\t match');
        }

        $this->assertEquals($statusCode, $response->getStatusCode());
    }

    public function contentOnPartiallyTranslatedPageDataProvider(): array
    {

        //Expected behaviour:
        //Setting sys_language_mode to different values doesn't influence the result as the requested page is translated to Polish,
        //Page title is always [PL]Page, and both sys_language_content and sys_language_uid are always 3
        return [
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode =',
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
                'sys_language_mode' => '',
                'sys_language_contentOL' => 0
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = content_fallback',
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 0
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = content_fallback;1,0',
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 0
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = strict',
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
                'sys_language_mode' => 'strict',
                'sys_language_contentOL' => 0
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = ignore',
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
                'sys_language_mode' => 'ignore',
                'sys_language_contentOL' => 0
            ],
            5 => [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode =',
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', 'Regular Element #2', 'Regular Element #3'],
                'sys_language_mode' => '',
                'sys_language_contentOL' => 1
            ],
            // Expected behaviour:
            // Not translated element #2 is shown because sys_language_overlay = 1 (with sys_language_overlay = hideNonTranslated, it would be hidden)
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = content_fallback',
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', 'Regular Element #2', 'Regular Element #3'],
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 1
            ],
            // Expected behaviour:
            // Element #3 is not translated in PL and it is translated in DK. It's not shown as content_fallback is not related to single CE level
            // but on page level - and this page is translated to Polish, so no fallback is happening
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = content_fallback;1,0',
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', 'Regular Element #2', 'Regular Element #3'],
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 1
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = strict',
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', 'Regular Element #2', 'Regular Element #3'],
                'sys_language_mode' => 'strict',
                'sys_language_contentOL' => 1
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = ignore',
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', 'Regular Element #2', 'Regular Element #3'],
                'sys_language_mode' => 'ignore',
                'sys_language_contentOL' => 1
            ],
            // Expected behaviour:
            // Non translated default language elements are not shown, because of hideNonTranslated
            10 => [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode =',
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1'],
                'sys_language_mode' => '',
                'sys_language_contentOL' => 'hideNonTranslated'
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = content_fallback',
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1'],
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 'hideNonTranslated'
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = content_fallback;1,0',
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1'],
                'sys_language_mode' => 'content_fallback',
                'sys_language_contentOL' => 'hideNonTranslated'
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = strict',
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1'],
                'sys_language_mode' => 'strict',
                'sys_language_contentOL' => 'hideNonTranslated'
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = ignore',
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1'],
                'sys_language_mode' => 'ignore',
                'sys_language_contentOL' => 'hideNonTranslated'
            ]
        ];
    }

    /**
     * Page uid 89 is translated to to Polish, but not all CE are translated
     *
     * @test
     * @dataProvider contentOnPartiallyTranslatedPageDataProvider
     *
     * @param string $typoScript
     * @param array $visibleHeaders
     * @param string $sysLanguageMode
     * @param string $sysLanguageContentOL
     */
    public function contentOnPartiallyTranslatedPage(string $typoScript, array $visibleHeaders, string $sysLanguageMode, string $sysLanguageContentOL)
    {
        $this->addTypoScriptToTemplateRecord(1, $typoScript);

        $frontendResponse = $this->getFrontendResponse(self::VALUE_PageId, 3);
        $this->assertEquals('success', $frontendResponse->getStatus());
        $responseSections = $frontendResponse->getResponseSections();

        $this->assertThat(
            $responseSections,
            $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)
            ->setField('header')
            ->setValues(...$visibleHeaders)
        );
        $this->assertThat(
            $responseSections,
            $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)
            ->setField('header')
            ->setValues(...$this->getNonVisibleHeaders($visibleHeaders))
        );

        $content = json_decode($frontendResponse->getContent());
        $this->assertEquals('[PL]Page', $content->Scope->page->title);
        $this->assertEquals(3, $content->Scope->tsfe->sys_language_uid, 'sys_language_uid doesn\'t match');
        $this->assertEquals(3, $content->Scope->tsfe->sys_language_content, 'sys_language_content doesn\'t match');
        $this->assertEquals($sysLanguageMode, $content->Scope->tsfe->sys_language_mode, 'sys_language_mode doesn\t match');
        $this->assertEquals($sysLanguageContentOL, $content->Scope->tsfe->sys_language_contentOL, 'sys_language_contentOL doesn\t match');
    }

    /**
     * Helper function to ease asserting that rest of the data set is not visible
     *
     * @param array $visibleHeaders
     * @return array
     */
    protected function getNonVisibleHeaders(array $visibleHeaders): array
    {
        $allElements = [
            'Regular Element #1',
            'Regular Element #2',
            'Regular Element #3',
            'Hidden Element #4',
            '[Translate to Dansk:] Regular Element #1',
            '[Translate to Dansk:] Regular Element #3',
            '[DK] Without default language',
            '[DK] UnHidden Element #4',
            '[DE] Without default language',
            '[Translate to Deutsch:] [Translate to Dansk:] Regular Element #1',
            '[Translate to Polski:] Regular Element #1',
            '[PL] Without default language',
            '[PL] Hidden Regular Element #2'
        ];
        return array_diff($allElements, $visibleHeaders);
    }

    /**
     * Helper function to ease asserting that rest of the files are not present
     *
     * @param array $visibleTitles
     * @return array
     */
    protected function getNonVisibleFileTitles(array $visibleTitles): array
    {
        $allElements = [
            'T3BOARD',
            'Kasper',
            '[Kasper] Image translated to Dansk',
            '[T3BOARD] Image added in Dansk (without parent)',
            '[T3BOARD] Image added to DK element without default language',
            '[T3BOARD] image translated to DE from DK',
            'Kasper2',
        ];
        return array_diff($allElements, $visibleTitles);
    }
}
