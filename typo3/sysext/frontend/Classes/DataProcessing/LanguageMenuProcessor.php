<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Frontend\DataProcessing;

use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Utility\CanonicalizationUtility;

/**
 * This menu processor generates a json encoded menu string that will be
 * decoded again and assigned to FLUIDTEMPLATE as variable.
 *
 * Options:
 * if        - TypoScript if condition
 * languages - A list of languages id's (e.g. 0,1,2) to use for the menu
 *             creation or 'auto' to load from system or site languages
 * as        - The variable to be used within the result
 *
 * Example TypoScript configuration:
 * 10 = TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor
 * 10 {
 *   as = languagenavigation
 * }
 */
class LanguageMenuProcessor implements DataProcessorInterface
{
    protected const LINK_PLACEHOLDER = '###LINKPLACEHOLDER###';

    /**
     * The content object renderer
     *
     * @var ContentObjectRenderer
     */
    protected ?ContentObjectRenderer $cObj = null;

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
    protected $allowedConfigurationKeys = [
        'if',
        'if.',
        'languages',
        'languages.',
        'as',
        'addQueryString',
        'addQueryString.',
    ];

    /**
     * Remove keys from configuration that should not be passed
     * to HMENU to prevent configuration errors
     *
     * @var array
     */
    protected $removeConfigurationKeysForHmenu = [
        'languages',
        'languages.',
        'as',
    ];

