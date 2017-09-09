<?php

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This test is an Extbase version of the \TYPO3\CMS\Frontend\Tests\Functional\Rendering\LocalizedContentRenderingTest
 * scenarios are the same, just a way of fetching content is different
 *
 * This test documents current behaviour of extbase which is inconsistent with TypoScript rendering of tt_content.
 */
class TranslatedContentTest extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase
{
    const VALUE_PageId = 89;
    const TABLE_Content = 'tt_content';
    const TABLE_Pages = 'pages';

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/frontend/Tests/Functional/Rendering/DataSet/';

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['extbase', 'fluid'];

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface The object manager
     */
    protected $objectManager;

    /**
     * @var \ExtbaseTeam\BlogExample\Domain\Repository\TtContentRepository
     */
    protected $contentRepository;

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
        $this->importScenarioDataSet('LiveDefaultPages');
        $this->importScenarioDataSet('LiveDefaultElements');

        $this->backendUser->workspace = 0;
        $this->objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->contentRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\TtContentRepository::class);
        $this->setUpFrontendRootPage(1, [
            'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example/Configuration/TypoScript/setup.txt',
            'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/Frontend/ContentJsonRenderer.ts'
        ]);
    }

    public function defaultLanguageConfigurationDataProvider()
    {
        return [
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode =',
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = content_fallback',
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = content_fallback;1,0',
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = strict',
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = ignore',
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode =',
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = content_fallback',
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = content_fallback;1,0',
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = strict',
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = ignore',
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode =',
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = content_fallback',
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = content_fallback;1,0',
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = strict',
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = ignore',
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
     */
    public function onlyEnglishContentIsRenderedForDefaultLanguage($typoScript)
    {
        $this->addTypoScriptToTemplateRecord(1, $typoScript);

        $frontendResponse = $this->getFrontendResponse(self::VALUE_PageId, 0);
        $responseSections = $frontendResponse->getResponseSections('Extbase:list()');
        $visibleHeaders = ['Regular Element #1', 'Regular Element #2', 'Regular Element #3'];

        $constraint = $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)
            ->setField('header');
            call_user_func_array([$constraint, 'setValues'],$visibleHeaders);

        $this->assertThat($responseSections, $constraint);
        $constraint = $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)
            ->setField('header');
            call_user_func_array([$constraint, 'setValues'],$this->getNonVisibleHeaders($visibleHeaders));
        $this->assertThat($responseSections, $constraint);

        //assert FAL relations
        $visibleRecords = [
            297  => ['T3BOARD'],
            298  => ['Kasper'],
        ];
        foreach ($visibleRecords as $ttContentUid => $visibleFileTitles) {
            $constraint = $this->getRequestSectionStructureHasRecordConstraint()
                ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('image')
                ->setTable('sys_file_reference')->setField('title');
            call_user_func_array([$constraint, 'setValues'], $visibleFileTitles);
            $this->assertThat($responseSections, $constraint);

            $constraint = $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
                ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('image')
                ->setTable('sys_file_reference')->setField('title');
            call_user_func_array([$constraint, 'setValues'], $this->getNonVisibleFileTitles($visibleFileTitles));
            $this->assertThat($responseSections, $constraint);
        }
    }

    /**
     * Dutch language has pages_language_overlay record and some content elements are translated
     *
     * @return array
     */
    public function dutchDataProvider()
    {
        //Expected behaviour:
        //Page is translated to Dutch, so changing sys_language_mode does NOT change the results
        //Page title is always [DK]Page, and both sys_language_content and sys_language_uid are always 1
        return [
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                    config.sys_language_mode =',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => ['T3BOARD'],

                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => []
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                    307 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => [],
                    ],
                ],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = content_fallback',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => ['T3BOARD'],

                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => []
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                    307 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => [],
                    ],
                ],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = content_fallback;1,0',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => ['T3BOARD'],

                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => []
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                    307 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => [],
                    ],
                ],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = strict',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => []
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                ],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = ignore',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => ['T3BOARD'],

                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => []
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                    307 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => [],
                    ],
                ],
            ],
            5 => [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode =',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => ['T3BOARD'],

                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => []
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                    307 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => [],
                    ],
                ],
            ],
