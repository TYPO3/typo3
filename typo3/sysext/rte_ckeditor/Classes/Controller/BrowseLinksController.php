<?php
declare(strict_types=1);
namespace TYPO3\CMS\RteCKEditor\Controller;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Recordlist\Controller\AbstractLinkBrowserController;

/**
 * Extended controller for link browser
 */
class BrowseLinksController extends AbstractLinkBrowserController
{
    /**
     * @var string
     */
    protected $editorId;

    /**
     * TYPO3 language code of the content language
     *
     * @var int
     */
    protected $contentsLanguage;

    /**
     * Language service object for localization to the content language
     *
     * @var LanguageService
     */
    protected $contentLanguageService;

    /**
     * @var array
     */
    protected $buttonConfig = [];

    /**
     * @var array
     */
    protected $thisConfig = [];

    /**
     * @var array
     */
    protected $classesAnchorDefault = [];

    /**
     * @var array
     */
    protected $classesAnchorDefaultTitle = [];

    /**
     * @var array
     */
    protected $classesAnchorClassTitle = [];

    /**
     * @var array
     */
    protected $classesAnchorDefaultTarget = [];

    /**
     * @var array
     */
    protected $classesAnchorJSOptions = [];

    /**
     * @var string
     */
    protected $defaultLinkTarget = '';

    /**
     * @var array
     */
    protected $additionalAttributes = [];

    /**
     * @var string
     */
    protected $siteUrl = '';

    /**
     * Initialize controller
     */
    protected function init()
    {
        parent::init();

        $this->contentLanguageService = GeneralUtility::makeInstance(LanguageService::class);
    }

    /**
     * @param ServerRequestInterface $request
     */
    protected function initVariables(ServerRequestInterface $request)
    {
        parent::initVariables($request);

        $queryParameters = $request->getQueryParams();

        $this->siteUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');

        $this->currentLinkParts = $queryParameters['curUrl'] ?? [];
        $this->editorId = $queryParameters['editorId'];
        $this->contentsLanguage = $queryParameters['contentsLanguage'];
        $this->RTEtsConfigParams = $queryParameters['RTEtsConfigParams'] ?? null;

        $this->contentLanguageService->init($this->contentsLanguage);

        /** @var Richtext $richtextConfigurationProvider */
        $richtextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
        $this->thisConfig = $richtextConfigurationProvider->getConfiguration(
            $this->parameters['table'],
            $this->parameters['fieldName'],
            (int)$this->parameters['pid'],
            $this->parameters['recordType'],
            ['richtext' => true]
        );
        $this->buttonConfig = $this->thisConfig['buttons.']['link.'] ?? [];
    }

