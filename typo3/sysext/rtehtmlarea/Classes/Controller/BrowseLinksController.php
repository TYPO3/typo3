<?php
namespace TYPO3\CMS\Rtehtmlarea\Controller;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
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
     * Active with TYPO3 Element Browser: Contains the name of the form field for which this window
     * opens - thus allows us to make references back to the main window in which the form is.
     * Example value: "data[pages][39][bodytext]|||tt_content|"
     * or "data[tt_content][NEW3fba56fde763d][image]|||gif,jpg,jpeg,tif,bmp,pcx,tga,png,pdf,ai|"
     *
     * Values:
     * 0: form field name reference, eg. "data[tt_content][123][image]"
     * 1: htmlArea RTE parameters: editorNo:contentTypo3Language
     * 2: RTE config parameters: RTEtsConfigParams
     * 3: allowed types. Eg. "tt_content" or "gif,jpg,jpeg,tif,bmp,pcx,tga,png,pdf,ai"
     *
     * $pArr = explode('|', $this->bparams);
     * $formFieldName = $pArr[0];
     * $allowedTablesOrFileTypes = $pArr[3];
     *
     * @var string
     */
    protected $bparams;

    /**
     * @var int
     */
    protected $editorNo;

    /**
     * TYPO3 language code of the content language
     *
     * @var int
     */
    protected $contentTypo3Language;

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
     * RTE configuration
     *
     * @var array
     */
    protected $RTEProperties = [];

    /**
     * Used with the Rich Text Editor.
     * Example value: "tt_content:NEW3fba58c969f5c:bodytext:23:text:23:"
     *
     * @var string
     */
    protected $RTEtsConfigParams;

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

        $lang = $this->getLanguageService();
        $lang->includeLLFile('EXT:rtehtmlarea/Resources/Private/Language/locallang_browselinkscontroller.xlf');
        $lang->includeLLFile('EXT:rtehtmlarea/Resources/Private/Language/locallang_dialogs.xlf');

        $this->contentLanguageService = GeneralUtility::makeInstance(LanguageService::class);
    }

    /**
     * @param ServerRequestInterface $request
     */
    protected function initVariables(ServerRequestInterface $request)
    {
        parent::initVariables($request);

        $queryParameters = $request->getQueryParams();
        $this->bparams = isset($queryParameters['bparams']) ? $queryParameters['bparams'] : '';

        $this->siteUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');

        $currentLinkParts = isset($queryParameters['curUrl']) ? $queryParameters['curUrl'] : [];
        if (isset($currentLinkParts['all'])) {
            $currentLinkParts = GeneralUtility::get_tag_attributes($queryParameters['curUrl']['all']);
            $currentLinkParts['url'] = htmlspecialchars_decode($currentLinkParts['href']);
            unset($currentLinkParts['href']);
        }
        $this->currentLinkParts = $currentLinkParts;

        // Process bparams
        $pArr = explode('|', $this->bparams);
        $pRteArr = explode(':', $pArr[1]);
        $this->editorNo = $pRteArr[0];
        $this->contentTypo3Language = $pRteArr[1];
        $this->RTEtsConfigParams = $pArr[2];
        if (!$this->editorNo) {
            $this->editorNo = GeneralUtility::_GP('editorNo');
            $this->contentTypo3Language = GeneralUtility::_GP('contentTypo3Language');
            $this->RTEtsConfigParams = GeneralUtility::_GP('RTEtsConfigParams');
        }
        $pArr[1] = implode(':', [$this->editorNo, $this->contentTypo3Language]);
        $pArr[2] = $this->RTEtsConfigParams;
        $this->bparams = implode('|', $pArr);

        $this->contentLanguageService->init($this->contentTypo3Language);

        $RTEtsConfigParts = explode(':', $this->RTEtsConfigParams);
        $RTEsetup = $this->getBackendUser()->getTSConfig('RTE', BackendUtility::getPagesTSconfig($RTEtsConfigParts[5]));
        $this->RTEProperties = $RTEsetup['properties'];

        $this->thisConfig = BackendUtility::RTEsetup($this->RTEProperties, $RTEtsConfigParts[0], $RTEtsConfigParts[2], $RTEtsConfigParts[4]);
        $this->buttonConfig = isset($this->thisConfig['buttons.']['link.'])
            ? $this->thisConfig['buttons.']['link.']
            : [];
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
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Rtehtmlarea/RteLinkBrowser');
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

        if (!empty($this->currentLinkParts['class'])) {
            // remove required classes
            $currentClasses = GeneralUtility::trimExplode(' ', $this->currentLinkParts['class'], true);
            if (count($currentClasses) > 1) {
                $this->currentLinkParts['class'] = end($currentClasses);
            }
        }

        if (empty($this->currentLinkParts['data-htmlarea-external']) && strpos($this->currentLinkParts['url'], 'mailto:') === false) {
            // strip siteUrl prefix except for external and mail links
            $paramsPosition = strpos($this->currentLinkParts['url'], '?');
            if ($paramsPosition !== false) {
                $this->currentLinkParts['url'] = substr($this->currentLinkParts['url'], $paramsPosition + 1);
            }
            // special treatment for page links, remove the id= part
            $idPosition = strpos($this->currentLinkParts['url'], 'id=');
            if ($idPosition !== false) {
                $this->currentLinkParts['url'] = substr($this->currentLinkParts['url'], $idPosition + 3);
            }

            // in RTE the additional params are encoded directly at the end of the href part
            // we need to split this again into dedicated fields
            $additionalParamsPosition = strpos($this->currentLinkParts['url'], '?');
            if ($additionalParamsPosition === false) {
                $additionalParamsPosition = strpos($this->currentLinkParts['url'], '&');
            }
            if ($additionalParamsPosition !== false) {
                $this->currentLinkParts['params'] = substr($this->currentLinkParts['url'], $additionalParamsPosition);
                $this->currentLinkParts['url'] = substr($this->currentLinkParts['url'], 0, $additionalParamsPosition);
                // in case the first sign was an ? override it with &
                $this->currentLinkParts['params'][0] = '&';
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
            if (is_array($this->RTEProperties['classesAnchor.'])) {
                foreach ($this->RTEProperties['classesAnchor.'] as $label => $conf) {
                    if (in_array($conf['class'], $classesAnchorArray)) {
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
                    $classLabel = !empty($this->RTEProperties['classes.'][$class . '.']['name'])
                        ? $this->getPageConfigLabel($this->RTEProperties['classes.'][$class . '.']['name'], 0)
                        : $class;
                    $classStyle = !empty($this->RTEProperties['classes.'][$class . '.']['value'])
                        ? $this->RTEProperties['classes.'][$class . '.']['value']
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
        // Initializing additional attributes
        if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['TYPO3Link']['additionalAttributes']) {
            $addAttributes = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['TYPO3Link']['additionalAttributes'], true);
            foreach ($addAttributes as $attribute) {
                $this->additionalAttributes[$attribute] = isset($this->linkAttributeValues[$attribute]) ? $this->linkAttributeValues[$attribute] : '';
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
        if (substr($string, 0, 4) !== 'LLL:') {
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
        $removeLink = '<a href="#" class="btn btn-default t3js-removeCurrentLink">' . $this->getLanguageService()->getLL('removeLink', true) . '</a>';
        return '
            <table border="0" cellpadding="0" cellspacing="0" id="typo3-curUrl">
                <tr>
                    <td>' . $this->getLanguageService()->getLL('currentLink', true) . ': ' . htmlspecialchars($this->currentLinkHandler->formatCurrentUrl()) . $removeLink . '</td>
                </tr>
            </table>';
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
        // @todo add rel to attributes
        $currentRel = $this->displayedLinkHandler === $this->currentLinkHandler && !empty($this->currentLinkParts)
            ? $this->linkAttributeValues['rel']
            : '';
        // @todo define label "linkRelationship" below in xlf
        return '
				<form action="" name="lrelform" id="lrelform" class="t3js-dummyform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkRel">
						<tr>
							<td><label>' . $this->getLanguageService()->getLL('linkRelationship', true) . ':</label></td>
							<td colspan="3"><input type="text" name="lrel" value="' . $currentRel . '" /></td>
						</tr>
					</table>
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
						<select name="ltarget_type" class="t3js-targetPreselect">
							<option value=""></option>
							<option value="_top">' . $lang->getLL('top', true) . '</option>
							<option value="_blank">' . $lang->getLL('newWindow', true) . '</option>
						</select>
			';
        }

        return '
				<form action="" name="ltargetform" id="ltargetform" class="t3js-dummyform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkTarget">
						<tr' . ($targetSelectorConfig['disabled'] ? ' style="display: none;"' : '') . '>
							<td style="width: 96px;">' . $lang->getLL('target', true) . ':</td>
							<td>
								<input type="text" name="ltarget" class="t3js-linkTarget" value="' . htmlspecialchars($target) . '" />
								' . $targetSelector . '
							</td>
						</tr>
					</table>
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
				<form action="" name="ltitleform" id="ltitleform" class="t3js-dummyform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkTitle">
						<tr>
							<td style="width: 96px;"><label for="rtehtmlarea-browse-links-anchor_title" id="rtehtmlarea-browse-links-title-label">' . $this->getLanguageService()->getLL('anchor_title', true) . '</label></td>
							<td>
								<span id="rtehtmlarea-browse-links-title-input" style="display: ' . ($readOnly ? 'none' : 'inline') . ';">
									<input type="text" id="rtehtmlarea-browse-links-anchor_title" name="ltitle" value="' . htmlspecialchars($title) . '" />
								</span>
								<span id="rtehtmlarea-browse-links-title-readonly" style="display: ' . ($readOnly ? 'inline' : 'none') . ';">' . htmlspecialchars($title) . '</span>
							</td>
						</tr>
					</table>
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
				<form action="" name="lclassform" id="lclassform" class="t3js-dummyform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkClass">
						<tr>
							<td style="width: 96px;">' . $this->getLanguageService()->getLL('anchor_class', true) . '</td>
							<td><select name="lclass" class="t3js-class-selector">
								' . $this->classesAnchorJSOptions[$this->displayedLinkHandlerId] . '
							</select></td>
						</tr>
					</table>
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
        return explode(':', $this->RTEtsConfigParams)[5];
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
            'bparams' => $this->bparams,
            'editorNo' => $this->editorNo,
            'contentTypo3Language' => $this->contentTypo3Language
        ];
    }
}
