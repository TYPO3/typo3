<?php
namespace TYPO3\CMS\Rtehtmlarea;

/**
 * Script class for the Element Browser window.
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class BrowseLinks extends \TYPO3\CMS\Recordlist\Browser\ElementBrowser {

	public $editorNo;
	/**
	 * TYPO3 language code of the content language
	 */
	public $contentTypo3Language;

	public $contentTypo3Charset = 'utf-8';
	/**
	 * Language service object for localization to the content language
	 */
	protected $contentLanguageService;

	public $additionalAttributes = array();

	public $buttonConfig = array();

	public $RTEProperties = array();

	public $anchorTypes = array('page', 'url', 'file', 'mail', 'spec');

	public $classesAnchorDefault = array();

	public $classesAnchorDefaultTitle = array();

	public $classesAnchorClassTitle = array();

	public $classesAnchorDefaultTarget = array();

	public $classesAnchorJSOptions = array();

	protected $defaultLinkTarget;

	public $allowedItems;

	/**
	 * Constructor:
	 * Initializes a lot of variables, setting JavaScript functions in header etc.
	 *
	 * @return 	void
	 * @todo Define visibility
	 */
	public function init() {
		$this->initVariables();
		// Create content laguage service
		$this->contentLanguageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Lang\\LanguageService');
		$this->contentLanguageService->init($this->contentTypo3Language);
		$this->initConfiguration();
		// init fileProcessor
		$this->fileProcessor = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Utility\\File\\BasicFileUtility');
		$this->fileProcessor->init($GLOBALS['FILEMOUNTS'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
		// Creating backend template object:
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		// Loading the Prototype library and browse_links.js
		$this->doc->getPageRenderer()->loadPrototype();
		$this->doc->loadJavascriptLib('js/tree.js');
		$this->doc->loadJavascriptLib('js/browse_links.js');
		// Adding context menu code
		$this->doc->getContextMenuCode();
		// Initializing hooking browsers
		$this->initHookObjects('ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php');
		// CurrentUrl - the current link url must be passed around if it exists
		$this->curUrlArray = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('curUrl');
		if ($this->curUrlArray['all']) {
			$this->curUrlArray = \TYPO3\CMS\Core\Utility\GeneralUtility::get_tag_attributes($this->curUrlArray['all']);
			$this->curUrlArray['href'] = htmlspecialchars_decode($this->curUrlArray['href']);
		}
		// Note: parseCurUrl will invoke the hooks
		$this->curUrlInfo = $this->parseCurUrl($this->curUrlArray['href'], $this->siteURL);
		if (isset($this->curUrlArray['data-htmlarea-external']) && $this->curUrlInfo['act'] != 'mail') {
			$this->curUrlInfo['act'] = 'url';
			$this->curUrlInfo['info'] = $this->curUrlArray['href'];
		}
		// Determine nature of current url:
		$this->act = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('act');
		if (!$this->act) {
			$this->act = $this->curUrlInfo['act'];
		}
		// Setting intial values for link attributes
		$this->initLinkAttributes();
		// Apply the same styles as those of the base script
		$this->doc->bodyTagId = 'typo3-browse-links-php';
		// Add attributes to body tag. Note: getBodyTagAdditions will invoke the hooks
		$this->doc->bodyTagAdditions = $this->getBodyTagAdditions();
		// Adding RTE JS code
		$this->doc->JScodeArray['rtehtmlarea'] = $this->getJSCode();
	}

	/**
	 * Initialize class variables
	 *
	 * @return 	void
	 */
	public function initVariables() {
		// Main GPvars:
		$this->pointer = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pointer');
		$this->bparams = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('bparams');
		$this->P = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');
		$this->expandPage = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('expandPage');
		$this->expandFolder = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('expandFolder');
		$this->PM = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('PM');
		// Process bparams
		$pArr = explode('|', $this->bparams);
		$pRteArr = explode(':', $pArr[1]);
		$this->editorNo = $pRteArr[0];
		$this->contentTypo3Language = $pRteArr[1];
		$this->RTEtsConfigParams = $pArr[2];
		if (!$this->editorNo) {
			$this->editorNo = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('editorNo');
			$this->contentTypo3Language = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('contentTypo3Language');
			$this->RTEtsConfigParams = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('RTEtsConfigParams');
		}
		$pArr[1] = implode(':', array($this->editorNo, $this->contentTypo3Language, $this->contentTypo3Charset));
		$pArr[2] = $this->RTEtsConfigParams;
		$this->bparams = implode('|', $pArr);
		// Find "mode"
		$this->mode = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('mode');
		if (!$this->mode) {
			$this->mode = 'rte';
		}
		// Current site url
		$this->siteURL = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
		// the script to link to
		$this->thisScript = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_NAME');
	}

	/**
	 * Initializes the configuration variables
	 *
	 * @return 	void
	 */
	public function initConfiguration() {
		$this->thisConfig = $this->getRTEConfig();
		$this->buttonConfig = $this->getButtonConfig('link');
	}

	/**
	 * Get the RTE configuration from Page TSConfig
	 *
	 * @return 	array		RTE configuration array
	 */
	protected function getRTEConfig() {
		$RTEtsConfigParts = explode(':', $this->RTEtsConfigParams);
		$RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($RTEtsConfigParts[5]));
		$this->RTEProperties = $RTEsetup['properties'];
		return \TYPO3\CMS\Backend\Utility\BackendUtility::RTEsetup($this->RTEProperties, $RTEtsConfigParts[0], $RTEtsConfigParts[2], $RTEtsConfigParts[4]);
	}

	/**
	 * Get the configuration of the button
	 *
	 * @param 	string		$buttonName: the name of the button
	 * @return 	array		the configuration array of the image button
	 */
	protected function getButtonConfig($buttonName) {
		return is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.'][$buttonName . '.']) ? $this->thisConfig['buttons.'][$buttonName . '.'] : array();
	}

	/**
	 * Initialize hook objects implementing the interface
	 *
	 * @param 	string		$hookKey: the hook key
	 * @return 	void
	 */
	protected function initHookObjects($hookKey) {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$hookKey]['browseLinksHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$hookKey]['browseLinksHook'] as $classData) {
				$processObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classData);
				if (!$processObject instanceof \TYPO3\CMS\Core\ElementBrowser\ElementBrowserHookInterface) {
					throw new \UnexpectedValueException('$processObject must implement interface TYPO3\\CMS\\Core\\ElementBrowser\\ElementBrowserHookInterface', 1195115652);
				}
				$parameters = array();
				$processObject->init($this, $parameters);
				$this->hookObjects[] = $processObject;
			}
		}
	}

	/**
	 * Initialize the current or default values of the link attributes
	 *
	 * @return 	void
	 */
	protected function initLinkAttributes() {
		// Initializing the title value
		$this->setTitle = $this->curUrlArray['title'];
		// Processing the classes configuration
		$classSelected = array();
		if ($this->buttonConfig['properties.']['class.']['allowedClasses']) {
			$this->setClass = $this->curUrlArray['class'];
			$classesAnchorArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->buttonConfig['properties.']['class.']['allowedClasses'], 1);
			$classesAnchorConfigArray = array();
			// Collecting allowed classes and configured default values
			$classesAnchor = array();
			$classesAnchor['all'] = array();
			$titleReadOnly = $this->buttonConfig['properties.']['title.']['readOnly'] || $this->buttonConfig[$this->act . '.']['properties.']['title.']['readOnly'];
			if (is_array($this->RTEProperties['classesAnchor.'])) {
				foreach ($this->RTEProperties['classesAnchor.'] as $label => $conf) {
					if (in_array($conf['class'], $classesAnchorArray)) {
						$classesAnchor['all'][] = $conf['class'];
						if (in_array($conf['type'], $this->anchorTypes)) {
							$classesAnchor[$conf['type']][] = $conf['class'];
							if ($this->buttonConfig[$conf['type'] . '.']['properties.']['class.']['default'] == $conf['class']) {
								$this->classesAnchorDefault[$conf['type']] = $conf['class'];
								if ($conf['titleText']) {
									$this->classesAnchorDefaultTitle[$conf['type']] = $this->getLLContent(trim($conf['titleText']));
								}
								if ($conf['target']) {
									$this->classesAnchorDefaultTarget[$conf['type']] = trim($conf['target']);
								}
							}
						}
						if ($titleReadOnly && $conf['titleText']) {
							$this->classesAnchorClassTitle[$conf['class']] = ($this->classesAnchorDefaultTitle[$conf['type']] = $this->getLLContent(trim($conf['titleText'])));
						}
					}
				}
			}
			// Constructing the class selector options
			foreach ($this->anchorTypes as $anchorType) {
				foreach ($classesAnchorArray as $class) {
					if (!in_array($class, $classesAnchor['all']) || in_array($class, $classesAnchor['all']) && is_array($classesAnchor[$anchorType]) && in_array($class, $classesAnchor[$anchorType])) {
						$selected = '';
						if ($this->setClass == $class || !$this->setClass && $this->classesAnchorDefault[$anchorType] == $class) {
							$selected = 'selected="selected"';
							$classSelected[$anchorType] = TRUE;
						}
						$classLabel = is_array($this->RTEProperties['classes.']) && is_array($this->RTEProperties['classes.'][$class . '.']) && $this->RTEProperties['classes.'][$class . '.']['name'] ? $this->getPageConfigLabel($this->RTEProperties['classes.'][$class . '.']['name'], 0) : $class;
						$classStyle = is_array($this->RTEProperties['classes.']) && is_array($this->RTEProperties['classes.'][$class . '.']) && $this->RTEProperties['classes.'][$class . '.']['value'] ? $this->RTEProperties['classes.'][$class . '.']['value'] : '';
						$this->classesAnchorJSOptions[$anchorType] .= '<option ' . $selected . ' value="' . $class . '"' . ($classStyle ? ' style="' . $classStyle . '"' : '') . '>' . $classLabel . '</option>';
					}
				}
				if ($this->classesAnchorJSOptions[$anchorType] && !($this->buttonConfig['properties.']['class.']['required'] || $this->buttonConfig[$this->act . '.']['properties.']['class.']['required'])) {
					$selected = '';
					if (!$this->setClass && !$this->classesAnchorDefault[$anchorType]) {
						$selected = 'selected="selected"';
					}
					$this->classesAnchorJSOptions[$anchorType] = '<option ' . $selected . ' value=""></option>' . $this->classesAnchorJSOptions[$anchorType];
				}
			}
		}
		// Initializing the target value
		// Unset the target if it is set to a value different than default and if no class is selected and the target field is not displayed
		// In other words, do not forward the target if we changed tab and the target field is not displayed
		$this->defaultLinkTarget = isset($this->buttonConfig['properties.']['target.']['default']) ? $this->buttonConfig['properties.']['target.']['default'] : '';
		$this->setTarget = '';
		if (isset($this->curUrlArray['target']) && !($this->curUrlArray['target'] != $this->defaultLinkTarget && !$classSelected[$this->act] && is_array($this->buttonConfig['targetSelector.']) && $this->buttonConfig['targetSelector.']['disabled'] && is_array($this->buttonConfig['popupSelector.']) && $this->buttonConfig['popupSelector.']['disabled'])) {
			$this->setTarget = $this->curUrlArray['target'];
		}
		if ($this->defaultLinkTarget && !isset($this->curUrlArray['target'])) {
			$this->setTarget = $this->defaultLinkTarget;
		}
		// Initializing additional attributes
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['TYPO3Link']['additionalAttributes']) {
			$addAttributes = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['TYPO3Link']['additionalAttributes'], 1);
			foreach ($addAttributes as $attribute) {
				$this->additionalAttributes[$attribute] = isset($this->curUrlArray[$attribute]) ? $this->curUrlArray[$attribute] : '';
			}
		}
	}

	/**
	 * Provide the additional parameters to be included in the template body tag
	 *
	 * @return 	string		the body tag additions
	 */
	public function getBodyTagAdditions() {
		$bodyTagAdditions = array();
		// call hook for extra additions
		foreach ($this->hookObjects as $hookObject) {
			if (method_exists($hookObject, 'addBodyTagAdditions')) {
				$bodyTagAdditions = $hookObject->addBodyTagAdditions($bodyTagAdditions);
			}
		}
		return \TYPO3\CMS\Core\Utility\GeneralUtility::implodeAttributes($bodyTagAdditions, TRUE);
	}

	/**
	 * Generate JS code to be used on the link insert/modify dialogue
	 *
	 * @return 	string		the generated JS code
	 * @todo Define visibility
	 */
	public function getJSCode() {
		// BEGIN accumulation of header JavaScript:
		$JScode = '';
		$JScode .= '
			var plugin = window.parent.RTEarea["' . $this->editorNo . '"].editor.getPlugin("TYPO3Link");
			var HTMLArea = window.parent.HTMLArea;
			var add_href="' . ($this->curUrlArray['href'] ? '&curUrl[href]=' . rawurlencode($this->curUrlArray['href']) : '') . '";
			var add_target="' . ($this->setTarget ? '&curUrl[target]=' . rawurlencode($this->setTarget) : '') . '";
			var add_class="' . ($this->setClass ? '&curUrl[class]=' . rawurlencode($this->setClass) : '') . '";
			var add_title="' . ($this->setTitle ? '&curUrl[title]=' . rawurlencode($this->setTitle) : '') . '";
			var add_params="' . ($this->bparams ? '&bparams=' . rawurlencode($this->bparams) : '') . '";
			var additionalValues = ' . (count($this->additionalAttributes) ? json_encode($this->additionalAttributes) : '{}') . ';';
		// Attributes setting functions
		$JScode .= '
			var cur_href="' . ($this->curUrlArray['href'] ? ($this->curUrlInfo['query'] ? substr($this->curUrlArray['href'], 0, -strlen($this->curUrlInfo['query'])) : $this->curUrlArray['href']) : '') . '";
			var cur_target="' . ($this->setTarget ? $this->setTarget : '') . '";
			var cur_class="' . ($this->setClass ? $this->setClass : '') . '";
			var cur_title="' . ($this->setTitle ? $this->setTitle : '') . '";

			function browse_links_setTarget(value) {
				cur_target=value;
				add_target="&curUrl[target]="+encodeURIComponent(value);
			}
			function browse_links_setClass(value) {
				cur_class=value;
				add_class="&curUrl[class]="+encodeURIComponent(value);
			}
			function browse_links_setTitle(value) {
				cur_title=value;
				add_title="&curUrl[title]="+encodeURIComponent(value);
			}
			function browse_links_setHref(value) {
				cur_href=value;
				add_href="&curUrl[href]="+value;
			}
			function browse_links_setAdditionalValue(name, value) {
				additionalValues[name] = value;
			}
		';
		// Link setting functions
		$JScode .= '
			function link_typo3Page(id,anchor) {
				var parameters = (document.ltargetform.query_parameters && document.ltargetform.query_parameters.value) ? (document.ltargetform.query_parameters.value.charAt(0) == "&" ? "" : "&") + document.ltargetform.query_parameters.value : "";
				var theLink = \'' . $this->siteURL . '?id=\' + id + parameters + (anchor ? anchor : "");
				if (document.ltargetform.anchor_title) browse_links_setTitle(document.ltargetform.anchor_title.value);
				if (document.ltargetform.anchor_class) browse_links_setClass(document.ltargetform.anchor_class.value);
				if (document.ltargetform.ltarget) browse_links_setTarget(document.ltargetform.ltarget.value);
				if (document.ltargetform.lrel) browse_links_setAdditionalValue("rel", document.ltargetform.lrel.value);
				browse_links_setAdditionalValue("data-htmlarea-external", "");
				plugin.createLink(theLink,cur_target,cur_class,cur_title,additionalValues);
				return false;
			}
			function link_folder(folder) {
				if (folder && folder.substr(0, 5) == "file:") {
					var theLink = \'' . $this->siteURL . '?file:\' + encodeURIComponent(folder.substr(5));
				} else {
					var theLink = \'' . $this->siteURL . '?\' + folder;
				}
				if (document.ltargetform.anchor_title) browse_links_setTitle(document.ltargetform.anchor_title.value);
				if (document.ltargetform.anchor_class) browse_links_setClass(document.ltargetform.anchor_class.value);
				if (document.ltargetform.ltarget) browse_links_setTarget(document.ltargetform.ltarget.value);
				if (document.ltargetform.lrel) browse_links_setAdditionalValue("rel", document.ltargetform.lrel.value);
				browse_links_setAdditionalValue("data-htmlarea-external", "");
				plugin.createLink(theLink,cur_target,cur_class,cur_title,additionalValues);
				return false;
			}
			function link_spec(theLink) {
				if (document.ltargetform.anchor_title) browse_links_setTitle(document.ltargetform.anchor_title.value);
				if (document.ltargetform.anchor_class) browse_links_setClass(document.ltargetform.anchor_class.value);
				if (document.ltargetform.ltarget) browse_links_setTarget(document.ltargetform.ltarget.value);
				browse_links_setAdditionalValue("data-htmlarea-external", "");
				plugin.createLink(theLink,cur_target,cur_class,cur_title,additionalValues);
				return false;
			}
			function link_current() {
				var parameters = (document.ltargetform.query_parameters && document.ltargetform.query_parameters.value) ? (document.ltargetform.query_parameters.value.charAt(0) == "&" ? "" : "&") + document.ltargetform.query_parameters.value : "";
				if (document.ltargetform.anchor_title) browse_links_setTitle(document.ltargetform.anchor_title.value);
				if (document.ltargetform.anchor_class) browse_links_setClass(document.ltargetform.anchor_class.value);
				if (document.ltargetform.ltarget) browse_links_setTarget(document.ltargetform.ltarget.value);
				if (document.ltargetform.lrel) browse_links_setAdditionalValue("rel", document.ltargetform.lrel.value);
				if (cur_href!="http://" && cur_href!="mailto:") {
					plugin.createLink(cur_href + parameters,cur_target,cur_class,cur_title,additionalValues);
				}
				return false;
			}
		';
		// General "jumpToUrl" and launchView functions:
		$JScode .= '
			function jumpToUrl(URL,anchor) {
				var add_editorNo = URL.indexOf("editorNo=")==-1 ? "&editorNo=' . $this->editorNo . '" : "";
				var add_contentTypo3Language = URL.indexOf("contentTypo3Language=")==-1 ? "&contentTypo3Language=' . $this->contentTypo3Language . '" : "";
				var add_act = URL.indexOf("act=")==-1 ? "&act=' . $this->act . '" : "";
				var add_mode = URL.indexOf("mode=")==-1 ? "&mode=' . $this->mode . '" : "";
				var add_additionalValues = "";
				if (plugin.pageTSConfiguration && plugin.pageTSConfiguration.additionalAttributes) {
					var additionalAttributes = plugin.pageTSConfiguration.additionalAttributes.split(",");
					for (var i = additionalAttributes.length; --i >= 0;) {
						if (additionalValues[additionalAttributes[i]] != "") {
							add_additionalValues += "&curUrl[" + additionalAttributes[i] + "]=" + encodeURIComponent(additionalValues[additionalAttributes[i]]);
						}
					}
				}
				var theLocation = URL+add_act+add_editorNo+add_contentTypo3Language+add_mode+add_href+add_target+add_class+add_title+add_additionalValues+add_params+(anchor?anchor:"");
				window.location.href = theLocation;
				return false;
			}
			function launchView(url) {
				var thePreviewWindow="";
				thePreviewWindow = window.open("' . $GLOBALS['BACK_PATH'] . 'show_item.php?table="+url,"ShowItem","height=300,width=410,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");
				if (thePreviewWindow && thePreviewWindow.focus) {
					thePreviewWindow.focus();
				}
			}
		';
		// Hook to overwrite or extend javascript functions
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']['extendJScode']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']['extendJScode'])) {
			$_params = array(
				'conf' => &$conf
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']['extendJScode'] as $objRef) {
				$processor =& \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($objRef);
				$JScode .= $processor->extendJScode($_params, $this);
			}
		}
		return $JScode;
	}

	/******************************************************************
	 *
	 * Main functions
	 *
	 ******************************************************************/
	/**
	 * Rich Text Editor (RTE) link selector (MAIN function)
	 * Generates the link selector for the Rich Text Editor.
	 * Can also be used to select links for the TCEforms (see $wiz)
	 *
	 * @param 	boolean		If set, the "remove link" is not shown in the menu: Used for the "Select link" wizard which is used by the TCEforms
	 * @return 	string		Modified content variable.
	 * @todo Define visibility
	 */
	public function main_rte($wiz = 0) {
		// Starting content:
		$content = $this->doc->startPage($GLOBALS['LANG']->getLL('Insert/Modify Link', 1));
		// Default allowed values
		$this->allowedItems = explode(',', 'page,file,folder,url,mail,spec');
		// Calling hook for extra options
		foreach ($this->hookObjects as $hookObject) {
			$this->allowedItems = $hookObject->addAllowedItems($this->allowedItems);
		}
		// Removing items as per configuration
		if (is_array($this->buttonConfig['options.']) && $this->buttonConfig['options.']['removeItems']) {
			$this->allowedItems = array_diff($this->allowedItems, \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->buttonConfig['options.']['removeItems'], 1));
		}
		reset($this->allowedItems);
		if (!in_array($this->act, $this->allowedItems)) {
			$this->act = current($this->allowedItems);
		}
		// Making menu in top:
		$menuDef = array();
		if (!$wiz && $this->curUrlArray['href']) {
			$menuDef['removeLink']['isActive'] = $this->act == 'removeLink';
			$menuDef['removeLink']['label'] = $GLOBALS['LANG']->getLL('removeLink', 1);
			$menuDef['removeLink']['url'] = '#';
			$menuDef['removeLink']['addParams'] = 'onclick="plugin.unLink();return false;"';
		}
		if (in_array('page', $this->allowedItems)) {
			$menuDef['page']['isActive'] = $this->act == 'page';
			$menuDef['page']['label'] = $GLOBALS['LANG']->getLL('page', 1);
			$menuDef['page']['url'] = '#';
			$menuDef['page']['addParams'] = 'onclick="jumpToUrl(\'' . htmlspecialchars(('?act=page&mode=' . $this->mode . '&bparams=' . $this->bparams)) . '\');return false;"';
		}
		if (in_array('file', $this->allowedItems)) {
			$menuDef['file']['isActive'] = $this->act == 'file';
			$menuDef['file']['label'] = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_browse_links.xlf:file', 1);
			$menuDef['file']['url'] = '#';
			$menuDef['file']['addParams'] = 'onclick="jumpToUrl(\'' . htmlspecialchars(('?act=file&mode=' . $this->mode . '&bparams=' . $this->bparams)) . '\');return false;"';
		}
		if (in_array('folder', $this->allowedItems)) {
			$menuDef['folder']['isActive'] = $this->act == 'folder';
			$menuDef['folder']['label'] = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_browse_links.xlf:folder', 1);
			$menuDef['folder']['url'] = '#';
			$menuDef['folder']['addParams'] = 'onclick="jumpToUrl(\'' . htmlspecialchars(('?act=folder&mode=' . $this->mode . '&bparams=' . $this->bparams)) . '\');return false;"';
		}
		if (in_array('url', $this->allowedItems)) {
			$menuDef['url']['isActive'] = $this->act == 'url';
			$menuDef['url']['label'] = $GLOBALS['LANG']->getLL('extUrl', 1);
			$menuDef['url']['url'] = '#';
			$menuDef['url']['addParams'] = 'onclick="jumpToUrl(\'' . htmlspecialchars(('?act=url&mode=' . $this->mode . '&bparams=' . $this->bparams)) . '\');return false;"';
		}
		if (in_array('mail', $this->allowedItems)) {
			$menuDef['mail']['isActive'] = $this->act == 'mail';
			$menuDef['mail']['label'] = $GLOBALS['LANG']->getLL('email', 1);
			$menuDef['mail']['url'] = '#';
			$menuDef['mail']['addParams'] = 'onclick="jumpToUrl(\'' . htmlspecialchars(('?act=mail&mode=' . $this->mode . '&bparams=' . $this->bparams)) . '\');return false;"';
		}
		if (is_array($this->thisConfig['userLinks.']) && in_array('spec', $this->allowedItems)) {
			$menuDef['spec']['isActive'] = $this->act == 'spec';
			$menuDef['spec']['label'] = $GLOBALS['LANG']->getLL('special', 1);
			$menuDef['spec']['url'] = '#';
			$menuDef['spec']['addParams'] = 'onclick="jumpToUrl(\'' . htmlspecialchars(('?act=spec&mode=' . $this->mode . '&bparams=' . $this->bparams)) . '\');return false;"';
		}
		// call hook for extra options
		foreach ($this->hookObjects as $hookObject) {
			$menuDef = $hookObject->modifyMenuDefinition($menuDef);
		}
		$content .= $this->doc->getTabMenuRaw($menuDef);
		// Adding the menu and header to the top of page:
		$content .= $this->printCurrentUrl($this->curUrlInfo['info']) . '<br />';
		// Depending on the current action we will create the actual module content for selecting a link:
		switch ($this->act) {
		case 'mail':
			$extUrl = '
			<!--
				Enter mail address:
			-->
					<form action="" name="lurlform" id="lurlform">
						<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkMail">
							<tr>
								<td>' . $GLOBALS['LANG']->getLL('emailAddress', 1) . ':</td>
								<td><input type="text" name="lemail"' . $this->doc->formWidth(20) . ' value="' . htmlspecialchars(($this->curUrlInfo['act'] == 'mail' ? $this->curUrlInfo['info'] : '')) . '" /> ' . '<input type="submit" value="' . $GLOBALS['LANG']->getLL('setLink', 1) . '" onclick="browse_links_setTarget(\'\');browse_links_setHref(\'mailto:\'+document.lurlform.lemail.value);browse_links_setAdditionalValue(\'data-htmlarea-external\', \'\');return link_current();" /></td>
							</tr>
						</table>
					</form>';
			$content .= $extUrl;
			$content .= $this->addAttributesForm();
			break;
		case 'url':
			$extUrl = '
			<!--
				Enter External URL:
			-->
					<form action="" name="lurlform" id="lurlform">
						<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkURL">
							<tr>
								<td>URL:</td>
								<td><input type="text" name="lurl"' . $this->doc->formWidth(20) . ' value="' . htmlspecialchars(($this->curUrlInfo['act'] == 'url' ? $this->curUrlInfo['info'] : 'http://')) . '" /> ' . '<input type="submit" value="' . $GLOBALS['LANG']->getLL('setLink', 1) . '" onclick="if (/^[A-Za-z0-9_+]{1,8}:/.test(document.lurlform.lurl.value)) { browse_links_setHref(document.lurlform.lurl.value); } else { browse_links_setHref(\'http://\'+document.lurlform.lurl.value); }; browse_links_setAdditionalValue(\'data-htmlarea-external\', \'1\'); return link_current();" /></td>
							</tr>
						</table>
					</form>';
			$content .= $extUrl;
			$content .= $this->addAttributesForm();
			break;
		case 'file':

		case 'folder':
			$content .= $this->addAttributesForm();
			// Create folder tree:
			$this->doc->JScode .= $this->doc->wrapScriptTags('
					Tree.ajaxID = "SC_alt_file_navframe::expandCollapse";
				');
			$foldertree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Rtehtmlarea\\FolderTree');
			$foldertree->thisScript = $this->thisScript;
			$tree = $foldertree->getBrowsableTree();
			if (!$this->curUrlInfo['value'] || $this->curUrlInfo['act'] != $this->act) {
				$cmpPath = '';
			} else {
				$cmpPath = $this->curUrlInfo['value'];
				if (!isset($this->expandFolder)) {
					$this->expandFolder = $cmpPath;
				}
			}
			// Get the selected folder
			if ($this->expandFolder) {
				$selectedFolder = FALSE;
				$fileOrFolderObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->expandFolder);
				if ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
					// it's a folder
					$selectedFolder = $fileOrFolderObject;
				} elseif ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
					// it's a file
					// @todo: find the parent folder, right now done a bit ugly, because the file does not
					// support finding the parent folder of a file on purpose
					$folderIdentifier = dirname($fileOrFolderObject->getIdentifier());
					$selectedFolder = $fileOrFolderObject->getStorage()->getFolder($folderIdentifier);
				}
			}
			// If no folder is selected, get the user's default upload folder
			if (!$selectedFolder) {
				$selectedFolder = $GLOBALS['BE_USER']->getDefaultUploadFolder();
			}
			// Build the file upload and folder creation forms
			$uploadForm = '';
			$createFolder = '';
			if ($selectedFolder && !$this->isReadOnlyFolder($selectedFolder)) {
				$uploadForm = $this->act === 'file' ? $this->uploadForm($selectedFolder) : '';
				if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.createFoldersInEB')) {
					$createFolder = $this->createFolder($selectedFolder);
				}
			}
			// Insert the upload form on top, if so configured
			if ($GLOBALS['BE_USER']->getTSConfigVal('options.uploadFieldsInTopOfEB')) {
				$content .= $uploadForm;
			}
			// Render the filelist if there is a folder selected
			if ($selectedFolder) {
				$files = $this->expandFolder($selectedFolder, $this->P['params']['allowedExtensions']);
			}
			$content .= '

			<!--
				Wrapper table for folder tree / file/folder list:
			-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkFiles">
						<tr>
							<td class="c-wCell" valign="top">' . $this->barheader(($GLOBALS['LANG']->getLL('folderTree') . ':')) . $tree . '</td>
							<td class="c-wCell" valign="top">' . $files . '</td>
						</tr>
					</table>
					';
			// Adding create folder + upload form if applicable
			if (!$GLOBALS['BE_USER']->getTSConfigVal('options.uploadFieldsInTopOfEB')) {
				$content .= $uploadForm;
			}
			$content .= '<br />';
			$content .= $createFolder;
			$content .= '<br />';
			break;
		case 'spec':
			if (is_array($this->thisConfig['userLinks.'])) {
				$subcats = array();
				$v = $this->thisConfig['userLinks.'];
				foreach ($v as $k2 => $dummyValue) {
					$k2i = intval($k2);
					if (substr($k2, -1) == '.' && is_array($v[$k2i . '.'])) {
						// Title:
						$title = trim($v[$k2i]);
						if (!$title) {
							$title = $v[$k2i . '.']['url'];
						} else {
							$title = $GLOBALS['LANG']->sL($title);
						}
						// Description:
						$description = $v[$k2i . '.']['description'] ? $GLOBALS['LANG']->sL($v[($k2i . '.')]['description'], 1) . '<br />' : '';
						// URL + onclick event:
						$onClickEvent = '';
						if (isset($v[$k2i . '.']['target'])) {
							$onClickEvent .= 'TYPO3\\CMS\\Recordlist\\Browser\\ElementBrowser_setTarget(\'' . $v[($k2i . '.')]['target'] . '\');';
						}
						$v[$k2i . '.']['url'] = str_replace('###_URL###', $this->siteURL, $v[$k2i . '.']['url']);
						if (substr($v[$k2i . '.']['url'], 0, 7) == 'http://' || substr($v[$k2i . '.']['url'], 0, 7) == 'mailto:') {
							$onClickEvent .= 'cur_href=unescape(\'' . rawurlencode($v[($k2i . '.')]['url']) . '\');link_current();';
						} else {
							$onClickEvent .= 'link_spec(unescape(\'' . $this->siteURL . rawurlencode($v[($k2i . '.')]['url']) . '\'));';
						}
						// Link:
						$A = array('<a href="#" onclick="' . htmlspecialchars($onClickEvent) . 'return false;">', '</a>');
						// Adding link to menu of user defined links:
						$subcats[$k2i] = '
								<tr>
									<td class="bgColor4">' . $A[0] . '<strong>' . htmlspecialchars($title) . ($this->curUrlInfo['info'] == $v[$k2i . '.']['url'] ? '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/blinkarrow_right.gif', 'width="5" height="9"') . ' class="c-blinkArrowR" alt="" />' : '') . '</strong><br />' . $description . $A[1] . '</td>
								</tr>';
					}
				}
				// Sort by keys:
				ksort($subcats);
				// Add menu to content:
				$content .= '
			<!--
				Special userdefined menu:
			-->
						<table border="0" cellpadding="1" cellspacing="1" id="typo3-linkSpecial">
							<tr>
								<td class="bgColor5" class="c-wCell" valign="top"><strong>' . $GLOBALS['LANG']->getLL('special', 1) . '</strong></td>
							</tr>
							' . implode('', $subcats) . '
						</table>
						';
			}
			break;
		case 'page':
			$content .= $this->addAttributesForm();
			$pagetree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Rtehtmlarea\\PageTree');
			$pagetree->ext_showNavTitle = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showNavTitle');
			$pagetree->ext_showPageId = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showPageIdWithTitle');
			$pagetree->addField('nav_title');
			$tree = $pagetree->getBrowsableTree();
			$cElements = $this->expandPage();
			// Outputting Temporary DB mount notice:
			if (intval($GLOBALS['BE_USER']->getSessionData('pageTree_temporaryMountPoint'))) {
				$link = '<a href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('setTempDBmount' => 0))) . '">' . $GLOBALS['LANG']->sl('LLL:EXT:lang/locallang_core.xlf:labels.temporaryDBmount', 1) . '</a>';
				$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $link, '', \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
				$dbmount = $flashMessage->render();
			}
			$content .= '
			<!--
				Wrapper table for page tree / record list:
			-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkPages">
						<tr>
							<td class="c-wCell" valign="top">' . $this->barheader(($GLOBALS['LANG']->getLL('pageTree') . ':')) . $dbmount . $tree . '</td>
							<td class="c-wCell" valign="top">' . $cElements . '</td>
						</tr>
					</table>
					';
			break;
		default:
			// call hook
			foreach ($this->hookObjects as $hookObject) {
				$content .= $hookObject->getTab($this->act);
			}
			break;
		}
		// End page, return content:
		$content .= $this->doc->endPage();
		$content = $this->doc->insertStylesAndJS($content);
		return $content;
	}

	/**
	 * Creates a form for link attributes
	 *
	 * @return string The HTML code of the form
	 */
	public function addAttributesForm() {
		$ltargetForm = '';
		$additionalAttributeFields = '';
		// Add page id, target, class selector box, title and parameters fields:
		$lpageId = $this->addPageIdSelector();
		$queryParameters = $this->addQueryParametersSelector();
		$ltarget = $this->addTargetSelector();
		$lclass = $this->addClassSelector();
		$ltitle = $this->addTitleSelector();
		$rel = $this->addRelField();
		// additional fields for links
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']['addAttributeFields']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']['addAttributeFields'])) {
			$_params = array(
				'conf' => &$conf
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']['addAttributeFields'] as $objRef) {
				$processor =& \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($objRef);
				$additionalAttributeFields .= $processor->getAttributefields($_params, $this);
			}
		}
		if ($lpageId || $queryParameters || $ltarget || $lclass || $ltitle || $rel) {
			$ltargetForm = $this->wrapInForm($lpageId . $queryParameters . $ltarget . $lclass . $ltitle . $rel . $additionalAttributeFields);
		}
		return $ltargetForm;
	}

	/**
	 * @todo Define visibility
	 */
	public function wrapInForm($string) {
		$form = '
			<!--
				Selecting target for link:
			-->
				<form action="" name="ltargetform" id="ltargetform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkTarget">' . $string;
		if ($this->act == $this->curUrlInfo['act'] && $this->act != 'mail' && $this->curUrlArray['href']) {
			$form .= '
						<tr>
							<td>
							</td>
							<td colspan="3">
								<input type="submit" value="' . $GLOBALS['LANG']->getLL('update', 1) . '" onclick="' . ($this->act == 'url' ? 'browse_links_setAdditionalValue(\'data-htmlarea-external\', \'1\'); ' : '') . 'return link_current();" />
							</td>
						</tr>';
		}
		$form .= '
					</table>
				</form>';
		return $form;
	}

	/**
	 * @todo Define visibility
	 */
	public function addPageIdSelector() {
		return $this->act == 'page' && $this->buttonConfig && is_array($this->buttonConfig['pageIdSelector.']) && $this->buttonConfig['pageIdSelector.']['enabled'] ? '
						<tr>
							<td>' . $GLOBALS['LANG']->getLL('page_id', 1) . ':</td>
							<td colspan="3">
								<input type="text" size="6" name="luid" />&nbsp;<input type="submit" value="' . $GLOBALS['LANG']->getLL('setLink', 1) . '" onclick="return link_typo3Page(document.ltargetform.luid.value);" />
							</td>
						</tr>' : '';
	}

	/**
	 * @todo Define visibility
	 */
	public function addRelField() {
		return ($this->act == 'page' || $this->act == 'url' || $this->act == 'file') && $this->buttonConfig && is_array($this->buttonConfig['relAttribute.']) && $this->buttonConfig['relAttribute.']['enabled'] ? '
						<tr>
							<td>' . $GLOBALS['LANG']->getLL('linkRelationship', 1) . ':</td>
							<td colspan="3">
								<input type="text" name="lrel" value="' . $this->additionalAttributes['rel'] . '"  ' . $this->doc->formWidth(30) . ' />
							</td>
						</tr>' : '';
	}

	/**
	 * @todo Define visibility
	 */
	public function addQueryParametersSelector() {
		return $this->act == 'page' && $this->buttonConfig && is_array($this->buttonConfig['queryParametersSelector.']) && $this->buttonConfig['queryParametersSelector.']['enabled'] ? '
						<tr>
							<td>' . $GLOBALS['LANG']->getLL('query_parameters', 1) . ':</td>
							<td colspan="3">
								<input type="text" name="query_parameters" value="' . ($this->curUrlInfo['query'] ? $this->curUrlInfo['query'] : '') . '" ' . $this->doc->formWidth(30) . ' />
							</td>
						</tr>' : '';
	}

	/**
	 * @todo Define visibility
	 */
	public function addTargetSelector() {
		$targetSelectorConfig = array();
		$popupSelectorConfig = array();
		if (is_array($this->buttonConfig['targetSelector.'])) {
			$targetSelectorConfig = $this->buttonConfig['targetSelector.'];
		}
		if (is_array($this->buttonConfig['popupSelector.'])) {
			$popupSelectorConfig = $this->buttonConfig['popupSelector.'];
		}
		$ltarget = '';
		if ($this->act != 'mail') {
			$ltarget .= '
					<tr id="ltargetrow"' . ($targetSelectorConfig['disabled'] && $popupSelectorConfig['disabled'] ? ' style="display: none;"' : '') . '>
						<td>' . $GLOBALS['LANG']->getLL('target', 1) . ':</td>
						<td><input type="text" name="ltarget" onchange="browse_links_setTarget(this.value);" value="' . htmlspecialchars(($this->setTarget ? $this->setTarget : ($this->setClass || !$this->classesAnchorDefault[$this->act] ? '' : $this->classesAnchorDefaultTarget[$this->act]))) . '"' . $this->doc->formWidth(10) . ' /></td>';
			$ltarget .= '
						<td colspan="2">';
			if (!$targetSelectorConfig['disabled']) {
				$ltarget .= '
							<select name="ltarget_type" onchange="browse_links_setTarget(this.options[this.selectedIndex].value);document.ltargetform.ltarget.value=this.options[this.selectedIndex].value;this.selectedIndex=0;">
								<option></option>
								<option value="_top">' . $GLOBALS['LANG']->getLL('top', 1) . '</option>
								<option value="_blank">' . $GLOBALS['LANG']->getLL('newWindow', 1) . '</option>
							</select>';
			}
			$ltarget .= '
						</td>
					</tr>';
			if (!$popupSelectorConfig['disabled']) {
				$selectJS = 'if (document.ltargetform.popup_width.options[document.ltargetform.popup_width.selectedIndex].value>0 && document.ltargetform.popup_height.options[document.ltargetform.popup_height.selectedIndex].value>0) {
					document.ltargetform.ltarget.value = document.ltargetform.popup_width.options[document.ltargetform.popup_width.selectedIndex].value+\'x\'+document.ltargetform.popup_height.options[document.ltargetform.popup_height.selectedIndex].value;
					browse_links_setTarget(document.ltargetform.ltarget.value);
					document.ltargetform.popup_width.selectedIndex=0;
					document.ltargetform.popup_height.selectedIndex=0;
				}';
				$ltarget .= '
						<tr>
							<td>' . $GLOBALS['LANG']->getLL('target_popUpWindow', 1) . ':</td>
							<td colspan="3">
								<select name="popup_width" onchange="' . $selectJS . '">
									<option value="0">' . $GLOBALS['LANG']->getLL('target_popUpWindow_width', 1) . '</option>
									<option value="300">300</option>
									<option value="400">400</option>
									<option value="500">500</option>
									<option value="600">600</option>
									<option value="700">700</option>
									<option value="800">800</option>
								</select>
								x
								<select name="popup_height" onchange="' . $selectJS . '">
									<option value="0">' . $GLOBALS['LANG']->getLL('target_popUpWindow_height', 1) . '</option>
									<option value="200">200</option>
									<option value="300">300</option>
									<option value="400">400</option>
									<option value="500">500</option>
									<option value="600">600</option>
								</select>
							</td>
						</tr>';
			}
		}
		return $ltarget;
	}

	/**
	 * Return html code for the class selector
	 *
	 * @return 	string		the html code to be added to the form
	 */
	public function addClassSelector() {
		$selectClass = '';
		if ($this->classesAnchorJSOptions[$this->act]) {
			$selectClass = '
						<tr>
							<td>' . $GLOBALS['LANG']->getLL('anchor_class', 1) . ':</td>
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
	public function getClassOnChangeJS() {
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
									if (anchorClass[\'target\']) {
										if (document.ltargetform.ltarget) {
											document.ltargetform.ltarget.value = anchorClass[\'target\'];
										}
										browse_links_setTarget(anchorClass[\'target\']);
									} else if (document.ltargetform.ltarget && document.getElementById(\'ltargetrow\').style.display == \'none\') {
											// Reset target to default if field is not displayed and class has no configured target
										document.ltargetform.ltarget.value = \'' . ($this->defaultLinkTarget ? $this->defaultLinkTarget : '') . '\';
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
	 * @todo Define visibility
	 */
	public function addTitleSelector() {
		$title = $this->setTitle ? $this->setTitle : ($this->setClass || !$this->classesAnchorDefault[$this->act] ? '' : $this->classesAnchorDefaultTitle[$this->act]);
		$readOnly = $this->buttonConfig['properties.']['title.']['readOnly'] || $this->buttonConfig[$this->act . '.']['properties.']['title.']['readOnly'];
		if ($readOnly) {
			$title = $this->setClass ? $this->classesAnchorClassTitle[$this->setClass] : $this->classesAnchorDefaultTitle[$this->act];
		}
		return '
						<tr>
							<td><label for="rtehtmlarea-browse-links-anchor_title" id="rtehtmlarea-browse-links-title-label">' . $GLOBALS['LANG']->getLL('anchor_title', 1) . ':</label></td>
							<td colspan="3">
								<span id="rtehtmlarea-browse-links-title-input" style="display: ' . ($readOnly ? 'none' : 'inline') . ';">
									<input type="text" id="rtehtmlarea-browse-links-anchor_title" name="anchor_title" value="' . $title . '" ' . $this->doc->formWidth(30) . ' />
								</span>
								<span id="rtehtmlarea-browse-links-title-readonly" style="display: ' . ($readOnly ? 'inline' : 'none') . ';">' . $title . '</span>
							</td>
						</tr>';
	}

	/**
	 * Localize a string using the language of the content element rather than the language of the BE interface
	 *
	 * @param 	string		string: the label to be localized
	 * @return 	string		Localized string.
	 */
	public function getLLContent($string) {
		return $this->contentLanguageService->sL($string);
	}

	/**
	 * Localize a label obtained from Page TSConfig
	 *
	 * @param string $string The label to be localized
	 * @param boolean $JScharCode If needs to be converted to a array of char numbers
	 * @return string Localized string.
	 */
	public function getPageConfigLabel($string, $JScharCode = 1) {
		if (strcmp(substr($string, 0, 4), 'LLL:')) {
			$label = $string;
		} else {
			$label = $GLOBALS['LANG']->sL(trim($string));
		}
		$label = str_replace('"', '\\"', str_replace('\\\'', '\'', $label));
		$label = $JScharCode ? $GLOBALS['LANG']->JScharCode($label) : $label;
		return $label;
	}

}


?>