    /**
     * Initialize document template object
     *
     *  @return void
     */
    protected function initDocumentTemplate()
    {
        parent::initDocumentTemplate();

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule(
            'TYPO3/CMS/RteCkeditor/RteLinkBrowser',
            'function(RteLinkBrowser) {
                RteLinkBrowser.initialize(' . GeneralUtility::quoteJSvalue($this->editorId) . ');
            }'
        );
    }

    /**
     * Initialize $this->currentLink and $this->currentLinkHandler
     *
     * @return void
     */
    protected function initCurrentUrl()
    {
        if (empty($this->currentLinkParts)) {
            return;
        }

        if (!empty($this->currentLinkParts['url'])) {
            $linkService = GeneralUtility::makeInstance(LinkService::class);
            $data = $linkService->resolve($this->currentLinkParts['url']);
            $this->currentLinkParts['type'] = $data['type'];
            unset($data['type']);
            $this->currentLinkParts['url'] = $data;
        }

        if (!empty($this->currentLinkParts['class'])) {
            // Only keep last class value (others are automatically added again by required option)
            // https://review.typo3.org/#/c/29643
            $currentClasses = GeneralUtility::trimExplode(' ', $this->currentLinkParts['class'], true);
            if (count($currentClasses) > 1) {
                $this->currentLinkParts['class'] = end($currentClasses);
            }
        }
        parent::initCurrentUrl();
    }

    /**
     * Renders the link attributes for the selected link handler
     *
     * @return string
     */
    public function renderLinkAttributeFields()
    {
        // Processing the classes configuration
        if (!empty($this->buttonConfig['properties.']['class.']['allowedClasses'])) {
            $classesAnchorArray = GeneralUtility::trimExplode(',', $this->buttonConfig['properties.']['class.']['allowedClasses'], true);
            // Collecting allowed classes and configured default values
            $classesAnchor = [
                'all' => []
            ];
            $titleReadOnly = $this->buttonConfig['properties.']['title.']['readOnly']
                || $this->buttonConfig[$this->displayedLinkHandlerId . '.']['properties.']['title.']['readOnly'];
            if (is_array($this->thisConfig['classesAnchor.'])) {
                foreach ($this->thisConfig['classesAnchor.'] as $label => $conf) {
                    if (in_array($conf['class'], $classesAnchorArray, true)) {
                        $classesAnchor['all'][] = $conf['class'];
                        if ($conf['type'] === $this->displayedLinkHandlerId) {
                            $classesAnchor[$conf['type']][] = $conf['class'];
                            if ($this->buttonConfig[$conf['type'] . '.']['properties.']['class.']['default'] == $conf['class']) {
                                $this->classesAnchorDefault[$conf['type']] = $conf['class'];
                                if ($conf['titleText']) {
                                    $this->classesAnchorDefaultTitle[$conf['type']] = $this->contentLanguageService->sL(trim($conf['titleText']));
                                }
                                if (isset($conf['target'])) {
                                    $this->classesAnchorDefaultTarget[$conf['type']] = trim($conf['target']);
                                }
                            }
                        }
                        if ($titleReadOnly && $conf['titleText']) {
                            $this->classesAnchorClassTitle[$conf['class']] = ($this->classesAnchorDefaultTitle[$conf['type']] = $this->contentLanguageService->sL(trim($conf['titleText'])));
                        }
                    }
                }
            }
            if (isset($this->linkAttributeValues['class'])
                && isset($classesAnchor[$this->displayedLinkHandlerId])
                && !in_array($this->linkAttributeValues['class'], $classesAnchor[$this->displayedLinkHandlerId], true)
            ) {
                unset($this->linkAttributeValues['class']);
            }
            // Constructing the class selector options
            foreach ($classesAnchorArray as $class) {
                if (!in_array($class, $classesAnchor['all']) || in_array($class, $classesAnchor['all']) && is_array($classesAnchor[$this->displayedLinkHandlerId]) && in_array($class, $classesAnchor[$this->displayedLinkHandlerId])) {
                    $selected = '';
                    if ($this->linkAttributeValues['class'] === $class || !$this->linkAttributeValues['class'] && $this->classesAnchorDefault[$this->displayedLinkHandlerId] == $class) {
                        $selected = 'selected="selected"';
                    }
                    $classLabel = !empty($this->thisConfig['classes.'][$class . '.']['name'])
                        ? $this->getPageConfigLabel($this->thisConfig['classes.'][$class . '.']['name'], 0)
                        : $class;
                    $classStyle = !empty($this->thisConfig['classes.'][$class . '.']['value'])
                        ? $this->thisConfig['classes.'][$class . '.']['value']
                        : '';
                    $this->classesAnchorJSOptions[$this->displayedLinkHandlerId] .= '<option ' . $selected . ' value="' . $class . '"' . ($classStyle ? ' style="' . $classStyle . '"' : '') . '>' . $classLabel . '</option>';
                }
            }
            if ($this->classesAnchorJSOptions[$this->displayedLinkHandlerId] && !($this->buttonConfig['properties.']['class.']['required'] || $this->buttonConfig[$this->displayedLinkHandlerId . '.']['properties.']['class.']['required'])) {
                $selected = '';
                if (!$this->linkAttributeValues['class'] && !$this->classesAnchorDefault[$this->displayedLinkHandlerId]) {
                    $selected = 'selected="selected"';
                }
                $this->classesAnchorJSOptions[$this->displayedLinkHandlerId] = '<option ' . $selected . ' value=""></option>' . $this->classesAnchorJSOptions[$this->displayedLinkHandlerId];
            }
        }
        // Default target
        $this->defaultLinkTarget = $this->classesAnchorDefault[$this->displayedLinkHandlerId] && $this->classesAnchorDefaultTarget[$this->displayedLinkHandlerId]
            ? $this->classesAnchorDefaultTarget[$this->displayedLinkHandlerId]
            : (isset($this->buttonConfig[$this->displayedLinkHandlerId . '.']['properties.']['target.']['default'])
                ? $this->buttonConfig[$this->displayedLinkHandlerId . '.']['properties.']['target.']['default']
                : (isset($this->buttonConfig['properties.']['target.']['default'])
                    ? $this->buttonConfig['properties.']['target.']['default']
                    : ''));

        // todo: find new name for this option
        // Initializing additional attributes
        if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rte_ckeditor']['plugins']['TYPO3Link']['additionalAttributes']) {
            $addAttributes = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rte_ckeditor']['plugins']['TYPO3Link']['additionalAttributes'], true);
            foreach ($addAttributes as $attribute) {
                $this->additionalAttributes[$attribute] = $this->linkAttributeValues[$attribute] ?? '';
            }
        }
        return parent::renderLinkAttributeFields();
    }

    /**
     * Localize a label obtained from Page TSConfig
     *
     * @param string $string The label to be localized
     * @param bool $JScharCode If needs to be converted to an array of char numbers
     * @return string Localized string
     */
    public function getPageConfigLabel($string, $JScharCode = true)
    {
        if (strpos($string, 'LLL:') !== 0) {
            $label = $string;
        } else {
            $label = $this->getLanguageService()->sL(trim($string));
        }
        $label = str_replace('"', '\\"', str_replace('\\\'', '\'', $label));
        return $JScharCode ? GeneralUtility::quoteJSvalue($label) : $label;
    }

    /**
     * @return string
     */
    protected function renderCurrentUrl()
    {
        $removeLink = ' <a href="#" class="btn btn-default t3js-removeCurrentLink">' . htmlspecialchars($this->getLanguageService()->getLL('removeLink')) . '</a>';
        return '
            <div class="link-browser-section link-browser-current-link">
                <strong>' .
                    htmlspecialchars($this->getLanguageService()->getLL('currentLink')) .
                    ': ' .
                    htmlspecialchars($this->currentLinkHandler->formatCurrentUrl()) .
                '</strong>' .
                '<span class="pull-right">' . $removeLink . '</span>' .
            '</div>';
    }

    /**
     * Get the allowed items or tabs
     *
     * @return string[]
     */
    protected function getAllowedItems()
    {
        $allowedItems = parent::getAllowedItems();

        $blindLinkOptions = isset($this->thisConfig['blindLinkOptions'])
            ? GeneralUtility::trimExplode(',', $this->thisConfig['blindLinkOptions'], true)
            : [];
        $allowedItems = array_diff($allowedItems, $blindLinkOptions);

        if (is_array($this->buttonConfig['options.']) && $this->buttonConfig['options.']['removeItems']) {
            $allowedItems = array_diff($allowedItems, GeneralUtility::trimExplode(',', $this->buttonConfig['options.']['removeItems'], true));
        }

        return $allowedItems;
    }

    /**
     * Get the allowed link attributes
     *
     * @return string[]
     */
    protected function getAllowedLinkAttributes()
    {
        $allowedLinkAttributes = parent::getAllowedLinkAttributes();

        $blindLinkFields = isset($this->thisConfig['blindLinkFields'])
            ? GeneralUtility::trimExplode(',', $this->thisConfig['blindLinkFields'], true)
            : [];
        $allowedLinkAttributes = array_diff($allowedLinkAttributes, $blindLinkFields);

        return $allowedLinkAttributes;
    }

    /**
     * Create an array of link attribute field rendering definitions
     *
     * @return string[]
     */
    protected function getLinkAttributeFieldDefinitions()
    {
        $fieldRenderingDefinitions = parent::getLinkAttributeFieldDefinitions();
        $fieldRenderingDefinitions['title'] = $this->getTitleField();
        $fieldRenderingDefinitions['class'] = $this->getClassField();
        $fieldRenderingDefinitions['target'] = $this->getTargetField();
        $fieldRenderingDefinitions['rel'] = $this->getRelField();
        if (empty($this->buttonConfig['queryParametersSelector.']['enabled'])) {
            unset($fieldRenderingDefinitions['params']);
        }
        return $fieldRenderingDefinitions;
    }

    /**
     * Add rel field
     *
     * @return string
     */
    protected function getRelField()
    {
        if (empty($this->buttonConfig['relAttribute.']['enabled'])) {
            return '';
        }

        $currentRel = $this->displayedLinkHandler === $this->currentLinkHandler && !empty($this->currentLinkParts)
            ? $this->linkAttributeValues['rel']
            : '';

        return '
            <form action="" name="lrelform" id="lrelform" class="t3js-dummyform form-horizontal">
                 <div class="form-group form-group-sm">
                    <label class="col-xs-4 control-label">' .
                        htmlspecialchars($this->getLanguageService()->getLL('linkRelationship')) .
                    '</label>
                    <div class="col-xs-8">
                        <input type="text" name="lrel" class="form-control" value="' . $currentRel . '" />
                    </div>
                </div>
            </form>
            ';
    }

    /**
     * Add target selector
     *
     * @return string
     */
    protected function getTargetField()
    {
        $targetSelectorConfig = [];
        if (is_array($this->buttonConfig['targetSelector.'])) {
            $targetSelectorConfig = $this->buttonConfig['targetSelector.'];
        }
        $target = $this->linkAttributeValues['target'] ?: $this->defaultLinkTarget;
        $lang = $this->getLanguageService();
        $targetSelector = '';

        if (!$targetSelectorConfig['disabled']) {
            $targetSelector = '
						<select name="ltarget_type" class="t3js-targetPreselect form-control">
							<option value=""></option>
							<option value="_top">' . htmlspecialchars($lang->getLL('top')) . '</option>
							<option value="_blank">' . htmlspecialchars($lang->getLL('newWindow')) . '</option>
						</select>
			';
        }

        return '
				<form action="" name="ltargetform" id="ltargetform" class="t3js-dummyform form-horizontal">
                    <div class="form-group form-group-sm" ' . ($targetSelectorConfig['disabled'] ? ' style="display: none;"' : '') . '>
                        <label class="col-xs-4 control-label">' . htmlspecialchars($lang->getLL('target')) . '</label>
						<div class="col-xs-4">
							<input type="text" name="ltarget" class="t3js-linkTarget form-control"
							    value="' . htmlspecialchars($target) . '" />
						</div>
						<div class="col-xs-4">
							' . $targetSelector . '
						</div>
					</div>
				</form>
				';
    }

    /**
     * Add title selector
     *
     * @return string
     */
    protected function getTitleField()
    {
        if ($this->linkAttributeValues['title']) {
            $title = $this->linkAttributeValues['title'];
        } else {
            $title = !$this->classesAnchorDefault[$this->displayedLinkHandlerId] ? '' : $this->classesAnchorDefaultTitle[$this->displayedLinkHandlerId];
        }
        if (isset($this->buttonConfig[$this->displayedLinkHandlerId . '.']['properties.']['title.']['readOnly'])) {
            $readOnly = (bool)$this->buttonConfig[$this->displayedLinkHandlerId . '.']['properties.']['title.']['readOnly'];
        } else {
            $readOnly = isset($this->buttonConfig['properties.']['title.']['readOnly'])
                ? (bool)$this->buttonConfig['properties.']['title.']['readOnly']
                : false;
        }

        if ($readOnly) {
            $currentClass = $this->linkAttributeFields['class'];
            if (!$currentClass) {
                $currentClass = empty($this->classesAnchorDefault[$this->displayedLinkHandlerId]) ? '' : $this->classesAnchorDefault[$this->displayedLinkHandlerId];
            }
            $title = $currentClass
                ? $this->classesAnchorClassTitle[$currentClass]
                : $this->classesAnchorDefaultTitle[$this->displayedLinkHandlerId];
        }
        return '
                <form action="" name="ltitleform" id="ltitleform" class="t3js-dummyform form-horizontal">
                    <div class="form-group form-group-sm">
                        <label class="col-xs-4 control-label">
                            ' . htmlspecialchars($this->getLanguageService()->getLL('title')) . '
                         </label>
                         <div class="col-xs-8">
                                <span style="display: ' . ($readOnly ? 'none' : 'inline') . ';">
                                    <input type="text" name="ltitle" class="form-control"
                                        value="' . htmlspecialchars($title) . '" />
                                </span>
                                <span id="rte-ckeditor-browse-links-title-readonly"
                                    style="display: ' . ($readOnly ? 'inline' : 'none') . ';">
                                    ' . htmlspecialchars($title) . '</span>
                        </div>
                    </div>
                </form>
                ';
    }

    /**
     * Return html code for the class selector
     *
     * @return string the html code to be added to the form
     */
    protected function getClassField()
    {
        $selectClass = '';
        if ($this->classesAnchorJSOptions[$this->displayedLinkHandlerId]) {
            $selectClass = '
                <form action="" name="lclassform" id="lclassform" class="t3js-dummyform form-horizontal">
                    <div class="form-group form-group-sm">
                        <label class="col-xs-4 control-label">
                            ' . htmlspecialchars($this->getLanguageService()->getLL('class')) . '
                        </label>
                        <div class="col-xs-8">
                            <select name="lclass" class="t3js-class-selector form-control">
                                ' . $this->classesAnchorJSOptions[$this->displayedLinkHandlerId] . '
                            </select>
                        </div>
                    </div>
                </form>
            ';
        }
        return $selectClass;
    }

    /**
     * Return the ID of current page
     *
     * @return int
     */
    protected function getCurrentPageId()
    {
        return (int)$this->parameters['pid'];
    }

    /**
     * Retrieve the configuration
     *
     * This is only used by RTE currently.
     *
     * @return array
     */
    public function getConfiguration()
    {
        return $this->buttonConfig;
    }

    /**
     * Get attributes for the body tag
     *
     * @return string[] Array of body-tag attributes
     */
    protected function getBodyTagAttributes()
    {
        $parameters = parent::getBodyTagAttributes();
        $parameters['data-site-url'] = $this->siteUrl;
        $parameters['data-default-link-target'] = $this->defaultLinkTarget;
        return $parameters;
    }

    /**
     * @param array $overrides
     *
     * @return array Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $overrides = null)
    {
        return [
            'act' => isset($overrides['act']) ? $overrides['act'] : $this->displayedLinkHandlerId,
            'editorId' => $this->editorId,
            'contentsLanguage' => $this->contentsLanguage,
            'P' => $this->parameters
        ];
    }
}