//             Expected behaviour:
//             Not translated element #2 is shown because sys_language_overlay = 1 (with sys_language_overlay = hideNonTranslated, it would be hidden)
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = content_fallback',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => ['T3BOARD'],

                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => []
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                    307 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => [],
                    ],
                ],
            ],
//             Expected behaviour:
//             Same as config.sys_language_mode = content_fallback because we're requesting language 1, so no additional fallback possible

            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = content_fallback;1,0',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => ['T3BOARD'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => []
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                    307 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => [],
                    ],
                ],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = strict',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => []
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                ],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = ignore',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => ['T3BOARD'],

                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => []
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                    307 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => [],
                    ],
                ],
            ],
//             Expected behaviour:
//             Non translated default language elements are not shown, because of hideNonTranslated
            10 => [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode =',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => ['T3BOARD'],

                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => []
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                    307 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => [],
                    ],
                ],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = content_fallback',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => ['T3BOARD'],

                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => []
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                    307 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => [],
                    ],
                ],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = content_fallback;1,0',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => ['T3BOARD'],

                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => []
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                    307 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => [],
                    ],
                ],
            ],
//            Setting sys_language_mode = strict has the same effect as previous data sets, because the translation of the page exists
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
                        'image' => []
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                ],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = ignore',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => ['T3BOARD'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => []
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                    307 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dutchDataProvider
     *
     * @param string $typoScript
     * @param array $visibleRecords
     */
    public function renderingOfDutchLanguage($typoScript, array $visibleRecords)
    {
        $this->addTypoScriptToTemplateRecord(1, $typoScript);
        $frontendResponse = $this->getFrontendResponse(self::VALUE_PageId, 1);
        $responseSections = $frontendResponse->getResponseSections('Extbase:list()');
        $visibleHeaders = array_map(function ($element) {
            return $element['header'];
        }, $visibleRecords);
        $constraint = $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)
            ->setField('header');
        call_user_func_array([$constraint, 'setValues'], $visibleHeaders);
        $this->assertThat($responseSections, $constraint);

        $constraint = $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)
            ->setField('header');
        call_user_func_array([$constraint, 'setValues'], $this->getNonVisibleHeaders($visibleHeaders));
        $this->assertThat($responseSections, $constraint);

        foreach ($visibleRecords as $ttContentUid => $properties) {
            $visibleFileTitles = $properties['image'];
            if (!empty($visibleFileTitles)) {
                $constraint = $this->getRequestSectionStructureHasRecordConstraint()
                    ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('image')
                    ->setTable('sys_file_reference')->setField('title');
                call_user_func_array([$constraint, 'setValues'], $visibleFileTitles);
                $this->assertThat($responseSections, $constraint);
            }
            $constraint = $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
                ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('image')
                ->setTable('sys_file_reference')->setField('title');
            call_user_func_array([$constraint, 'setValues'], $this->getNonVisibleFileTitles($visibleFileTitles));
            $this->assertThat($responseSections, $constraint);
        }
    }

    public function contentOnNonTranslatedPageDataProvider()
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
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => [],
                    ],
                ],
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
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => [],
                    ],
                ],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = content_fallback;1,0',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => ['T3BOARD'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => []
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                    307 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => [],
                    ],
                ],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = strict',
                'visibleRecords' => [],
                'status' => 404,
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = ignore',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Deutsch:] [Translate to Dansk:] Regular Element #1',
                        'image' => ['T3BOARD'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => []
                    ],
                    304 => [
                        'header' => '[DE] Without default language',
                        'image' => [],
                    ],
                ],
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
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => [],
                    ],
                ],
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
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => [],
                    ],
                ],
            ],
            //Dutch elements are shown because of the content fallback 1,0 - first Dutch, then default language
            //note that '[DK] Without default language' is NOT shown - due to overlays (fetch default language and overlay it with translations)
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = content_fallback;1,0',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => ['T3BOARD'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => []
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                    307 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => [],
                    ],
                ],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = strict',
                'visibleRecords' => [],
                'status' => 404
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = ignore',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Deutsch:] [Translate to Dansk:] Regular Element #1',
                        'image' => ['T3BOARD'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => []
                    ],
                    304 => [
                        'header' => '[DE] Without default language',
                        'image' => [],
                    ],
                ],
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
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => [],
                    ],
                ],
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
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => [],
                    ],
                ],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = content_fallback;1,0',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => ['T3BOARD'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => []
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                    307 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => [],
                    ],
                ],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = strict',
                'visibleRecords' => [],
                'status' => 404,
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = ignore',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Deutsch:] [Translate to Dansk:] Regular Element #1',
                        'image' => ['T3BOARD'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => []
                    ],
                    304 => [
                        'header' => '[DE] Without default language',
                        'image' => [],
                    ],
                ],
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
     * @param string $status 'success' or 404
     */
    public function contentOnNonTranslatedPageGerman($typoScript, array $visibleRecords, $status='success')
    {
        $this->addTypoScriptToTemplateRecord(1, $typoScript);
        $visibleHeaders = array_map(function ($element) {
            return $element['header'];
        }, $visibleRecords);

        $frontendResponse = $this->getFrontendResponse(self::VALUE_PageId, 2);
        if ($status === 'success') {
            $responseSections = $frontendResponse->getResponseSections('Extbase:list()');
            $constraint =
                $this->getRequestSectionHasRecordConstraint()
                ->setTable(self::TABLE_Content)
                ->setField('header');
                call_user_func_array([$constraint, 'setValues'],$visibleHeaders);

            $this->assertThat(
                $responseSections,$constraint);
               $constraint = $this->getRequestSectionDoesNotHaveRecordConstraint()
                ->setTable(self::TABLE_Content)
                ->setField('header');
                call_user_func_array([$constraint, 'setValues'],$this->getNonVisibleHeaders($visibleHeaders));
            $this->assertThat($responseSections, $constraint);

            foreach ($visibleRecords as $ttContentUid => $properties) {
                $visibleFileTitles = $properties['image'];
                if (!empty($visibleFileTitles)) {
                    $constraint = $this->getRequestSectionStructureHasRecordConstraint()
                        ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('image')
                        ->setTable('sys_file_reference')->setField('title');
                    call_user_func_array([$constraint, 'setValues'], $visibleFileTitles);
                    $this->assertThat($responseSections, $constraint);
                }
                $constraint = $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
                    ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('image')
                    ->setTable('sys_file_reference')->setField('title');
                call_user_func_array([$constraint, 'setValues'], $this->getNonVisibleFileTitles($visibleFileTitles));
                $this->assertThat($responseSections, $constraint);
            }
        }
        //some configuration combinations results in 404, in that case status will be set to 404
        $this->assertEquals($status, $frontendResponse->getStatus());
    }

    public function contentOnPartiallyTranslatedPageDataProvider()
    {

        //Expected behaviour:
        //Setting sys_language_mode to different values doesn't influence the result as the requested page is translated to Polish,
        //Page title is always [PL]Page, and both sys_language_content and sys_language_uid are always 3
        return [
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode =',
                'visibleRecordHeaders' => ['Regular Element #3', '[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = content_fallback',
                'visibleRecordHeaders' => ['Regular Element #3', '[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = content_fallback;1,0',
                'visibleRecordHeaders' => ['Regular Element #3', '[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = strict',
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 0
                                config.sys_language_mode = ignore',
                'visibleRecordHeaders' => ['Regular Element #3', '[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
            ],
            5 => [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode =',
                'visibleRecordHeaders' => ['[PL] Without default language', '[Translate to Polski:] Regular Element #1', 'Regular Element #3'],
            ],
            // Expected behaviour:
            // Not translated element #2 is shown because sys_language_overlay = 1 (with sys_language_overlay = hideNonTranslated, it would be hidden)
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = content_fallback',
                'visibleRecordHeaders' => ['[PL] Without default language', '[Translate to Polski:] Regular Element #1', 'Regular Element #3'],
            ],
//             Expected behaviour:
//             Element #3 is not translated in PL and it is translated in DK. It's not shown as content_fallback is not related to single CE level
//             but on page level - and this page is translated to Polish, so no fallback is happening
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = content_fallback;1,0',
                'visibleRecordHeaders' => ['[PL] Without default language', '[Translate to Polski:] Regular Element #1', 'Regular Element #3'],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = strict',
                'visibleRecordHeaders' => ['[PL] Without default language', '[Translate to Polski:] Regular Element #1'],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = 1
                                config.sys_language_mode = ignore',
                'visibleRecordHeaders' => ['[PL] Without default language', '[Translate to Polski:] Regular Element #1', 'Regular Element #3'],
            ],
            // Expected behaviour:
            // Non translated default language elements are not shown, because of hideNonTranslated
            10 => [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode =',
                'visibleRecordHeaders' => ['[PL] Without default language', 'Regular Element #3', '[Translate to Polski:] Regular Element #1'],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = content_fallback',
                'visibleRecordHeaders' => ['[PL] Without default language', 'Regular Element #3', '[Translate to Polski:] Regular Element #1'],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = content_fallback;1,0',
                'visibleRecordHeaders' => ['[PL] Without default language', 'Regular Element #3', '[Translate to Polski:] Regular Element #1'],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = strict',
                'visibleRecordHeaders' => ['[PL] Without default language', '[Translate to Polski:] Regular Element #1'],
            ],
            [
                'typoScript' => 'config.sys_language_overlay = hideNonTranslated
                                config.sys_language_mode = ignore',
                'visibleRecordHeaders' => ['[PL] Without default language', 'Regular Element #3', '[Translate to Polski:] Regular Element #1'],
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
     */
    public function contentOnPartiallyTranslatedPage($typoScript, array $visibleHeaders)
    {
        $this->addTypoScriptToTemplateRecord(1, $typoScript);

        $frontendResponse = $this->getFrontendResponse(self::VALUE_PageId, 3);
        $this->assertEquals('success', $frontendResponse->getStatus());
        $responseSections = $frontendResponse->getResponseSections('Extbase:list()');

        $constraint =
            $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)
            ->setField('header');
            call_user_func_array([$constraint, 'setValues'],$visibleHeaders);

        $this->assertThat(
            $responseSections,$constraint);
           $constraint = $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)
            ->setField('header');
            call_user_func_array([$constraint, 'setValues'],$this->getNonVisibleHeaders($visibleHeaders));
        $this->assertThat($responseSections, $constraint);
    }

    /**
     * Helper function to ease asserting that rest of the data set is not visible
     *
     * @param array $visibleHeaders
     * @return array
     */
    protected function getNonVisibleHeaders(array $visibleHeaders)
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
     * Helper function to ease asserting that rest of the data set is not visible
     *
     * @param array $visibleTitles
     * @return array
     */
    protected function getNonVisibleFileTitles(array $visibleTitles)
    {
        $allElements = [
            'T3BOARD',
            'Kasper',
            '[Kasper] Image translated to Dansk',
            '[T3BOARD] Image added in Dansk (without parent)',
            '[T3BOARD] Image added to DK element without default language',
            '[T3BOARD] image translated to DE from DK',
        ];
        return array_diff($allElements, $visibleTitles);
    }
}