    /**
     * @var array
     */
    protected $menuConfig = [
        'special' => 'language',
        'addQueryString' => 1,
        'wrap' => '[|]',
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
                '1' => 'LOAD_REGISTER',
                '1.' => [
                    'languageId.' => [
                        'cObject' => 'TEXT',
                        'cObject.' => [
                            'value.' => [
                                'data' => 'register:languages_HMENU',
                            ],
                            'listNum.' => [
                                'stdWrap.' => [
                                    'data' => 'register:count_HMENU_MENUOBJ',
                                    'wrap' => '|-1',
                                ],
                                'splitChar' => ',',
                            ],
                        ],
                    ],
                ],
                '10' => 'TEXT',
                '10.' => [
                    'stdWrap.' => [
                        'data' => 'register:languageId',
                    ],
                    'wrap' => '"languageId":|',
                ],
                '11' => 'USER',
                '11.' => [
                    'userFunc' => 'TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor->getFieldAsJson',
                    'language.' => [
                        'data' => 'register:languageId',
                    ],
                    'field' => 'locale',
                    'stdWrap.' => [
                        'wrap' => ',"locale":|',
                    ],
                ],
                '20' => 'USER',
                '20.' => [
                    'userFunc' => 'TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor->getFieldAsJson',
                    'language.' => [
                        'data' => 'register:languageId',
                    ],
                    'field' => 'title',
                    'stdWrap.' => [
                        'wrap' => ',"title":|',
                    ],
                ],
                '21' => 'USER',
                '21.' => [
                    'userFunc' => 'TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor->getFieldAsJson',
                    'language.' => [
                        'data' => 'register:languageId',
                    ],
                    'field' => 'navigationTitle',
                    'stdWrap.' => [
                        'wrap' => ',"navigationTitle":|',
                    ],
                ],
                '22' => 'USER',
                '22.' => [
                    'userFunc' => 'TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor->getFieldAsJson',
                    'language.' => [
                        'data' => 'register:languageId',
                    ],
                    'field' => 'twoLetterIsoCode',
                    'stdWrap.' => [
                        'wrap' => ',"twoLetterIsoCode":|',
                    ],
                ],
                '23' => 'USER',
                '23.' => [
                    'userFunc' => 'TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor->getFieldAsJson',
                    'language.' => [
                        'data' => 'register:languageId',
                    ],
                    'field' => 'hreflang',
                    'stdWrap.' => [
                        'wrap' => ',"hreflang":|',
                    ],
                ],
                '24' => 'USER',
                '24.' => [
                    'userFunc' => 'TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor->getFieldAsJson',
                    'language.' => [
                        'data' => 'register:languageId',
                    ],
                    'field' => 'direction',
                    'stdWrap.' => [
                        'wrap' => ',"direction":|',
                    ],
                ],
                '25' => 'USER',
                '25.' => [
                    'userFunc' => 'TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor->getFieldAsJson',
                    'language.' => [
                        'data' => 'register:languageId',
                    ],
                    'field' => 'flag',
                    'stdWrap.' => [
                        'wrap' => ',"flag":|',
                    ],
                ],
                '90' => 'TEXT',
                '90.' => [
                    'value' => self::LINK_PLACEHOLDER,
                    'wrap' => ',"link":|',
                ],
                '91' => 'TEXT',
                '91.' => [
                    'value' => '0',
                    'wrap' => ',"active":|',
                ],
                '92' => 'TEXT',
                '92.' => [
                    'value' => '0',
                    'wrap' => ',"current":|',
                ],
                '93' => 'TEXT',
                '93.' => [
                    'value' => '1',
                    'wrap' => ',"available":|',
                ],
                '99' => 'RESTORE_REGISTER',
            ],
        ],
    ];

    /**
     * @var array
     */
    protected $menuDefaults = [
        'as' => 'languagemenu',
    ];

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
     * This is called from UserContentObject via ContentObjectRenderer->callUserFunction()
     * for nested menu items - those use a USER content object for getFieldAsJson().
     *
     * @param ContentObjectRenderer $cObj
     */
    public function setContentObjectRenderer(ContentObjectRenderer $cObj): void
    {
        $this->cObj = $cObj;
    }

    /**
     * Get configuration value from processorConfiguration
     *
     * @param string $key
     * @return string
     */
    protected function getConfigurationValue(string $key): string
    {
        return $this->cObj->stdWrapValue($key, $this->processorConfiguration, $this->menuDefaults[$key]);
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * Returns the currently configured "site" if a site is configured (= resolved) in the current request.
     *
     * @return Site
     */
    protected function getCurrentSite(): Site
    {
        return $this->getTypoScriptFrontendController()->getSite();
    }

    /**
     * JSON Encode
     *
     * @param mixed $value
     * @return string
     */
    protected function jsonEncode($value): string
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function validateConfiguration()
    {
        $invalidArguments = [];
        foreach ($this->processorConfiguration as $key => $value) {
            if (!in_array($key, $this->allowedConfigurationKeys)) {
                $invalidArguments[str_replace('.', '', $key)] = $key;
            }
        }
        if (!empty($invalidArguments)) {
            throw new \InvalidArgumentException('LanguageMenuProcessor configuration contains invalid arguments: ' . implode(', ', $invalidArguments), 1522959188);
        }
    }

    /**
     * Process languages and filter the configuration
     */
    protected function prepareConfiguration(): void
    {
        $this->menuConfig = array_merge($this->menuConfig, $this->processorConfiguration);

        // Process languages
        $this->menuConfig['special.']['value'] = $this->cObj->stdWrapValue('languages', $this->menuConfig, 'auto');

        // Filter configuration
        foreach ($this->menuConfig as $key => $value) {
            if (in_array($key, $this->removeConfigurationKeysForHmenu, true)) {
                unset($this->menuConfig[$key]);
            }
        }

        $paramsToExclude = CanonicalizationUtility::getParamsToExcludeForCanonicalizedUrl(
            (int)$this->getTypoScriptFrontendController()->id,
            (array)$GLOBALS['TYPO3_CONF_VARS']['FE']['additionalCanonicalizedUrlParameters']
        );

        $this->menuConfig['addQueryString.']['exclude'] = implode(
            ',',
            array_merge(
                GeneralUtility::trimExplode(',', $this->menuConfig['addQueryString.']['exclude'] ?? '', true),
                $paramsToExclude
            )
        );
    }

    /**
     * Build the menu configuration so it can be treated by HMENU cObject
     */
    protected function buildConfiguration(): void
    {
        $this->menuConfig['1'] = 'TMENU';
        $this->menuConfig['1.']['IProcFunc'] = LanguageMenuProcessor::class . '->replacePlaceholderInRenderedMenuItem';
        $this->menuConfig['1.']['NO'] = '1';
        $this->menuConfig['1.']['NO.'] = $this->menuLevelConfig;
        $this->menuConfig['1.']['ACT'] = $this->menuConfig['1.']['NO'];
        $this->menuConfig['1.']['ACT.'] = $this->menuConfig['1.']['NO.'];
        $this->menuConfig['1.']['ACT.']['stdWrap.']['cObject.']['91.']['value'] = '1';
        $this->menuConfig['1.']['CUR'] = $this->menuConfig['1.']['ACT'];
        $this->menuConfig['1.']['CUR.'] = $this->menuConfig['1.']['ACT.'];
        $this->menuConfig['1.']['CUR.']['stdWrap.']['cObject.']['92.']['value'] = '1';
        $this->menuConfig['1.']['USERDEF1'] = $this->menuConfig['1.']['NO'];
        $this->menuConfig['1.']['USERDEF1.'] = $this->menuConfig['1.']['NO.'];
        $this->menuConfig['1.']['USERDEF1.']['stdWrap.']['cObject.']['93.']['value'] = '0';
        $this->menuConfig['1.']['USERDEF2'] = $this->menuConfig['1.']['ACT'];
        $this->menuConfig['1.']['USERDEF2.'] = $this->menuConfig['1.']['ACT.'];
        $this->menuConfig['1.']['USERDEF2.']['stdWrap.']['cObject.']['93.']['value'] = '0';
    }

    /**
     * Validate and Build the menu configuration so it can be treated by HMENU cObject
     */
    protected function validateAndBuildConfiguration(): void
    {
        // Validate Configuration
        $this->validateConfiguration();

        // Build Configuration
        $this->prepareConfiguration();
        $this->buildConfiguration();
    }

    /**
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array $contentObjectConfiguration The configuration of Content Object
     * @param array $processorConfiguration The configuration of this processor
     * @param array $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array the processed data as key/value store
     */
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData): array
    {
        $this->cObj = $cObj;
        $this->processorConfiguration = $processorConfiguration;

        // Get Configuration
        $this->menuTargetVariableName = $this->getConfigurationValue('as');

        // Validate and Build Configuration
        $this->validateAndBuildConfiguration();

        // Process Configuration
        $menuContentObject = $cObj->getContentObject('HMENU');
        $renderedMenu = $menuContentObject->render($this->menuConfig);
        if (!$renderedMenu) {
            return $processedData;
        }

        // Process menu
        $menu = json_decode($renderedMenu, true);
        $processedMenu = [];
        if (is_iterable($menu)) {
            foreach ($menu as $key => $language) {
                $processedMenu[$key] = $language;
            }
        }

        // Return processed data
        $processedData[$this->menuTargetVariableName] = $processedMenu;
        return $processedData;
    }

    /**
     * This UserFunc gets the link and the target
     *
     * @param array $menuItem
     * @return array
     */
    public function replacePlaceholderInRenderedMenuItem(array $menuItem): array
    {
        $link = $this->jsonEncode($menuItem['linkHREF']['HREF']);

        $menuItem['parts']['title'] = str_replace(self::LINK_PLACEHOLDER, $link, $menuItem['parts']['title']);

        return $menuItem;
    }

    /**
     * Returns the data from the field and language submitted by $conf in JSON format
     *
     * @param string $content Empty string (no content to process)
     * @param array $conf TypoScript configuration
     * @return string JSON encoded data
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     */
    public function getFieldAsJson(string $content, array $conf): string
    {
        // Support of stdWrap for parameters
        if (isset($conf['language.'])) {
            $conf['language'] = $this->cObj->stdWrapValue('language', $conf ?? []);
            unset($conf['language.']);
        }
        if (isset($conf['field.'])) {
            $conf['field'] = $this->cObj->stdWrapValue('field', $conf ?? []);
            unset($conf['field.']);
        }

        // Check required fields
        if ($conf['language'] === '') {
            throw new \InvalidArgumentException('Argument \'language\' must be supplied.', 1522959186);
        }
        if ($conf['field'] === '') {
            throw new \InvalidArgumentException('Argument \'field\' must be supplied.', 1522959187);
        }

        // Get and check current site
        $site = $this->getCurrentSite();

        // Throws InvalidArgumentException in case language is not found which is fine
        $language = $site->getLanguageById((int)$conf['language']);
        if ($language->enabled()) {
            $language = $language->toArray();
        } else {
            $language = null;
        }

        // Check field for return exists
        if ($language !== null && !isset($language[$conf['field']])) {
            throw new \InvalidArgumentException('Invalid value \'' . $conf['field'] . '\' for argument \'field\' supplied.', 1524063160);
        }

        return $this->jsonEncode($language[$conf['field']]);
    }
}
