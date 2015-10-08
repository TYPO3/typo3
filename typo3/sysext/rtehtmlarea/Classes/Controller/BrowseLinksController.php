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
use TYPO3\CMS\Recordlist\Controller\LinkBrowserController;
use TYPO3\CMS\Rtehtmlarea\LinkHandler\RemoveLinkHandler;

/**
 * Extended controller for link browser
 */
class BrowseLinksController extends LinkBrowserController
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

        $parameters = $request->getQueryParams();
        $this->bparams = isset($parameters['bparams']) ? $parameters['bparams'] : '';

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
        $pArr[1] = implode(':', array($this->editorNo, $this->contentTypo3Language));
        $pArr[2] = $this->RTEtsConfigParams;
        $this->bparams = implode('|', $pArr);

        $this->contentLanguageService->init($this->contentTypo3Language);
        $this->buttonConfig = isset($this->RTEProperties['default.']['buttons.']['link.'])
            ? $this->RTEProperties['default.']['buttons.']['link.']
            : [];

        $RTEtsConfigParts = explode(':', $this->RTEtsConfigParams);
        $RTEsetup = $this->getBackendUser()->getTSConfig('RTE', BackendUtility::getPagesTSconfig($RTEtsConfigParts[5]));
        $this->thisConfig = BackendUtility::RTEsetup($RTEsetup['properties'], $RTEtsConfigParts[0], $RTEtsConfigParts[2], $RTEtsConfigParts[4]);
    }

    /**
     * Initialize document template object
     *
     *  @return void
     */
    protected function initDocumentTemplate()
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Rtehtmlarea/RteLinkBrowser');
    }

    /**
     * Get the allowed items or tabs
     *
     * @return string[]
     */
    protected function getAllowedItems()
    {
        $allowedItems = parent::getAllowedItems();

        // do not show the "removeLink" item if there is no current link
        if (!$this->currentLink) {
            $position = array_search('removeLink', $allowedItems, true);
            if ($position !== false) {
                unset($allowedItems[$position]);
            }
        }

        $blindLinkOptions = isset($this->RTEProperties['default.']['blindLinkOptions'])
            ? GeneralUtility::trimExplode(',', $this->RTEProperties['default.']['blindLinkOptions'], true)
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

        $blindLinkFields = isset($this->RTEProperties['default.']['blindLinkFields'])
            ? GeneralUtility::trimExplode(',', $this->RTEProperties['default.']['blindLinkFields'], true)
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
        $fieldRenderingDefinitions['params'] = $this->getParamsField();
        $fieldRenderingDefinitions['rel'] = $this->getRelField();
    }


    /**
     * Add rel field
     *
     * @return string
     */
    protected function getRelField()
    {
        // Unset rel attribute if we changed tab
        $currentRel = $this->curUrlInfo['act'] === $this->act && isset($this->curUrlArray['rel']) ? $this->curUrlArray['rel'] : '';
        if (($this->act === 'page' || $this->act === 'url' || $this->act === 'file')
            && isset($this->buttonConfig['relAttribute.']['enabled']) && $this->buttonConfig['relAttribute.']['enabled']
        ) {
            return '
						<tr>
							<td><label>' . $this->getLanguageService()->getLL('linkRelationship', true) . ':</label></td>
							<td colspan="3">
								<input type="text" name="lrel" value="' . $currentRel . '"  '
            . $this->doc->formWidth(30) . ' />
							</td>
						</tr>';
        }
        return '';
    }


    /**
     * Add query parameter selector
     *
     * @return string
     */
    protected function getParamsField()
    {
        if (empty($this->buttonConfig['queryParametersSelector.']['enabled'])) {
            return '';
        }
        return '
			<tr>
				<td><label>' . $this->getLanguageService()->getLL('query_parameters', true) . ':</label></td>
				<td colspan="3">
					<input type="text" name="query_parameters" value="' . ($this->curUrlInfo['query'] ?: '') . '" ' . $this->doc->formWidth(30) . ' />
				</td>
			</tr>';
    }

    /**
     * Add target selector
     *
     * @return string
     */
    protected function getTargetField()
    {
        $targetSelectorConfig = array();
        if (is_array($this->buttonConfig['targetSelector.'])) {
            $targetSelectorConfig = $this->buttonConfig['targetSelector.'];
        }
        // Reset the target to default if we changed tab
        $currentTarget = $this->curUrlInfo['act'] === $this->act && isset($this->curUrlArray['target']) ? $this->curUrlArray['target'] : '';
        $target = $currentTarget ?: $this->defaultLinkTarget;
        $lang = $this->getLanguageService();
        $ltarget = '
				<tr id="ltargetrow"' . ($targetSelectorConfig['disabled'] ? ' style="display: none;"' : '') . '>
					<td><label>' . $lang->getLL('target', true) . ':</label></td>
					<td><input type="text" name="ltarget" onchange="browse_links_setTarget(this.value);" value="'
            . htmlspecialchars($target) . '"' . $this->doc->formWidth(10) . ' /></td>';
        $ltarget .= '
					<td colspan="2">';
        if (!$targetSelectorConfig['disabled']) {
            $ltarget .= '
						<select name="ltarget_type" onchange="browse_links_setTarget(this.options[this.selectedIndex].value);document.ltargetform.ltarget.value=this.options[this.selectedIndex].value;this.selectedIndex=0;">
							<option></option>
							<option value="_top">' . $lang->getLL('top', true) . '</option>
							<option value="_blank">' . $lang->getLL('newWindow', true) . '</option>
						</select>';
        }
        $ltarget .= '
					</td>
				</tr>';
        return $ltarget;
    }


    /**
     * Add title selector
     *
     * @return string
     */
    protected function getTitleField()
    {
        // Reset the title to default if we changed tab
        $currentTitle = $this->curUrlInfo['act'] === $this->act && isset($this->curUrlArray['title']) ? $this->curUrlArray['title'] : '';
        $title = $currentTitle ?: (!$this->classesAnchorDefault[$this->act] ? '' : $this->classesAnchorDefaultTitle[$this->act]);
        $readOnly = isset($this->buttonConfig[$this->act . '.']['properties.']['title.']['readOnly'])
            ? $this->buttonConfig[$this->act . '.']['properties.']['title.']['readOnly']
            : (isset($this->buttonConfig['properties.']['title.']['readOnly'])
                ? $this->buttonConfig['properties.']['title.']['readOnly']
                : false);
        if ($readOnly) {
            $currentClass = $this->curUrlInfo['act'] === $this->act ? $this->curUrlArray['class'] : '';
            if (!$currentClass) {
                $currentClass = !$this->classesAnchorDefault[$this->act] ? '' : $this->classesAnchorDefault[$this->act];
            }
            $title = $currentClass
                ? $this->classesAnchorClassTitle[$currentClass]
                : $this->classesAnchorDefaultTitle[$this->act];
        }
        return '
						<tr>
							<td><label for="rtehtmlarea-browse-links-anchor_title" id="rtehtmlarea-browse-links-title-label">' . $this->getLanguageService()->getLL('anchor_title', true) . ':</label></td>
							<td colspan="3">
								<span id="rtehtmlarea-browse-links-title-input" style="display: ' . ($readOnly ? 'none' : 'inline') . ';">
									<input type="text" id="rtehtmlarea-browse-links-anchor_title" name="anchor_title" value="' . htmlspecialchars($title) . '" ' . $this->doc->formWidth(30) . ' />
								</span>
								<span id="rtehtmlarea-browse-links-title-readonly" style="display: ' . ($readOnly ? 'inline' : 'none') . ';">' . htmlspecialchars($title) . '</span>
							</td>
						</tr>';
    }

    /**
     * Return html code for the class selector
     *
     * @return string the html code to be added to the form
     */
    protected function getClassField()
    {
        $selectClass = '';
        if ($this->classesAnchorJSOptions[$this->act]) {
            $selectClass = '
						<tr>
							<td><label>' . $this->getLanguageService()->getLL('anchor_class', true) . ':</label></td>
							<td colspan="3">
								<select name="anchor_class" onchange="' . $this->getClassOnChangeJS() . '">
									' . $this->classesAnchorJSOptions[$this->act] . '
								</select>
							</td>
						</tr>';
        }
        return $selectClass;
    }

    /**
     * Return JS code for the class selector onChange event
     *
     * @return 	string	class selector onChange JS code
     */
    protected function getClassOnChangeJS()
    {
        return '
					if (document.ltargetform.anchor_class) {
						document.ltargetform.anchor_class.value = document.ltargetform.anchor_class.options[document.ltargetform.anchor_class.selectedIndex].value;
						if (document.ltargetform.anchor_class.value && HTMLArea.classesAnchorSetup) {
							for (var i = HTMLArea.classesAnchorSetup.length; --i >= 0;) {
								var anchorClass = HTMLArea.classesAnchorSetup[i];
								if (anchorClass[\'name\'] == document.ltargetform.anchor_class.value) {
									if (anchorClass[\'titleText\'] && document.ltargetform.anchor_title) {
										document.ltargetform.anchor_title.value = anchorClass[\'titleText\'];
										document.getElementById(\'rtehtmlarea-browse-links-title-readonly\').innerHTML = anchorClass[\'titleText\'];
										browse_links_setTitle(anchorClass[\'titleText\']);
									}
									if (typeof anchorClass[\'target\'] !== \'undefined\') {
										if (document.ltargetform.ltarget) {
											document.ltargetform.ltarget.value = anchorClass[\'target\'];
										}
										browse_links_setTarget(anchorClass[\'target\']);
									} else if (document.ltargetform.ltarget && document.getElementById(\'ltargetrow\').style.display == \'none\') {
											// Reset target to default if field is not displayed and class has no configured target
										document.ltargetform.ltarget.value = \'' . ($this->defaultLinkTarget ?: '') . '\';
										browse_links_setTarget(document.ltargetform.ltarget.value);
									}
									break;
								}
							}
						}
						browse_links_setClass(document.ltargetform.anchor_class.value);
					}
								';
    }

    /**
     * Reads the configured link handlers from page TSconfig
     *
     * @return array
     */
    protected function getLinkHandlers()
    {
        $linkHandlers = parent::getLinkHandlers();

        // add the "remove link" tab as last tab
        $linkHandlers['removeLink'] = [
            'handler' => RemoveLinkHandler::class,
            'label' => 'LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_browselinkscontroller.xlf:removeLink',
            'displayAfter' => [ 'page', 'file', 'folder', 'url', 'mail' ],
            'addParams' => 'onclick="plugin.unLink();return false;"',
        ];

        return $linkHandlers;
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
