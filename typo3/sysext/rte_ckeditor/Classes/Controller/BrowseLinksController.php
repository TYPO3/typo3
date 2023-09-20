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

namespace TYPO3\CMS\RteCKEditor\Controller;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\AbstractLinkBrowserController;
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewInterface;

/**
 * Extended controller for link browser
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class BrowseLinksController extends AbstractLinkBrowserController
{
    protected string $editorId;

    /**
     * TYPO3 language code of the content language
     */
    protected string $contentsLanguage;
    protected ?LanguageService $contentLanguageService;
    protected array $buttonConfig = [];
    protected array $thisConfig = [];
    protected array $classesAnchorDefault = [];
    protected array $classesAnchorDefaultTarget = [];
    protected array $classesAnchorJSOptions = [];
    protected string $defaultLinkTarget = '';
    protected array $additionalAttributes = [];
    protected string $siteUrl = '';

    public function __construct(
        protected readonly LinkService $linkService,
        protected readonly Richtext $richtext,
        protected readonly LanguageServiceFactory $languageServiceFactory,
    ) {
    }

    /**
     * This is only used by RTE currently.
     */
    public function getConfiguration(): array
    {
        return $this->buttonConfig;
    }

    /**
     * @return array{act: string, P: array, editorId: string, contentsLanguage: string} Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $overrides = null): array
    {
        return [
            'act' => $overrides['act'] ?? $this->displayedLinkHandlerId,
            'P' => $overrides['P'] ?? $this->parameters,
            'editorId' => $this->editorId,
            'contentsLanguage' => $this->contentsLanguage,
        ];
    }

    protected function initDocumentTemplate(): void
    {
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/rte-ckeditor/rte-link-browser.js')
                ->invoke('initialize', $this->editorId)
        );
    }

    protected function getCurrentPageId(): int
    {
        return (int)$this->parameters['pid'];
    }

    protected function initVariables(ServerRequestInterface $request): void
    {
        parent::initVariables($request);
        $queryParameters = $request->getQueryParams();
        $this->siteUrl = $request->getAttribute('normalizedParams')->getSiteUrl();
        $this->currentLinkParts = $queryParameters['P']['curUrl'] ?? [];
        $this->editorId = $queryParameters['editorId'];
        $this->contentsLanguage = $queryParameters['contentsLanguage'];
        $this->contentLanguageService = $this->languageServiceFactory->create($this->contentsLanguage);
        $tcaFieldConf = [
            'enableRichtext' => true,
            'richtextConfiguration' => $this->parameters['richtextConfigurationName'] ?: null,
        ];
        $this->thisConfig = $this->richtext->getConfiguration(
            $this->parameters['table'],
            $this->parameters['fieldName'],
            (int)$this->parameters['pid'],
            $this->parameters['recordType'],
            $tcaFieldConf
        );
        $this->buttonConfig = $this->thisConfig['buttons']['link'] ?? [];
    }

    protected function initCurrentUrl(): void
    {
        if (empty($this->currentLinkParts)) {
            return;
        }
        if (!empty($this->currentLinkParts['url'])) {
            $data = $this->linkService->resolve($this->currentLinkParts['url']);
            $this->currentLinkParts['type'] = $data['type'];
            unset($data['type']);
            $this->currentLinkParts['url'] = $data;
            if (!empty($this->currentLinkParts['url']['parameters'])) {
                $this->currentLinkParts['params'] = '&' . $this->currentLinkParts['url']['parameters'];
            }
        }
        parent::initCurrentUrl();
    }

    protected function renderLinkAttributeFields(ViewInterface $view): string
    {
        // Processing the classes configuration
        if (!empty($this->buttonConfig['properties']['class']['allowedClasses'])) {
            $classesAnchorArray = is_array($this->buttonConfig['properties']['class']['allowedClasses'])
                ? $this->buttonConfig['properties']['class']['allowedClasses']
                : GeneralUtility::trimExplode(',', $this->buttonConfig['properties']['class']['allowedClasses'], true);
            // Collecting allowed classes and configured default values
            $classesAnchor = [
                'all' => [],
            ];

            if (is_array($this->thisConfig['classesAnchor'] ?? null)) {
                foreach ($this->thisConfig['classesAnchor'] as $label => $conf) {
                    if (in_array($conf['class'] ?? null, $classesAnchorArray, true)) {
                        $classesAnchor['all'][] = $conf['class'];
                        if ($conf['type'] === $this->displayedLinkHandlerId) {
                            $classesAnchor[$conf['type']][] = $conf['class'];
                            if (($this->buttonConfig[$conf['type']]['properties']['class']['default'] ?? null) === $conf['class']) {
                                $this->classesAnchorDefault[$conf['type']] = $conf['class'];
                                if (isset($conf['target'])) {
                                    $this->classesAnchorDefaultTarget[$conf['type']] = trim((string)$conf['target']);
                                }
                            }
                        }
                    }
                }
            }
            if (isset($this->linkAttributeValues['class'])) {
                // Cleanup current link class value by removing any invalid class, including
                // the automatically applied highlighting class `ck-link_selected`.
                $linkClass = trim(str_replace('ck-link_selected', '', $this->linkAttributeValues['class']));
                if (in_array($linkClass, $classesAnchorArray, true)) {
                    $this->linkAttributeValues['class'] = $linkClass;
                } else {
                    unset($this->linkAttributeValues['class']);
                }
                if (isset($classesAnchor[$this->displayedLinkHandlerId])
                    && !in_array($linkClass, $classesAnchor[$this->displayedLinkHandlerId], true)
                ) {
                    unset($this->linkAttributeValues['class']);
                }
            }

            // Constructing the class selector options
            foreach ($classesAnchorArray as $class) {
                if (
                    !in_array($class, $classesAnchor['all'])
                    || (
                        in_array($class, $classesAnchor['all'])
                        && isset($classesAnchor[$this->displayedLinkHandlerId])
                        && is_array($classesAnchor[$this->displayedLinkHandlerId])
                        && in_array($class, $classesAnchor[$this->displayedLinkHandlerId])
                    )
                ) {
                    $selected = '';
                    if (
                        (($this->linkAttributeValues['class'] ?? false) === $class)
                        || ($this->classesAnchorDefault[$this->displayedLinkHandlerId] ?? false) === $class
                    ) {
                        $selected = 'selected="selected"';
                    }
                    $classLabel = !empty($this->thisConfig['classes'][$class]['name'])
                        ? $this->getPageConfigLabel($this->thisConfig['classes'][$class]['name'], false)
                        : $class;
                    $classStyle = !empty($this->thisConfig['classes'][$class]['value'])
                        ? $this->thisConfig['classes'][$class]['value']
                        : '';

                    $this->classesAnchorJSOptions[$this->displayedLinkHandlerId] ??= '';
                    $this->classesAnchorJSOptions[$this->displayedLinkHandlerId] .= '<option ' . $selected . ' value="' . htmlspecialchars($class) . '"'
                        . ($classStyle ? ' style="' . htmlspecialchars($classStyle) . '"' : '')
                        . '>' . htmlspecialchars($classLabel)
                        . '</option>';
                }
            }
            if (
                ($this->classesAnchorJSOptions[$this->displayedLinkHandlerId] ?? false)
                && !(
                    ($this->buttonConfig['properties']['class']['required'] ?? false)
                    || ($this->buttonConfig[$this->displayedLinkHandlerId]['properties']['class']['required'] ?? false)
                )
            ) {
                $selected = '';
                if (!($this->linkAttributeValues['class'] ?? false) && !($this->classesAnchorDefault[$this->displayedLinkHandlerId] ?? false)) {
                    $selected = 'selected="selected"';
                }
                $this->classesAnchorJSOptions[$this->displayedLinkHandlerId] = '<option ' . $selected . ' value=""></option>' . $this->classesAnchorJSOptions[$this->displayedLinkHandlerId];
            }
        }
        // Default target
        $this->defaultLinkTarget = ($this->classesAnchorDefault[$this->displayedLinkHandlerId] ?? false) && ($this->classesAnchorDefaultTarget[$this->displayedLinkHandlerId] ?? false)
            ? $this->classesAnchorDefaultTarget[$this->displayedLinkHandlerId]
            : ($this->buttonConfig[$this->displayedLinkHandlerId]['properties']['target']['default'] ?? $this->buttonConfig['properties']['target']['default'] ?? '');

        // todo: find new name for this option
        // Initializing additional attributes
        if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rte_ckeditor']['plugins']['TYPO3Link']['additionalAttributes'] ?? false) {
            $addAttributes = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rte_ckeditor']['plugins']['TYPO3Link']['additionalAttributes'], true);
            foreach ($addAttributes as $attribute) {
                $this->additionalAttributes[$attribute] = $this->linkAttributeValues[$attribute] ?? '';
            }
        }
        return parent::renderLinkAttributeFields($view);
    }

    /**
     * Localize a label obtained from Page TSConfig
     *
     * @param string $string The label to be localized
     * @param bool $JScharCode If it needs to be converted to an array of char numbers
     * @return string Localized string
     */
    protected function getPageConfigLabel(string $string, bool $JScharCode = true): string
    {
        $label = $this->getLanguageService()->sL(trim($string));
        $label = str_replace(['\\\'', '"'], ['\'', '\\"'], $label);
        return $JScharCode ? GeneralUtility::quoteJSvalue($label) : $label;
    }

    protected function renderCurrentUrl(ViewInterface $view): void
    {
        $view->assign('removeCurrentLink', true);
        parent::renderCurrentUrl($view);
    }

    /**
     * @return string[]
     */
    protected function getAllowedItems(): array
    {
        $allowedItems = parent::getAllowedItems();

        if (isset($this->thisConfig['allowedTypes'])) {
            $allowedItems = array_intersect($allowedItems, GeneralUtility::trimExplode(',', $this->thisConfig['allowedTypes'], true));
        } elseif (isset($this->thisConfig['blindLinkOptions'])) {
            // @todo Deprecate this option
            $allowedItems = array_diff($allowedItems, GeneralUtility::trimExplode(',', $this->thisConfig['blindLinkOptions'], true));
        }

        if (is_array($this->buttonConfig['options'] ?? null) && !empty($this->buttonConfig['options']['removeItems'])) {
            $allowedItems = array_diff($allowedItems, GeneralUtility::trimExplode(',', $this->buttonConfig['options']['removeItems'], true));
        }

        return $allowedItems;
    }

    /**
     * @return string[]
     */
    protected function getAllowedLinkAttributes(): array
    {
        $allowedLinkAttributes = parent::getAllowedLinkAttributes();

        if (isset($this->thisConfig['allowedOptions'])) {
            $allowedLinkAttributes = array_intersect($allowedLinkAttributes, GeneralUtility::trimExplode(',', $this->thisConfig['allowedOptions'], true));
        } elseif (isset($this->thisConfig['blindLinkFields'])) {
            // @todo Deprecate this option
            $allowedLinkAttributes = array_diff($allowedLinkAttributes, GeneralUtility::trimExplode(',', $this->thisConfig['blindLinkFields'], true));
        }

        return $allowedLinkAttributes;
    }

    /**
     * Create an array of link attribute field rendering definitions
     *
     * @return string[]
     */
    protected function getLinkAttributeFieldDefinitions(): array
    {
        $fieldRenderingDefinitions = parent::getLinkAttributeFieldDefinitions();
        $fieldRenderingDefinitions['class'] = $this->getClassField();
        $fieldRenderingDefinitions['target'] = $this->getTargetField();
        $fieldRenderingDefinitions['rel'] = $this->getRelField();
        if (empty($this->buttonConfig['queryParametersSelector']['enabled'])) {
            unset($fieldRenderingDefinitions['params']);
        }
        return $fieldRenderingDefinitions;
    }

    protected function getRelField(): string
    {
        if (empty($this->buttonConfig['relAttribute']['enabled'])) {
            return '';
        }

        $currentRel = '';
        if ($this->displayedLinkHandler === $this->currentLinkHandler
            && !empty($this->currentLinkParts)
            && isset($this->linkAttributeValues['rel'])
            && is_string($this->linkAttributeValues['rel'])
        ) {
            $currentRel = $this->linkAttributeValues['rel'];
        }

        return '
            <div class="element-browser-form-group">
                <label for="lrel" class="form-label">' .
                    htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:linkRelationship')) .
                '</label>
                <input type="text" name="lrel" class="form-control" value="' . htmlspecialchars($currentRel) . '" />
            </div>
            ';
    }

    protected function getTargetField(): string
    {
        $targetSelectorConfig = [];
        if (is_array($this->buttonConfig['targetSelector'] ?? null)) {
            $targetSelectorConfig = $this->buttonConfig['targetSelector'];
        }
        $target = !empty($this->linkAttributeValues['target']) ? $this->linkAttributeValues['target'] : $this->defaultLinkTarget;
        $lang = $this->getLanguageService();

        $disabled = $targetSelectorConfig['disabled'] ?? false;
        if ($disabled) {
            return '';
        }

        return '
            <div class="element-browser-form-group">
                <label for="ltarget" class="form-label">
                    ' . htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:target')) . '
                </label>
                <span class="input-group">
                    <input id="ltarget" type="text" name="ltarget" class="t3js-linkTarget form-control"
                        value="' . htmlspecialchars($target) . '" />
                    <select name="ltarget_type" class="t3js-targetPreselect form-select">
                        <option value=""></option>
                        <option value="_top">' . htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:top')) . '</option>
                        <option value="_blank">' . htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:newWindow')) . '</option>
                    </select>
                </span>
            </div>';
    }

    /**
     * Return html code for the class selector
     *
     * @return string the html code to be added to the form
     */
    protected function getClassField(): string
    {
        if (!isset($this->classesAnchorJSOptions[$this->displayedLinkHandlerId])) {
            return '';
        }

        return '
            <div class="element-browser-form-group">
                <label for="lclass" class="form-label">
                    ' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:class')) . '
                </label>
                <select id="lclass" name="lclass" class="t3js-class-selector form-select">
                    ' . $this->classesAnchorJSOptions[$this->displayedLinkHandlerId] . '
                </select>
            </div>
        ';
    }

    /**
     * @return string[] Array of body-tag attributes
     */
    protected function getBodyTagAttributes(): array
    {
        $parameters = parent::getBodyTagAttributes();
        $parameters['data-site-url'] = $this->siteUrl;
        $parameters['data-default-link-target'] = $this->defaultLinkTarget;
        return $parameters;
    }
}
