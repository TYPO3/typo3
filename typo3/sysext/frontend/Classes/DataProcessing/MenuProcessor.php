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

namespace TYPO3\CMS\Frontend\DataProcessing;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\ContentObject\Menu\MenuContentObjectFactory;

/**
 * This menu processor generates a menu array that will be assigned to
 * FLUIDTEMPLATE as variable. Additional DataProcessing is supported and
 * will be applied to each record.
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
    /**
     * The content object renderer
     */
    protected ?ContentObjectRenderer $cObj = null;

    /**
     * The processor configuration
     */
    protected array $processorConfiguration;

    /**
     * Allowed configuration keys for menu generation, other keys
     * will throw an exception to prevent configuration errors.
     */
    public array $allowedConfigurationKeys = [
        'cache',
        'cache.',
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
        'includeNotInMenu.',
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
        'dataProcessing.',
    ];

    /**
     * Remove keys from configuration that should not be passed
     * to HMENU to prevent configuration errors
     */
    public array $removeConfigurationKeysForHmenu = [
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
        'dataProcessing.',
    ];

    protected array $menuConfig = [];

    public array $menuDefaults = [
        'levels' => 1,
        'expandAll' => 1,
        'includeSpacer' => 0,
        'as' => 'menu',
        'titleField' => 'nav_title // title',
    ];

    protected int $menuLevels;
    protected int $menuExpandAll;
    protected int $menuIncludeSpacer;
    protected string $menuTitleField;
    protected string $menuAlternativeSortingField;
    protected string $menuTargetVariableName;

    public function __construct(
        protected ContentDataProcessor $contentDataProcessor,
        protected MenuContentObjectFactory $menuContentObjectFactory,
    ) {}

    /**
     * Get configuration value from processorConfiguration
     */
    protected function getConfigurationValue(string $key): string
    {
        return $this->cObj->stdWrapValue($key, $this->processorConfiguration, $this->menuDefaults[$key] ?? '');
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function validateConfiguration(): void
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

    public function prepareConfiguration(): void
    {
        $this->menuConfig = $this->processorConfiguration;
        // Filter configuration
        foreach ($this->menuConfig as $key => $value) {
            if (in_array($key, $this->removeConfigurationKeysForHmenu)) {
                unset($this->menuConfig[$key]);
            }
        }
        // Process special value
        if (isset($this->menuConfig['special.']['value.'])) {
            $this->menuConfig['special.']['value'] = $this->cObj->stdWrapValue('value', $this->menuConfig['special.']);
            unset($this->menuConfig['special.']['value.']);
        }
    }

    /**
     * Build the menu configuration so it can be treated by TMENU
     */
    public function buildConfiguration(): void
    {
        for ($i = 1; $i <= $this->menuLevels; $i++) {
            $this->menuConfig[$i] = 'TMENU';
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
            if ($this->menuIncludeSpacer) {
                $this->menuConfig[$i . '.']['SPC'] = '1';
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
        $this->buildConfiguration();

        // Create menu object and get menu items directly
        $request = $cObj->getRequest();
        $menu = $this->menuContentObjectFactory->getMenuObjectByType('TMENU');
        $menu->parent_cObj = $cObj;

        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        if (!$menu->start(null, $pageRepository, '', $this->menuConfig, 1, '', $request)) {
            return $processedData;
        }
        $menu->makeMenu();
        $menuItems = $menu->getMenuItems();

        if ($menuItems === []) {
            return $processedData;
        }

        // Process additional data processors
        $processedMenu = [];
        foreach ($menuItems as $key => $page) {
            $processedMenu[$key] = $this->processAdditionalDataProcessors($page, $processorConfiguration);
        }

        // Return processed data
        $processedData[$this->menuTargetVariableName] = $processedMenu;
        return $processedData;
    }

    /**
     * Process additional data processors
     */
    protected function processAdditionalDataProcessors(array $page, array $processorConfiguration): array
    {
        if (is_array($page['children'] ?? false)) {
            foreach ($page['children'] as $key => $item) {
                $page['children'][$key] = $this->processAdditionalDataProcessors($item, $processorConfiguration);
            }
        }
        $request = $this->cObj->getRequest();
        $recordContentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $recordContentObjectRenderer->setRequest($request);
        $recordContentObjectRenderer->start($page['data'] ?? [], 'pages');

        return $this->contentDataProcessor->process($recordContentObjectRenderer, $processorConfiguration, $page);
    }

}
