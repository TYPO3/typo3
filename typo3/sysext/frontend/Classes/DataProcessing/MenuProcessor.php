<?php
namespace TYPO3\CMS\Frontend\DataProcessing;

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

use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * This menu processor utilizes HMENU to generate a json encoded menu
 * string that will be decoded again and assigned to FLUIDTEMPLATE as
 * variable. Additional DataProcessing is supported and will be applied
 * to each record.
 *
 * Options:
 * as - The variable to be used within the result
 * levels - Number of levels of the menu
 * expandAll = If false, submenus will only render if the parent page is active
 * includeSpacer = If true, pagetype spacer will be included in the menu
 * titleField = Field that should be used for the title
 *
 * See HMENU docs for more options.
 * https://docs.typo3.org/typo3cms/TyposcriptReference/ContentObjects/Hmenu/Index.html
 *
 *
 * Example TypoScript configuration:
 *
 * 10 = TYPO3\CMS\Frontend\DataProcessing\MenuProcessor
 * 10 {
 *   special = list
 *   special.value.field = pages
 *   levels = 7
 *   as = menu
 *   expandAll = 1
 *   includeSpacer = 1
 *   titleField = nav_title // title
 *   dataProcessing {
 *     10 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor
 *     10 {
 *        references.fieldName = media
 *     }
 *   }
 * }
 */
class MenuProcessor implements DataProcessorInterface
{
    const LINK_PLACEHOLDER = '###LINKPLACEHOLDER###';
    const TARGET_PLACEHOLDER = '###TARGETPLACEHOLDER###';

    /**
     * The content object renderer
     *
     * @var ContentObjectRenderer
     */
    public $cObj;

    /**
     * The processor configuration
     *
     * @var array
     */
    protected $processorConfiguration;

    /**
     * Allowed configuration keys for menu generation, other keys
     * will throw an exception to prevent configuration errors.
     *
     * @var array
     */
    public $allowedConfigurationKeys = [
        'cache_period',
        'entryLevel',
        'entryLevel.',
        'special',
        'special.',
        'minItems',
        'minItems.',
        'maxItems',
        'maxItems.',
        'begin',
        'begin.',
        'alternativeSortingField',
        'alternativeSortingField.',
        'showAccessRestrictedPages',
        'showAccessRestrictedPages.',
        'excludeUidList',
        'excludeUidList.',
        'excludeDoktypes',
        'includeNotInMenu',
        'alwaysActivePIDlist',
        'alwaysActivePIDlist.',
        'protectLvar',
        'addQueryString',
        'addQueryString.',
        'if',
        'if.',
        'levels',
        'levels.',
        'expandAll',
        'expandAll.',
        'includeSpacer',
        'includeSpacer.',
        'as',
        'titleField',
        'titleField.',
        'dataProcessing',
        'dataProcessing.'
    ];

    /**
     * Remove keys from configuration that should not be passed
     * to HMENU to prevent configuration errors
     *
     * @var array
     */
    public $removeConfigurationKeysForHmenu = [
        'levels',
        'levels.',
        'expandAll',
        'expandAll.',
        'includeSpacer',
        'includeSpacer.',
        'as',
        'titleField',
        'titleField.',
        'dataProcessing',
        'dataProcessing.'
    ];

    /**
     * @var array
     */
    protected $menuConfig = [
        'wrap' => '[|]'
    ];

    /**
     * @var array
     */
    protected $menuLevelConfig = [
        'doNotLinkIt' => '1',
        'wrapItemAndSub' => '{|}, |*| {|}, |*| {|}',
        'stdWrap.' => [
            'cObject' => 'COA',
            'cObject.' => [
                '10' => 'USER',
                '10.' => [
                    'userFunc' => 'TYPO3\CMS\Frontend\DataProcessing\MenuProcessor->getDataAsJson',
                    'stdWrap.' => [
                        'wrap' => '"data":|'
                    ]
                ],
                '20' => 'TEXT',
                '20.' => [
                    'field' => 'nav_title // title',
                    'trim' => '1',
                    'wrap' => ',"title":|',
                    'preUserFunc' => 'TYPO3\CMS\Frontend\DataProcessing\MenuProcessor->jsonEncodeUserFunc'
                ],
                '21' => 'TEXT',
                '21.' => [
                    'value' => self::LINK_PLACEHOLDER,
                    'wrap' => ',"link":|',
                ],
                '22' => 'TEXT',
                '22.' => [
                    'value' => self::TARGET_PLACEHOLDER,
                    'wrap' => ',"target":|',
                ],
                '30' => 'TEXT',
                '30.' => [
                    'value' => '0',
                    'wrap' => ',"active":|'
                ],
                '40' => 'TEXT',
                '40.' => [
                    'value' => '0',
                    'wrap' => ',"current":|'
                ],
                '50' => 'TEXT',
                '50.' => [
                    'value' => '0',
                    'wrap' => ',"spacer":|'
                ]
            ]
        ]
    ];

