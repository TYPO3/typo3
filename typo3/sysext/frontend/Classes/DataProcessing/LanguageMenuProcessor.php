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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\ContentObject\Menu\MenuContentObjectFactory;
use TYPO3\CMS\Frontend\Utility\CanonicalizationUtility;

/**
 * This menu processor generates a language menu array that will be
 * assigned to FLUIDTEMPLATE as variable.
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
    protected ContentObjectRenderer $cObj;
    protected array $processorConfiguration;

    /**
     * Allowed configuration keys for menu generation, other keys
     * will throw an exception to prevent configuration errors.
     */
    protected array $allowedConfigurationKeys = [
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
     * to the menu to prevent configuration errors
     */
    protected array $removeConfigurationKeysForHmenu = [
        'languages',
        'languages.',
        'as',
    ];

    protected array $menuConfig = [
        'special' => 'language',
        'addQueryString' => 1,
    ];

    protected array $menuDefaults = [
        'as' => 'languagemenu',
    ];

    public function __construct(
        protected readonly MenuContentObjectFactory $menuContentObjectFactory,
    ) {}

    /**
     * Get configuration value from processorConfiguration
     */
    protected function getConfigurationValue(string $key): string
    {
        return $this->cObj->stdWrapValue($key, $this->processorConfiguration, $this->menuDefaults[$key] ?? '');
    }

    protected function getRequest(): ServerRequestInterface
    {
        return $this->cObj->getRequest();
    }

    /**
     * Returns the currently configured "site" if a site is configured (= resolved) in the current request.
     */
    protected function getCurrentSite(): Site
    {
        return $this->getRequest()->getAttribute('site');
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function validateConfiguration(): void
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
            $this->getRequest()->getAttribute('frontend.page.information')->getId(),
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
     * Build the menu configuration so it can be treated by TMENU
     */
    protected function buildConfiguration(): void
    {
        $this->menuConfig['1'] = 'TMENU';
        $this->menuConfig['1.']['NO'] = '1';
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

        // Validate Configuration
        $this->validateConfiguration();

        // Build Configuration
        $this->prepareConfiguration();
        $this->buildConfiguration();

        // Create menu object and get menu items directly
        $request = $cObj->getRequest();
        $site = $this->getCurrentSite();

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

        // Enrich with language-specific fields
        $processedMenu = [];
        foreach ($menuItems as $key => $item) {
            $languageId = (int)($item['data']['_REQUESTED_OVERLAY_LANGUAGE'] ?? 0);
            try {
                $languageObject = $site->getLanguageById($languageId);
            } catch (\InvalidArgumentException) {
                // Language not found in site config
                continue;
            }
            $item['languageId'] = $languageId;
            $item['locale'] = $languageObject->getLocale()->getName();
            // Override title with language title (not page title)
            $item['title'] = $languageObject->getTitle();
            $item['navigationTitle'] = $languageObject->getNavigationTitle();
            $item['twoLetterIsoCode'] = $languageObject->getLocale()->getLanguageCode();
            $item['hreflang'] = $languageObject->getHreflang();
            $item['direction'] = $languageObject->getLocale()->isRightToLeftLanguageDirection() ? 'rtl' : 'ltr';
            $item['flag'] = $languageObject->getFlagIdentifier();
            // Determine state from ITEM_STATE set by the menu system
            $itemState = $item['data']['ITEM_STATE'] ?? '';
            // active = 1 if state is ACT, ACTIFSUB, USERDEF2 (active states)
            $item['active'] = in_array($itemState, ['ACT', 'ACTIFSUB', 'USERDEF2'], true) ? 1 : 0;
            // current = 1 if state is CUR, CURIFSUB (current language)
            $item['current'] = in_array($itemState, ['CUR', 'CURIFSUB'], true) ? 1 : 0;
            // available = 1 unless USERDEF1/USERDEF2 state (language not available)
            $item['available'] = !in_array($itemState, ['USERDEF1', 'USERDEF2'], true) ? 1 : 0;
            $processedMenu[$key] = $item;
        }

        // Return processed data
        $processedData[$this->getConfigurationValue('as')] = $processedMenu;
        return $processedData;
    }
}