    /**
     * @var array
     */
    public $menuDefaults = [
        'levels' => 1,
        'expandAll' => 1,
        'includeSpacer' => 0,
        'as' => 'menu',
        'titleField' => 'nav_title // title'
    ];

    /**
     * @var int
     */
    protected $menuLevels;

    /**
     * @var int
     */
    protected $menuExpandAll;

    /**
     * @var int
     */
    protected $menuIncludeSpacer;

    /**
     * @var string
     */
    protected $menuTitleField;

    /**
     * @var string
     */
    protected $menuAlternativeSortingField;

    /**
     * @var string
     */
    protected $menuTargetVariableName;

    /**
     * @var ContentDataProcessor
     */
    protected $contentDataProcessor;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->contentDataProcessor = GeneralUtility::makeInstance(ContentDataProcessor::class);
    }

    /**
     * Get configuration value from processorConfiguration
     *
     * @param string $key
     * @return string
     */
    protected function getConfigurationValue($key)
    {
        return $this->cObj->stdWrapValue($key, $this->processorConfiguration, $this->menuDefaults[$key]);
    }

    /**
     * Validate configuration
     *
     * @throws \InvalidArgumentException
     */
    public function validateConfiguration()
    {
        $invalidArguments = [];
        foreach ($this->processorConfiguration as $key => $value) {
            if (!in_array($key, $this->allowedConfigurationKeys)) {
                $invalidArguments[str_replace('.', '', $key)] = $key;
            }
        }
        if (!empty($invalidArguments)) {
            throw new \InvalidArgumentException('MenuProcessor Configuration contains invalid Arguments: ' . implode(', ', $invalidArguments), 1478806566);
        }
    }

    /**
     * Prepare Configuration
     */
    public function prepareConfiguration()
    {
        $this->menuConfig += $this->processorConfiguration;
        // Filter configuration
        foreach ($this->menuConfig as $key => $value) {
            if (in_array($key, $this->removeConfigurationKeysForHmenu)) {
                unset($this->menuConfig[$key]);
            }
        }
        // Process special value
        if (isset($this->menuConfig['special.']['value.'])) {
            $this->menuConfig['special.']['value'] = $this->cObj->stdWrap($this->menuConfig['special.']['value'], $this->menuConfig['special.']['value.']);
            unset($this->menuConfig['special.']['value.']);
        }
    }

    /**
     * Prepare configuration for a certain menu level in the hierarchy
     */
    public function prepareLevelConfiguration()
    {
        $this->menuLevelConfig['stdWrap.']['cObject.'] = array_replace_recursive(
            $this->menuLevelConfig['stdWrap.']['cObject.'],
            [
                '20.' => [
                    'field' => $this->menuTitleField,
                ]
            ]
        );
    }

    /**
     * Prepare the configuration when rendering a language menu
     */
    public function prepareLevelLanguageConfiguration()
    {
        if ($this->menuConfig['special'] === 'language') {
            $languageUids = $this->menuConfig['special.']['value'];
            if ($this->menuConfig['special.']['value'] === 'auto') {
                $site = $this->getCurrentSite();
                $languageUids = implode(',', array_keys($site->getLanguages()));
            }
            $this->menuLevelConfig['stdWrap.']['cObject.'] = array_replace_recursive(
                $this->menuLevelConfig['stdWrap.']['cObject.'],
                [
                    '60' => 'TEXT',
                    '60.' => [
                        'value' => '1',
                        'wrap' => ',"available":|'
                    ],
                    '70' => 'TEXT',
                    '70.' => [
                        'value' => $languageUids,
                        'listNum.' => [
                            'stdWrap.' => [
                                'data' => 'register:count_HMENU_MENUOBJ',
                                'wrap' => '|-1'
                            ],
                            'splitChar' => ','
                        ],
                        'wrap' => ',"languageUid":"|"'
                    ]
                ]
            );
        }
    }

    /**
     * Build the menu configuration so it can be treated by HMENU cObject
     */
    public function buildConfiguration()
    {
        for ($i = 1; $i <= $this->menuLevels; $i++) {
            $this->menuConfig[$i] = 'TMENU';
            $this->menuConfig[$i . '.']['IProcFunc'] = 'TYPO3\CMS\Frontend\DataProcessing\MenuProcessor->replacePlaceholderInRenderedMenuItem';
            if ($i > 1) {
                $this->menuConfig[$i . '.']['stdWrap.']['wrap'] = ',"children": [|]';
            }
            if (array_key_exists('showAccessRestrictedPages', $this->menuConfig)) {
                $this->menuConfig[$i . '.']['showAccessRestrictedPages'] = $this->menuConfig['showAccessRestrictedPages'];
                if (array_key_exists('showAccessRestrictedPages.', $this->menuConfig)
                    && is_array($this->menuConfig['showAccessRestrictedPages.'])) {
                    $this->menuConfig[$i . '.']['showAccessRestrictedPages.'] = $this->menuConfig['showAccessRestrictedPages.'];
                }
            }
            $this->menuConfig[$i . '.']['expAll'] = $this->menuExpandAll;
            $this->menuConfig[$i . '.']['alternativeSortingField'] = $this->menuAlternativeSortingField;
            $this->menuConfig[$i . '.']['NO'] = '1';
            $this->menuConfig[$i . '.']['NO.'] = $this->menuLevelConfig;
            if ($this->menuIncludeSpacer) {
                $this->menuConfig[$i . '.']['SPC'] = '1';
                $this->menuConfig[$i . '.']['SPC.'] = $this->menuConfig[$i . '.']['NO.'];
                $this->menuConfig[$i . '.']['SPC.']['stdWrap.']['cObject.']['50.']['value'] = '1';
            }
            $this->menuConfig[$i . '.']['IFSUB'] = '1';
            $this->menuConfig[$i . '.']['IFSUB.'] = $this->menuConfig[$i . '.']['NO.'];
            $this->menuConfig[$i . '.']['ACT'] = '1';
            $this->menuConfig[$i . '.']['ACT.'] = $this->menuConfig[$i . '.']['NO.'];
            $this->menuConfig[$i . '.']['ACT.']['stdWrap.']['cObject.']['30.']['value'] = '1';
            $this->menuConfig[$i . '.']['ACTIFSUB'] = '1';
            $this->menuConfig[$i . '.']['ACTIFSUB.'] = $this->menuConfig[$i . '.']['ACT.'];
            $this->menuConfig[$i . '.']['CUR'] = '1';
            $this->menuConfig[$i . '.']['CUR.'] = $this->menuConfig[$i . '.']['ACT.'];
            $this->menuConfig[$i . '.']['CUR.']['stdWrap.']['cObject.']['40.']['value'] = '1';
            $this->menuConfig[$i . '.']['CURIFSUB'] = '1';
            $this->menuConfig[$i . '.']['CURIFSUB.'] = $this->menuConfig[$i . '.']['CUR.'];
            if ($this->menuConfig['special'] === 'language') {
                $this->menuConfig[$i . '.']['USERDEF1'] = $this->menuConfig[$i . '.']['NO'];
                $this->menuConfig[$i . '.']['USERDEF1.'] = $this->menuConfig[$i . '.']['NO.'];
                $this->menuConfig[$i . '.']['USERDEF1.']['stdWrap.']['cObject.']['60.']['value'] = '0';
                $this->menuConfig[$i . '.']['USERDEF2'] = $this->menuConfig[$i . '.']['ACT'];
                $this->menuConfig[$i . '.']['USERDEF2.'] = $this->menuConfig[$i . '.']['ACT.'];
                $this->menuConfig[$i . '.']['USERDEF2.']['stdWrap.']['cObject.']['60.']['value'] = '0';
            }
        }
    }

    /**
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array $contentObjectConfiguration The configuration of Content Object
     * @param array $processorConfiguration The configuration of this processor
     * @param array $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array the processed data as key/value store
     */
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData)
    {
        $this->cObj = $cObj;
        $this->processorConfiguration = $processorConfiguration;

        // Get Configuration
        $this->menuLevels = (int)$this->getConfigurationValue('levels') ?: 1;
        $this->menuExpandAll = (int)$this->getConfigurationValue('expandAll');
        $this->menuIncludeSpacer = (int)$this->getConfigurationValue('includeSpacer');
        $this->menuTargetVariableName = $this->getConfigurationValue('as');
        $this->menuTitleField = $this->getConfigurationValue('titleField');
        $this->menuAlternativeSortingField = $this->getConfigurationValue('alternativeSortingField');

        // Validate Configuration
        $this->validateConfiguration();

        // Build Configuration
        $this->prepareConfiguration();
        $this->prepareLevelConfiguration();
        $this->prepareLevelLanguageConfiguration();
        $this->buildConfiguration();

        // Process Configuration
        $menuContentObject = $cObj->getContentObject('HMENU');
        $renderedMenu = $menuContentObject->render($this->menuConfig);
        if (!$renderedMenu) {
            return $processedData;
        }

        // Process menu
        $menu = json_decode($renderedMenu, true);
        $processedMenu = [];

        foreach ($menu as $key => $page) {
            $processedMenu[$key] = $this->processAdditionalDataProcessors($page, $processorConfiguration);
        }

        // Return processed data
        $processedData[$this->menuTargetVariableName] = $processedMenu;
        return $processedData;
    }

    /**
     * Process additional data processors
     *
     * @param array $page
     * @param array $processorConfiguration
     * @return array
     */
    protected function processAdditionalDataProcessors($page, $processorConfiguration)
    {
        if (is_array($page['children'])) {
            foreach ($page['children'] as $key => $item) {
                $page['children'][$key] = $this->processAdditionalDataProcessors($item, $processorConfiguration);
            }
        }
        /** @var ContentObjectRenderer $recordContentObjectRenderer */
        $recordContentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $recordContentObjectRenderer->start($page['data'], 'pages');
        $processedPage = $this->contentDataProcessor->process($recordContentObjectRenderer, $processorConfiguration, $page);
        return $processedPage;
    }

    /**
     * Gets the data of the current record in JSON format
     *
     * @return string JSON encoded data
     */
    public function getDataAsJson()
    {
        return $this->jsonEncode($this->cObj->data);
    }

    /**
     * This UserFunc encodes the content as Json
     *
     * @param string $content
     * @param array $conf
     * @return string JSON encoded content
     */
    public function jsonEncodeUserFunc($content, $conf)
    {
        $content = $this->jsonEncode($content);
        return $content;
    }

    /**
     * JSON Encode
     *
     * @param mixed $value
     * @return string
     */
    public function jsonEncode($value)
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    }

    /**
     * This UserFunc gets the link and the target
     *
     * @param array $menuItem
     * @param array $conf
     * @return array
     */
    public function replacePlaceholderInRenderedMenuItem($menuItem, $conf)
    {
        $link = $this->jsonEncode($menuItem['linkHREF']['HREF']);
        $target = $this->jsonEncode($menuItem['linkHREF']['TARGET']);

        $menuItem['parts']['title'] = str_replace(self::LINK_PLACEHOLDER, $link, $menuItem['parts']['title']);
        $menuItem['parts']['title'] = str_replace(self::TARGET_PLACEHOLDER, $target, $menuItem['parts']['title']);

        return $menuItem;
    }

    /**
     * Returns the currently configured "site" if a site is configured (= resolved) in the current request.
     *
     * @return SiteInterface
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     */
    protected function getCurrentSite(): SiteInterface
    {
        $matcher = GeneralUtility::makeInstance(SiteMatcher::class);
        return $matcher->matchByPageId((int)$this->getTypoScriptFrontendController()->id);
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
