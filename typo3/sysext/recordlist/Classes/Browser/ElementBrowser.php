<?php
namespace TYPO3\CMS\Recordlist\Browser;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * class for the Element Browser window.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ElementBrowser {

	// Internal, static:
	// Current site URL (Frontend)
	/**
	 * @todo Define visibility
	 */
	public $siteURL;

	// the script to link to
	/**
	 * @todo Define visibility
	 */
	public $thisScript;

	// RTE specific TSconfig
	/**
	 * @todo Define visibility
	 */
	public $thisConfig;

	// Target (RTE specific)
	/**
	 * @todo Define visibility
	 */
	public $setTarget;

	// CSS Class (RTE specific)
	/**
	 * @todo Define visibility
	 */
	public $setClass;

	// title (RTE specific)
	/**
	 * @todo Define visibility
	 */
	public $setTitle;

	// Backend template object
	/**
	 * @todo Define visibility
	 */
	public $doc;

	// Holds information about files
	/**
	 * @todo Define visibility
	 */
	public $elements = array();

	// GPvars:	(Input variables from outside)
	/**
	 * The mode determines the main kind of output from the element browser.
	 * There are these options for values: rte, db, file, filedrag, wizard.
	 * "rte" will show the link selector for the Rich Text Editor (see main_rte())
	 * "db" will allow you to browse for pages or records in the page tree (for TCEforms, see main_db())
	 * "file"/"filedrag" will allow you to browse for files or folders in the folder mounts (for TCEforms, main_file())
	 * "wizard" will allow you to browse for links (like "rte") which are passed back to TCEforms (see main_rte(1))
	 *
	 * @see main()
	 * @todo Define visibility
	 */
	public $mode;

	/**
	 * Link selector action.
	 * page,file,url,mail,spec are allowed values.
	 * These are only important with the link selector function and in that case they switch
	 * between the various menu options.
	 *
	 * @todo Define visibility
	 */
	public $act;

	/**
	 * When you click a page title/expand icon to see the content of a certain page, this
	 * value will contain that value (the ID of the expanded page). If the value is NOT set,
	 * then it will be restored from the module session data (see main(), mode="db")
	 *
	 * @todo Define visibility
	 */
	public $expandPage;

	/**
	 * When you click a folder name/expand icon to see the content of a certain file folder,
	 * this value will contain that value (the path of the expanded file folder). If the
	 * value is NOT set, then it will be restored from the module session data (see main(),
	 * mode="file"/"filedrag"). Example value: "/www/htdocs/typo3/32/3dsplm/fileadmin/css/"
	 *
	 * @todo Define visibility
	 */
	public $expandFolder;

	/**
	 * the folder object of a parent folder that was selected
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder
	 */
	protected $selectedFolder;

	/**
	 * TYPO3 Element Browser, wizard mode parameters. There is a heap of parameters there, better debug() them out if you need something... :-)
	 *
	 * @todo Define visibility
	 */
	public $P;

	/**
	 * Active with TYPO3 Element Browser: Contains the name of the form field for which this window opens - thus allows us to make references back to the main window in which the form is.
	 * Example value: "data[pages][39][bodytext]|||tt_content|" or "data[tt_content][NEW3fba56fde763d][image]|||gif,jpg,jpeg,tif,bmp,pcx,tga,png,pdf,ai|"
	 *
	 * Values:
	 * 0: form field name reference, eg. "data[tt_content][123][image]"
	 * 1: htlmArea RTE parameters: editorNo:contentTypo3Language:contentTypo3Charset
	 * 2: RTE config parameters: RTEtsConfigParams
	 * 3: allowed types. Eg. "tt_content" or "gif,jpg,jpeg,tif,bmp,pcx,tga,png,pdf,ai"
	 * 4: IRRE uniqueness: target level object-id to perform actions/checks on, eg. "data[79][tt_address][1][<field>][<foreign_table>]"
	 * 5: IRRE uniqueness: name of function in opener window that checks if element is already used, eg. "inline.checkUniqueElement"
	 * 6: IRRE uniqueness: name of function in opener window that performs some additional(!) action, eg. "inline.setUniqueElement"
	 * 7: IRRE uniqueness: name of function in opener window that performs action instead of using addElement/insertElement, eg. "inline.importElement"
	 *
	 * $pArr = explode('|', $this->bparams);
	 * $formFieldName = $pArr[0];
	 * $allowedTablesOrFileTypes = $pArr[3];
	 *
	 * @todo Define visibility
	 */
	public $bparams;

	/**
	 * Used with the Rich Text Editor.
	 * Example value: "tt_content:NEW3fba58c969f5c:bodytext:23:text:23:"
	 *
	 * @todo Define visibility
	 */
	public $RTEtsConfigParams;

	/**
	 * Plus/Minus icon value. Used by the tree class to open/close notes on the trees.
	 *
	 * @todo Define visibility
	 */
	public $PM;

	/**
	 * Pointer, used when browsing a long list of records etc.
	 *
	 * @todo Define visibility
	 */
	public $pointer;

	/**
	 * Used with the link selector: Contains the GET input information about the CURRENT link in the RTE/TCEform field. This consists of "href", "target" and "title" keys. This information is passed around in links.
	 *
	 * @todo Define visibility
	 */
	public $curUrlArray;

	/**
	 * Used with the link selector: Contains a processed version of the input values from curUrlInfo. This is splitted into pageid, content element id, label value etc. This is used for the internal processing of that information.
	 *
	 * @todo Define visibility
	 */
	public $curUrlInfo;

	/**
	 * array which holds hook objects (initialised in init() )
	 */
	protected $hookObjects = array();

	/**
	 * @var \TYPO3\CMS\Core\Utility\File\BasicFileUtility
	 */
	public $fileProcessor;

	/**
	 * Constructor:
	 * Initializes a lot of variables, setting JavaScript functions in header etc.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
		// Main GPvars:
		$this->pointer = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pointer');
		$this->bparams = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('bparams');
		$this->P = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');
		$this->RTEtsConfigParams = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('RTEtsConfigParams');
		$this->expandPage = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('expandPage');
		$this->expandFolder = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('expandFolder');
		$this->PM = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('PM');
		// Find "mode"
		$this->mode = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('mode');
		if (!$this->mode) {
			$this->mode = 'rte';
		}
		// Creating backend template object:
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		// Load the Prototype library and browse_links.js
		$this->doc->getPageRenderer()->loadPrototype();
		$this->doc->loadJavascriptLib('js/browse_links.js');
		$this->doc->loadJavascriptLib('js/tree.js');
		// init hook objects:
		$this->hookObjects = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['browseLinksHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['browseLinksHook'] as $classData) {
				$processObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classData);
				if (!$processObject instanceof \TYPO3\CMS\Core\ElementBrowser\ElementBrowserHookInterface) {
					throw new \UnexpectedValueException('$processObject must implement interface TYPO3\\CMS\\Core\\ElementBrowser\\ElementBrowserHookInterface', 1195039394);
				}
				$parameters = array();
				$processObject->init($this, $parameters);
				$this->hookObjects[] = $processObject;
			}
		}
		// Site URL
		// Current site url
		$this->siteURL = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
		// The script to link to
		$this->thisScript = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_NAME');
		// Init fileProcessor
		$this->fileProcessor = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Utility\\File\\BasicFileUtility');
		$this->fileProcessor->init($GLOBALS['FILEMOUNTS'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
		// CurrentUrl - the current link url must be passed around if it exists
		if ($this->mode == 'wizard') {
			$currentValues = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(LF, trim($this->P['currentValue']));
			if (count($currentValues) > 0) {
				$currentValue = array_pop($currentValues);
			} else {
				$currentValue = '';
			}
			$currentLinkParts = \TYPO3\CMS\Core\Utility\GeneralUtility::unQuoteFilenames($currentValue, TRUE);
			$initialCurUrlArray = array(
				'href' => $currentLinkParts[0],
				'target' => $currentLinkParts[1],
				'class' => $currentLinkParts[2],
				'title' => $currentLinkParts[3],
				'params' => $currentLinkParts[4]
			);
			$this->curUrlArray = is_array(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('curUrl')) ? array_merge($initialCurUrlArray, \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('curUrl')) : $initialCurUrlArray;
			// Additional fields for page links
			if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['extendUrlArray']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['extendUrlArray'])) {
				$_params = array(
					'conf' => &$conf,
					'linkParts' => $currentLinkParts
				);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['extendUrlArray'] as $objRef) {
					$processor =& \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($objRef);
					$processor->extendUrlArray($_params, $this);
				}
			}
			$this->curUrlInfo = $this->parseCurUrl($this->siteURL . '?id=' . $this->curUrlArray['href'], $this->siteURL);
			// pageid == 0 means that this is not an internal (page) link
			if ($this->curUrlInfo['pageid'] == 0 && $this->curUrlArray['href']) {
				// Check if there is the FAL API
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($this->curUrlArray['href'], 'file:')) {
					$this->curUrlInfo = $this->parseCurUrl($this->curUrlArray['href'], $this->siteURL);
					// Remove the "file:" prefix
					$currentLinkParts[0] = rawurldecode(substr($this->curUrlArray['href'], 5));
				} elseif (file_exists(PATH_site . rawurldecode($this->curUrlArray['href']))) {
					if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($this->curUrlArray['href'], PATH_site)) {
						$currentLinkParts[0] = substr($this->curUrlArray['href'], strlen(PATH_site));
					}
					$this->curUrlInfo = $this->parseCurUrl($this->siteURL . $this->curUrlArray['href'], $this->siteURL);
				} elseif (strstr($this->curUrlArray['href'], '@')) {
					// check for email link
					if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($this->curUrlArray['href'], 'mailto:')) {
						$currentLinkParts[0] = substr($this->curUrlArray['href'], 7);
					}
					$this->curUrlInfo = $this->parseCurUrl('mailto:' . $this->curUrlArray['href'], $this->siteURL);
				} else {
					// nothing of the above. this is an external link
					if (strpos($this->curUrlArray['href'], '://') === FALSE) {
						$currentLinkParts[0] = 'http://' . $this->curUrlArray['href'];
					}
					$this->curUrlInfo = $this->parseCurUrl($currentLinkParts[0], $this->siteURL);
				}
			} elseif (!$this->curUrlArray['href']) {
				$this->curUrlInfo = array();
				$this->act = 'page';
			} else {
				$this->curUrlInfo = $this->parseCurUrl($this->siteURL . '?id=' . $this->curUrlArray['href'], $this->siteURL);
			}
		} else {
			$this->curUrlArray = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('curUrl');
			if ($this->curUrlArray['all']) {
				$this->curUrlArray = \TYPO3\CMS\Core\Utility\GeneralUtility::get_tag_attributes($this->curUrlArray['all']);
			}
			$this->curUrlInfo = $this->parseCurUrl($this->curUrlArray['href'], $this->siteURL);
		}
		// Determine nature of current url:
		$this->act = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('act');
		if (!$this->act) {
			$this->act = $this->curUrlInfo['act'];
		}
		// Rich Text Editor specific configuration:
		$addPassOnParams = '';
		if ((string) $this->mode == 'rte') {
			$RTEtsConfigParts = explode(':', $this->RTEtsConfigParams);
			$addPassOnParams .= '&RTEtsConfigParams=' . rawurlencode($this->RTEtsConfigParams);
			$RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($RTEtsConfigParts[5]));
			$this->thisConfig = \TYPO3\CMS\Backend\Utility\BackendUtility::RTEsetup($RTEsetup['properties'], $RTEtsConfigParts[0], $RTEtsConfigParts[2], $RTEtsConfigParts[4]);
		}
		// Initializing the target value (RTE)
		$this->setTarget = $this->curUrlArray['target'] != '-' ? rawurlencode($this->curUrlArray['target']) : '';
		if ($this->thisConfig['defaultLinkTarget'] && !isset($this->curUrlArray['target'])) {
			$this->setTarget = $this->thisConfig['defaultLinkTarget'];
		}
		// Initializing the class value (RTE)
		$this->setClass = $this->curUrlArray['class'] != '-' ? rawurlencode($this->curUrlArray['class']) : '';
		// Initializing the title value (RTE)
		$this->setTitle = $this->curUrlArray['title'] != '-' ? rawurlencode($this->curUrlArray['title']) : '';
		// Initializing the params value
		$this->setParams = $this->curUrlArray['params'] != '-' ? rawurlencode($this->curUrlArray['params']) : '';
		// BEGIN accumulation of header JavaScript:
		$JScode = '
				// This JavaScript is primarily for RTE/Link. jumpToUrl is used in the other cases as well...
			var add_href="' . ($this->curUrlArray['href'] ? '&curUrl[href]=' . rawurlencode($this->curUrlArray['href']) : '') . '";
			var add_target="' . ($this->setTarget ? '&curUrl[target]=' . rawurlencode($this->setTarget) : '') . '";
			var add_class="' . ($this->setClass ? '&curUrl[class]=' . rawurlencode($this->setClass) : '') . '";
			var add_title="' . ($this->setTitle ? '&curUrl[title]=' . rawurlencode($this->setTitle) : '') . '";
			var add_params="' . ($this->bparams ? '&bparams=' . rawurlencode($this->bparams) : '') . '";

			var cur_href="' . ($this->curUrlArray['href'] ? rawurlencode($this->curUrlArray['href']) : '') . '";
			var cur_target="' . ($this->setTarget ? $this->setTarget : '') . '";
			var cur_class = "' . ($this->setClass ? $this->setClass : '') . '";
			var cur_title="' . ($this->setTitle ? $this->setTitle : '') . '";
			var cur_params="' . ($this->setParams ? $this->setParams : '') . '";

			function browse_links_setTarget(target) {	//
				cur_target=target;
				add_target="&curUrl[target]="+escape(target);
			}
			function browse_links_setClass(cssClass) {   //
				cur_class = cssClass;
				add_class = "&curUrl[class]=" + escape(cssClass);
			}
			function browse_links_setTitle(title) {	//
				cur_title=title;
				add_title="&curUrl[title]="+escape(title);
			}
			function browse_links_setValue(value) {	//
				cur_href=value;
				add_href="&curUrl[href]="+value;
			}
			function browse_links_setParams(params) {	//
				cur_params=params;
				add_params="&curUrl[params]="+escape(params);
			}
		';
		// Functions used, if the link selector is in wizard mode (= TCEforms fields)
		if ($this->mode == 'wizard') {
			if (!$this->areFieldChangeFunctionsValid() && !$this->areFieldChangeFunctionsValid(TRUE)) {
				$this->P['fieldChangeFunc'] = array();
			}
			unset($this->P['fieldChangeFunc']['alert']);
			$update = '';
			foreach ($this->P['fieldChangeFunc'] as $k => $v) {
				$update .= '
				window.opener.' . $v;
			}
			$P2 = array();
			$P2['itemName'] = $this->P['itemName'];
			$P2['formName'] = $this->P['formName'];
			$P2['fieldChangeFunc'] = $this->P['fieldChangeFunc'];
			$P2['fieldChangeFuncHash'] = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac(serialize($this->P['fieldChangeFunc']));
			$P2['params']['allowedExtensions'] = $this->P['params']['allowedExtensions'];
			$P2['params']['blindLinkOptions'] = $this->P['params']['blindLinkOptions'];
			$P2['params']['blindLinkFields'] = $this->P['params']['blindLinkFields'];
			$addPassOnParams .= \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('P', $P2);
			$JScode .= '
				function link_typo3Page(id,anchor) {	//
					updateValueInMainForm(id + (anchor ? anchor : ""));
					close();
					return false;
				}
				function link_folder(folder) {	//
					updateValueInMainForm(folder);
					close();
					return false;
				}
				function link_current() {	//
					if (cur_href!="http://" && cur_href!="mailto:") {
						returnBeforeCleaned = cur_href;
						if (returnBeforeCleaned.substr(0, 7) == "http://") {
							returnToMainFormValue = returnBeforeCleaned.substr(7);
						} else if (returnBeforeCleaned.substr(0, 7) == "mailto:") {
							if (returnBeforeCleaned.substr(0, 14) == "mailto:mailto:") {
								returnToMainFormValue = returnBeforeCleaned.substr(14);
							} else {
								returnToMainFormValue = returnBeforeCleaned.substr(7);
							}
						} else {
							returnToMainFormValue = returnBeforeCleaned;
						}
						updateValueInMainForm(returnToMainFormValue);
						close();
					}
					return false;
				}
				function checkReference() {	//
					if (window.opener && window.opener.document && window.opener.document.' . $this->P['formName'] . ' && window.opener.document.' . $this->P['formName'] . '["' . $this->P['itemName'] . '"] ) {
						return window.opener.document.' . $this->P['formName'] . '["' . $this->P['itemName'] . '"];
					} else {
						close();
					}
				}
				function updateValueInMainForm(input) {	//
					var field = checkReference();
					if (field) {
						if (cur_target == "" && (cur_class != "" || cur_title != "" || cur_params != "")) {
							cur_target = "-";
						}
						if (cur_class == "" && (cur_title != "" || cur_params != "")) {
							cur_class = "-";
						}
						cur_class = cur_class.replace(/[\'\\"]/g, "");
						if (cur_class.indexOf(" ") != -1) {
							cur_class = "\\"" + cur_class + "\\"";
						}
						if (cur_title == "" && cur_params != "") {
 							cur_title = "-";
 						}
						cur_title = cur_title.replace(/(^\\")|(\\"$)/g, "");
						if (cur_title.indexOf(" ") != -1) {
							cur_title = "\\"" + cur_title + "\\"";
						}
						if (cur_params) {
							cur_params = cur_params.replace(/\\bid\\=.*?(\\&|$)/, "");
						}
						input = input + " " + cur_target + " " + cur_class + " " + cur_title + " " + cur_params;
						if(field.value && field.className.search(/textarea/) != -1) {
							field.value += "\\n" + input;
						} else {
							field.value = input;
						}
						' . $update . '
					}
				}
			';
		} else {
			// Functions used, if the link selector is in RTE mode:
			$JScode .= '
				function link_typo3Page(id,anchor) {	//
					var theLink = \'' . $this->siteURL . '?id=\'+id+(anchor?anchor:"");
					self.parent.parent.renderPopup_addLink(theLink, cur_target, cur_class, cur_title);
					return false;
				}
				function link_folder(folder) {	//
					var theLink = \'' . $this->siteURL . '\'+folder;
					self.parent.parent.renderPopup_addLink(theLink, cur_target, cur_class, cur_title);
					return false;
				}
				function link_spec(theLink) {	//
					self.parent.parent.renderPopup_addLink(theLink, cur_target, cur_class, cur_title);
					return false;
				}
				function link_current() {	//
					if (cur_href!="http://" && cur_href!="mailto:") {
						self.parent.parent.renderPopup_addLink(cur_href, cur_target, cur_class, cur_title);
					}
					return false;
				}
			';
		}
		// General "jumpToUrl" function:
		$JScode .= '
			function jumpToUrl(URL,anchor) {	//
				var add_act = URL.indexOf("act=")==-1 ? "&act=' . $this->act . '" : "";
				var add_mode = URL.indexOf("mode=")==-1 ? "&mode=' . $this->mode . '" : "";
				var theLocation = URL + add_act + add_mode + add_href + add_target + add_class + add_title + add_params' . ($addPassOnParams ? '+"' . $addPassOnParams . '"' : '') . '+(anchor?anchor:"");
				window.location.href = theLocation;
				return false;
			}
		';
		/**
		 * Splits parts of $this->bparams
		 *
		 * @see $bparams
		 */
		$pArr = explode('|', $this->bparams);
		// This is JavaScript especially for the TBE Element Browser!
		$formFieldName = 'data[' . $pArr[0] . '][' . $pArr[1] . '][' . $pArr[2] . ']';
		// insertElement - Call check function (e.g. for uniqueness handling):
		if ($pArr[4] && $pArr[5]) {
			$JScodeCheck = '
					// Call a check function in the opener window (e.g. for uniqueness handling):
				if (parent.window.opener) {
					var res = parent.window.opener.' . $pArr[5] . '("' . addslashes($pArr[4]) . '",table,uid,type);
					if (!res.passed) {
						if (res.message) alert(res.message);
						performAction = false;
					}
				} else {
					alert("Error - reference to main window is not set properly!");
					parent.close();
				}
			';
		}
		// insertElement - Call helper function:
		if ($pArr[4] && $pArr[6]) {
			$JScodeHelper = '
						// Call helper function to manage data in the opener window:
					if (parent.window.opener) {
						parent.window.opener.' . $pArr[6] . '("' . addslashes($pArr[4]) . '",table,uid,type,"' . addslashes($pArr[0]) . '");
					} else {
						alert("Error - reference to main window is not set properly!");
						parent.close();
					}
			';
		}
		// insertElement - perform action commands:
		if ($pArr[4] && $pArr[7]) {
			// Call user defined action function:
			$JScodeAction = '
					if (parent.window.opener) {
						parent.window.opener.' . $pArr[7] . '("' . addslashes($pArr[4]) . '",table,uid,type);
						if (close) { focusOpenerAndClose(close); }
					} else {
						alert("Error - reference to main window is not set properly!");
						if (close) { parent.close(); }
					}
			';
			$JScodeActionMultiple = '
						// Call helper function to manage data in the opener window:
					if (parent.window.opener) {
						parent.window.opener.' . $pArr[7] . 'Multiple("' . addslashes($pArr[4]) . '",table,uid,type,"' . addslashes($pArr[0]) . '");
					} else {
						alert("Error - reference to main window is not set properly!");
						parent.close();
					}
			';
		} elseif ($pArr[0] && !$pArr[1] && !$pArr[2]) {
			$JScodeAction = '
					addElement(filename,table+"_"+uid,fp,close);
			';
		} else {
			$JScodeAction = '
					if (setReferences()) {
						parent.window.opener.group_change("add","' . $pArr[0] . '","' . $pArr[1] . '","' . $pArr[2] . '",elRef,targetDoc);
					} else {
						alert("Error - reference to main window is not set properly!");
					}
					focusOpenerAndClose(close);
			';
		}
		$JScode .= '
			var elRef="";
			var targetDoc="";

			function launchView(url) {	//
				var thePreviewWindow="";
				thePreviewWindow = window.open("' . $GLOBALS['BACK_PATH'] . 'show_item.php?table="+url,"ShowItem","height=300,width=410,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");
				if (thePreviewWindow && thePreviewWindow.focus) {
					thePreviewWindow.focus();
				}
			}
			function setReferences() {	//
				if (parent.window.opener && parent.window.opener.content && parent.window.opener.content.document.editform && parent.window.opener.content.document.editform["' . $formFieldName . '"]) {
					targetDoc = parent.window.opener.content.document;
					elRef = targetDoc.editform["' . $formFieldName . '"];
					return true;
				} else {
					return false;
				}
			}
			function insertElement(table, uid, type, filename, fp, filetype, imagefile, action, close) {	//
				var performAction = true;
				' . $JScodeCheck . '
					// Call performing function and finish this action:
				if (performAction) {
						' . $JScodeHelper . $JScodeAction . '
				}
				return false;
			}
			function insertMultiple(table, uid) {
				var type = "";
						' . $JScodeActionMultiple . '
				return false;
			}
			function addElement(elName, elValue, altElValue, close) {	//
				if (parent.window.opener && parent.window.opener.setFormValueFromBrowseWin) {
					parent.window.opener.setFormValueFromBrowseWin("' . $pArr[0] . '",altElValue?altElValue:elValue,elName);
					focusOpenerAndClose(close);
				} else {
					alert("Error - reference to main window is not set properly!");
					parent.close();
				}
			}
			function focusOpenerAndClose(close) {	//
				BrowseLinks.focusOpenerAndClose(close);
			}
		';
		// extends JavaScript code
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['extendJScode']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['extendJScode'])) {
			$_params = array(
				'conf' => &$conf,
				'wizardUpdate' => $update,
				'addPassOnParams' => $addPassOnParams
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['extendJScode'] as $objRef) {
				$processor =& \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($objRef);
				$JScode .= $processor->extendJScode($_params, $this);
			}
		}
		// Finally, add the accumulated JavaScript to the template object:
		$this->doc->JScode .= $this->doc->wrapScriptTags($JScode);
	}

	/**
	 * Session data for this class can be set from outside with this method.
	 * Call after init()
	 *
	 * @param array $data Session data array
	 * @return array Session data and boolean which indicates that data needs to be stored in session because it's changed
	 * @todo Define visibility
	 */
	public function processSessionData($data) {
		$store = FALSE;
		switch ((string) $this->mode) {
		case 'db':
			if (isset($this->expandPage)) {
				$data['expandPage'] = $this->expandPage;
				$store = TRUE;
			} else {
				$this->expandPage = $data['expandPage'];
			}
			break;
		case 'file':

		case 'filedrag':

		case 'folder':
			if (isset($this->expandFolder)) {
				$data['expandFolder'] = $this->expandFolder;
				$store = TRUE;
			} else {
				$this->expandFolder = $data['expandFolder'];
			}
			break;
		}
		return array($data, $store);
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
	 * @param boolean $wiz If set, the "remove link" is not shown in the menu: Used for the "Select link" wizard which is used by the TCEforms
	 * @return string Modified content variable.
	 * @todo Define visibility
	 */
	public function main_rte($wiz = 0) {
		// Starting content:
		$content = $this->doc->startPage('RTE link');
		// Initializing the action value, possibly removing blinded values etc:
		$allowedItems = array_diff(explode(',', 'page,file,folder,url,mail,spec'), \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->thisConfig['blindLinkOptions'], 1));
		$allowedItems = array_diff($allowedItems, \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->P['params']['blindLinkOptions']));
		// Call hook for extra options
		foreach ($this->hookObjects as $hookObject) {
			$allowedItems = $hookObject->addAllowedItems($allowedItems);
		}
		// Removing link fields if configured
		$allowedFields = array_diff(array('target', 'title', 'class', 'params'), \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->thisConfig['blindLinkFields'], TRUE), \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->P['params']['blindLinkFields'], TRUE));
		// If $this->act is not allowed, default to first allowed
		if (!in_array($this->act, $allowedItems)) {
			$this->act = reset($allowedItems);
		}
		// Making menu in top:
		$menuDef = array();
		if (!$wiz) {
			$menuDef['removeLink']['isActive'] = $this->act == 'removeLink';
			$menuDef['removeLink']['label'] = $GLOBALS['LANG']->getLL('removeLink', 1);
			$menuDef['removeLink']['url'] = '#';
			$menuDef['removeLink']['addParams'] = 'onclick="self.parent.parent.renderPopup_unLink();return false;"';
		}
		if (in_array('page', $allowedItems)) {
			$menuDef['page']['isActive'] = $this->act == 'page';
			$menuDef['page']['label'] = $GLOBALS['LANG']->getLL('page', 1);
			$menuDef['page']['url'] = '#';
			$menuDef['page']['addParams'] = 'onclick="jumpToUrl(\'?act=page\');return false;"';
		}
		if (in_array('file', $allowedItems)) {
			$menuDef['file']['isActive'] = $this->act == 'file';
			$menuDef['file']['label'] = $GLOBALS['LANG']->getLL('file', 1);
			$menuDef['file']['url'] = '#';
			$menuDef['file']['addParams'] = 'onclick="jumpToUrl(\'?act=file\');return false;"';
		}
		if (in_array('folder', $allowedItems)) {
			$menuDef['folder']['isActive'] = $this->act == 'folder';
			$menuDef['folder']['label'] = $GLOBALS['LANG']->getLL('folder', 1);
			$menuDef['folder']['url'] = '#';
			$menuDef['folder']['addParams'] = 'onclick="jumpToUrl(\'?act=folder\');return false;"';
		}
		if (in_array('url', $allowedItems)) {
			$menuDef['url']['isActive'] = $this->act == 'url';
			$menuDef['url']['label'] = $GLOBALS['LANG']->getLL('extUrl', 1);
			$menuDef['url']['url'] = '#';
			$menuDef['url']['addParams'] = 'onclick="jumpToUrl(\'?act=url\');return false;"';
		}
		if (in_array('mail', $allowedItems)) {
			$menuDef['mail']['isActive'] = $this->act == 'mail';
			$menuDef['mail']['label'] = $GLOBALS['LANG']->getLL('email', 1);
			$menuDef['mail']['url'] = '#';
			$menuDef['mail']['addParams'] = 'onclick="jumpToUrl(\'?act=mail\');return false;"';
		}
		if (is_array($this->thisConfig['userLinks.']) && in_array('spec', $allowedItems)) {
			$menuDef['spec']['isActive'] = $this->act == 'spec';
			$menuDef['spec']['label'] = $GLOBALS['LANG']->getLL('special', 1);
			$menuDef['spec']['url'] = '#';
			$menuDef['spec']['addParams'] = 'onclick="jumpToUrl(\'?act=spec\');return false;"';
		}
		// Call hook for extra options
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
								<td style="width: 96px;">' . $GLOBALS['LANG']->getLL('emailAddress', 1) . ':</td>
								<td><input type="text" name="lemail"' . $this->doc->formWidth(20) . ' value="' . htmlspecialchars(($this->curUrlInfo['act'] == 'mail' ? $this->curUrlInfo['info'] : '')) . '" /> ' . '<input type="submit" value="' . $GLOBALS['LANG']->getLL('setLink', 1) . '" onclick="browse_links_setTarget(\'\');browse_links_setValue(\'mailto:\'+document.lurlform.lemail.value); return link_current();" /></td>
							</tr>
						</table>
					</form>';
			$content .= $extUrl;
			break;
		case 'url':
			$extUrl = '

			<!--
				Enter External URL:
			-->
					<form action="" name="lurlform" id="lurlform">
						<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkURL">
							<tr>
								<td style="width: 96px;">URL:</td>
								<td><input type="text" name="lurl"' . $this->doc->formWidth(30) . ' value="' . htmlspecialchars(($this->curUrlInfo['act'] == 'url' ? $this->curUrlInfo['info'] : 'http://')) . '" /> ' . '<input type="submit" value="' . $GLOBALS['LANG']->getLL('setLink', 1) . '" onclick="browse_links_setValue(document.lurlform.lurl.value); return link_current();" /></td>
							</tr>
						</table>
					</form>';
			$content .= $extUrl;
			break;
		case 'file':

		case 'folder':
			$foldertree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('rteFolderTree');
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
			// Create upload/create folder forms, if a path is given
			if ($this->expandFolder) {
				$selectedFolder = FALSE;
				$fileOrFolderObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->expandFolder);

				if ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
					// It's a folder
					$selectedFolder = $fileOrFolderObject;
				} elseif ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
					// It's a file
					// @todo: find the parent folder, right now done a bit ugly, because the file does not
					// support finding the parent folder of a file on purpose
					$folderIdentifier = dirname($fileOrFolderObject->getIdentifier());
					$selectedFolder = $fileOrFolderObject->getStorage()->getFolder($folderIdentifier);
				}
			}
			// Or get the user's default upload folder
			if (!$selectedFolder) {
				$selectedFolder = $GLOBALS['BE_USER']->getDefaultUploadFolder();
			}
			// Build the file upload and folder creation form
			$uploadForm = '';
			$createFolder = '';
			if ($selectedFolder && !$this->isReadOnlyFolder($selectedFolder)) {
				$uploadForm = ($this->act === 'file') ? $this->uploadForm($selectedFolder) : '';
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
			$this->doc->JScode .= $this->doc->wrapScriptTags('
			Tree.ajaxID = "SC_alt_file_navframe::expandCollapse";
		');
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
					<br />
					';
			// Adding create folder + upload forms if applicable
			if (!$GLOBALS['BE_USER']->getTSConfigVal('options.uploadFieldsInTopOfEB')) {
				$content .= $uploadForm;
			}
			$content .= $createFolder . '<br />';
			break;
		case 'spec':
			if (is_array($this->thisConfig['userLinks.'])) {
				$subcats = array();
				$v = $this->thisConfig['userLinks.'];
				foreach ($v as $k2 => $value) {
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
							$onClickEvent .= 'browse_links_setTarget(\'' . $v[($k2i . '.')]['target'] . '\');';
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
			$pagetree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('rtePageTree');
			$pagetree->thisScript = $this->thisScript;
			$pagetree->ext_showPageId = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showPageIdWithTitle');
			$pagetree->ext_showNavTitle = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showNavTitle');
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
			// Call hook
			foreach ($this->hookObjects as $hookObject) {
				$content .= $hookObject->getTab($this->act);
			}
			break;
		}
		if (in_array('params', $allowedFields, TRUE)) {
			$content .= '
				<!--
					Selecting params for link:
				-->
				<form action="" name="lparamsform" id="lparamsform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkParams">
						<tr>
							<td style="width: 96px;">' . $GLOBALS['LANG']->getLL('params', 1) . '</td>
							<td><input type="text" name="lparams" class="typo3-link-input" onchange="browse_links_setParams(this.value);" value="' . htmlspecialchars($this->setParams) . '" /></td>
						</tr>
					</table>
				</form>
			';
		}
		if (in_array('class', $allowedFields, TRUE)) {
			$content .= '
				<!--
					Selecting class for link:
				-->
				<form action="" name="lclassform" id="lclassform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkClass">
						<tr>
							<td style="width: 96px;">' . $GLOBALS['LANG']->getLL('class', 1) . '</td>
							<td><input type="text" name="lclass" class="typo3-link-input" onchange="browse_links_setClass(this.value);" value="' . htmlspecialchars($this->setClass) . '" /></td>
						</tr>
					</table>
				</form>
			';
		}
		if (in_array('title', $allowedFields, TRUE)) {
			$content .= '
				<!--
					Selecting title for link:
				-->
				<form action="" name="ltitleform" id="ltitleform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkTitle">
						<tr>
							<td style="width: 96px;">' . $GLOBALS['LANG']->getLL('title', 1) . '</td>
							<td><input type="text" name="ltitle" class="typo3-link-input" onchange="browse_links_setTitle(this.value);" value="' . htmlspecialchars($this->setTitle) . '" /></td>
						</tr>
					</table>
				</form>
			';
		}
		// additional fields for page links
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['addFields_PageLink']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['addFields_PageLink'])) {
			$_params = array(
				'conf' => &$conf
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['addFields_PageLink'] as $objRef) {
				$processor =& \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($objRef);
				$content .= $processor->addFields($_params, $this);
			}
		}
		// Target:
		if ($this->act != 'mail' && in_array('target', $allowedFields, TRUE)) {
			$ltarget = '

			<!--
				Selecting target for link:
			-->
				<form action="" name="ltargetform" id="ltargetform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkTarget">
						<tr>
							<td>' . $GLOBALS['LANG']->getLL('target', 1) . ':</td>
							<td><input type="text" name="ltarget" onchange="browse_links_setTarget(this.value);" value="' . htmlspecialchars($this->setTarget) . '"' . $this->doc->formWidth(10) . ' /></td>
							<td>
								<select name="ltarget_type" onchange="browse_links_setTarget(this.options[this.selectedIndex].value);document.ltargetform.ltarget.value=this.options[this.selectedIndex].value;this.selectedIndex=0;">
									<option></option>
									<option value="_top">' . $GLOBALS['LANG']->getLL('top', 1) . '</option>
									<option value="_blank">' . $GLOBALS['LANG']->getLL('newWindow', 1) . '</option>
								</select>
							</td>
							<td>';
			if (($this->curUrlInfo['act'] == 'page' || $this->curUrlInfo['act'] == 'file' || $this->curUrlInfo['act'] == 'folder') && $this->curUrlArray['href'] && $this->curUrlInfo['act'] == $this->act) {
				$ltarget .= '
							<input type="submit" value="' . $GLOBALS['LANG']->getLL('update', 1) . '" onclick="return link_current();" />';
			}
			$selectJS = '
				if (document.ltargetform.popup_width.options[document.ltargetform.popup_width.selectedIndex].value>0 && document.ltargetform.popup_height.options[document.ltargetform.popup_height.selectedIndex].value>0) {
					document.ltargetform.ltarget.value = document.ltargetform.popup_width.options[document.ltargetform.popup_width.selectedIndex].value+"x"+document.ltargetformbrowse_links_setTarget.popup_height.options[document.ltargetform.popup_height.selectedIndex].value;
					browse_links_setTarget(document.ltargetform.ltarget.value);
					browse_links_setClass(document.lclassform.lclass.value);
					browse_links_setTitle(document.ltitleform.ltitle.value);
					browse_links_setParams(document.lparamsform.lparams.value);
					document.ltargetform.popup_width.selectedIndex=0;
					document.ltargetform.popup_height.selectedIndex=0;
				}
			';
			$ltarget .= '		</td>
						</tr>
						<tr>
							<td>' . $GLOBALS['LANG']->getLL('target_popUpWindow', 1) . ':</td>
							<td colspan="3">
								<select name="popup_width" onchange="' . htmlspecialchars($selectJS) . '">
									<option value="0">' . $GLOBALS['LANG']->getLL('target_popUpWindow_width', 1) . '</option>
									<option value="300">300</option>
									<option value="400">400</option>
									<option value="500">500</option>
									<option value="600">600</option>
									<option value="700">700</option>
									<option value="800">800</option>
								</select>
								x
								<select name="popup_height" onchange="' . htmlspecialchars($selectJS) . '">
									<option value="0">' . $GLOBALS['LANG']->getLL('target_popUpWindow_height', 1) . '</option>
									<option value="200">200</option>
									<option value="300">300</option>
									<option value="400">400</option>
									<option value="500">500</option>
									<option value="600">600</option>
								</select>
							</td>
						</tr>
					</table>
				</form>';
			// Add "target selector" box to content:
			$content .= $ltarget;
			// Add some space
			$content .= '<br /><br />';
		}
		// End page, return content:
		$content .= $this->doc->endPage();
		$content = $this->doc->insertStylesAndJS($content);
		return $content;
	}

	/**
	 * TYPO3 Element Browser: Showing a page tree and allows you to browse for records
	 *
	 * @return string HTML content for the module
	 * @todo Define visibility
	 */
	public function main_db() {
		// Starting content:
		$content = $this->doc->startPage('TBE record selector');
		// Init variable:
		$pArr = explode('|', $this->bparams);
		// Making the browsable pagetree:
		$pagetree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TBE_PageTree');
		$pagetree->thisScript = $this->thisScript;
		$pagetree->ext_pArrPages = !strcmp($pArr[3], 'pages') ? 1 : 0;
		$pagetree->ext_showNavTitle = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showNavTitle');
		$pagetree->ext_showPageId = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showPageIdWithTitle');
		$pagetree->addField('nav_title');
		$tree = $pagetree->getBrowsableTree();
		// Making the list of elements, if applicable:
		$cElements = $this->TBE_expandPage($pArr[3]);
		// Putting the things together, side by side:
		$content .= '

			<!--
				Wrapper table for page tree / record list:
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-EBrecords">
				<tr>
					<td class="c-wCell" valign="top">' . $this->barheader(($GLOBALS['LANG']->getLL('pageTree') . ':')) . $tree . '</td>
					<td class="c-wCell" valign="top">' . $cElements . '</td>
				</tr>
			</table>
			';
		// Add some space
		$content .= '<br /><br />';
		// End page, return content:
		$content .= $this->doc->endPage();
		$content = $this->doc->insertStylesAndJS($content);
		return $content;
	}

	/**
	 * TYPO3 Element Browser: Showing a folder tree, allowing you to browse for files.
	 *
	 * @return string HTML content for the module
	 * @todo Define visibility
	 */
	public function main_file() {
		// include JS files and set prefs for foldertree
		$this->doc->getDragDropCode('folders');
		$this->doc->JScode .= $this->doc->wrapScriptTags('
			Tree.ajaxID = "SC_alt_file_navframe::expandCollapse";
		');
		// Starting content:
		$content = $this->doc->startPage('TBE file selector');
		// Init variable:
		$pArr = explode('|', $this->bparams);
		// The key number 3 of the pArr contains the "allowed" string. Disallowed is not passed to the element browser at all but only filtered out in TCEMain afterwards
		$allowed = $pArr[3];
		if ($allowed !== 'sys_file') {
			$allowedFileExtensions = $allowed;
		}
		$this->storages = $GLOBALS['BE_USER']->getFileStorages();
		if (isset($allowedFileExtensions)) {
			// Create new filter object
			$filterObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Filter\\FileExtensionFilter');
			$filterObject->setAllowedFileExtensions($allowedFileExtensions);
			// Set file extension filters on all storages
			/** @var $storage \TYPO3\CMS\Core\Resource\ResourceStorage */
			foreach ($this->storages as $storage) {
				$storage->addFileAndFolderNameFilter(array($filterObject, 'filterFileList'));
			}
		}
		// Create upload/create folder forms, if a path is given
		if ($this->expandFolder) {
			$this->selectedFolder = FALSE;
			$fileOrFolderObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->expandFolder);
			if ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
				// It's a folder
				$this->selectedFolder = $fileOrFolderObject;
			} elseif ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
				// It's a file
				// @todo: find the parent folder, right now done a bit ugly, because the file does not
				// support finding the parent folder of a file on purpose
				$folderIdentifier = dirname($fileOrFolderObject->getIdentifier());
				$this->selectedFolder = $fileOrFolderObject->getStorage()->getFolder($folderIdentifier);
			}
		}
		// Or get the user's default upload folder
		if (!$this->selectedFolder) {
			$this->selectedFolder = $GLOBALS['BE_USER']->getDefaultUploadFolder();
		}
			// Build the file upload and folder creation form
		$uploadForm = '';
		$createFolder = '';
		if ($this->selectedFolder && !$this->isReadOnlyFolder($this->selectedFolder)) {
			$uploadForm = ($this->act === 'file') ? $this->uploadForm($this->selectedFolder) : '';
			if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.createFoldersInEB')) {
				$createFolder =  $this->createFolder($this->selectedFolder);
			}
		}
		if ($this->selectedFolder) {
			$uploadForm = $this->uploadForm($this->selectedFolder);
			$createFolder = $this->createFolder($this->selectedFolder);
		} else {
			$uploadForm = $createFolder = '';
		}
		// Insert the upload form on top, if so configured
		if ($GLOBALS['BE_USER']->getTSConfigVal('options.uploadFieldsInTopOfEB')) {
			$content .= $uploadForm;
		}
		// Getting flag for showing/not showing thumbnails:
		$noThumbs = $GLOBALS['BE_USER']->getTSConfigVal('options.noThumbsInEB');
		if (!$noThumbs) {
			// MENU-ITEMS, fetching the setting for thumbnails from File>List module:
			$_MOD_MENU = array('displayThumbs' => '');
			$_MCONF['name'] = 'file_list';
			$_MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData($_MOD_MENU, \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SET'), $_MCONF['name']);
		}
		$noThumbs = $noThumbs ? $noThumbs : !$_MOD_SETTINGS['displayThumbs'];
		// Create folder tree:
		$foldertree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TBE_FolderTree');
		$foldertree->thisScript = $this->thisScript;
		$foldertree->ext_noTempRecyclerDirs = $this->mode == 'filedrag';
		$tree = $foldertree->getBrowsableTree();
		list(, , $specUid) = explode('_', $this->PM);
		if ($this->mode == 'filedrag') {
			$files = $this->TBE_dragNDrop($this->selectedFolder, $pArr[3]);
		} else {
			$files = $this->TBE_expandFolder($this->selectedFolder, $pArr[3], $noThumbs);
		}
		// Putting the parts together, side by side:
		$content .= '

			<!--
				Wrapper table for folder tree / file list:
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-EBfiles">
				<tr>
					<td class="c-wCell" valign="top">' . $this->barheader(($GLOBALS['LANG']->getLL('folderTree') . ':')) . $tree . '</td>
					<td class="c-wCell" valign="top">' . $files . '</td>
				</tr>
			</table>
			';
		// Adding create folder + upload forms if applicable:
		if (!$GLOBALS['BE_USER']->getTSConfigVal('options.uploadFieldsInTopOfEB')) {
			$content .= $uploadForm;
		}
		$content .= $createFolder;
		// Add some space
		$content .= '<br /><br />';
		// Setup indexed elements:
		$this->doc->JScode .= $this->doc->wrapScriptTags('BrowseLinks.addElements(' . json_encode($this->elements) . ');');
		// Ending page, returning content:
		$content .= $this->doc->endPage();
		$content = $this->doc->insertStylesAndJS($content);
		return $content;
	}

	/**
	 * TYPO3 Element Browser: Showing a folder tree, allowing you to browse for folders.
	 *
	 * @return string HTML content for the module
	 * @todo Define visibility
	 */
	public function main_folder() {
		// include JS files
		$this->doc->getDragDropCode('folders');
		// Setting prefs for foldertree
		$this->doc->JScode .= $this->doc->wrapScriptTags('
			Tree.ajaxID = "SC_alt_file_navframe::expandCollapse";
		');
		// Starting content:
		$content = $this->doc->startPage('TBE folder selector');
		// Init variable:
		$parameters = explode('|', $this->bparams);
		if ($this->expandFolder) {
			$this->selectedFolder = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFolderObjectFromCombinedIdentifier($this->expandFolder);
		}
		if ($this->selectedFolder && !$this->isReadOnlyFolder($this->selectedFolder)) {
			$createFolder = $this->createFolder($this->selectedFolder);
		} else {
			$createFolder = '';
		}
		// Create folder tree:
		$foldertree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TBE_FolderTree');
		$foldertree->thisScript = $this->thisScript;
		$foldertree->ext_noTempRecyclerDirs = $this->mode == 'filedrag';
		$tree = $foldertree->getBrowsableTree(FALSE);
		list(, , $specUid) = explode('_', $this->PM);
		if ($this->mode == 'filedrag') {
			$folders = $this->TBE_dragNDrop($this->selectedFolder, $parameters[3]);
		} else {
			$folders = $this->TBE_expandSubFolders($this->selectedFolder);
		}
		// Putting the parts together, side by side:
		$content .= '

			<!--
				Wrapper table for folder tree / folder list:
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-EBfiles">
				<tr>
					<td class="c-wCell" valign="top">' . $this->barheader(($GLOBALS['LANG']->getLL('folderTree') . ':')) . $tree . '</td>
					<td class="c-wCell" valign="top">' . $folders . '</td>
				</tr>
			</table>
			';
		// Adding create folder if applicable:
		if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.createFoldersInEB')) {
			$content .= $createFolder;
		}
		// Add some space
		$content .= '<br /><br />';
		// Ending page, returning content:
		$content .= $this->doc->endPage();
		$content = $this->doc->insertStylesAndJS($content);
		return $content;
	}

	/******************************************************************
	 *
	 * Record listing
	 *
	 ******************************************************************/
	/**
	 * For RTE: This displays all content elements on a page and lets you create a link to the element.
	 *
	 * @return string HTML output. Returns content only if the ->expandPage value is set (pointing to a page uid to show tt_content records from ...)
	 * @todo Define visibility
	 */
	public function expandPage() {
		$out = '';
		// Set page id (if any) to expand
		$expPageId = $this->expandPage;
		// If there is an anchor value (content element reference) in the element reference, then force an ID to expand:
		if (!$this->expandPage && $this->curUrlInfo['cElement']) {
			// Set to the current link page id.
			$expPageId = $this->curUrlInfo['pageid'];
		}
		// Draw the record list IF there is a page id to expand:
		if ($expPageId && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($expPageId) && $GLOBALS['BE_USER']->isInWebMount($expPageId)) {
			// Set header:
			$out .= $this->barheader($GLOBALS['LANG']->getLL('contentElements') . ':');
			// Create header for listing, showing the page title/icon:
			$titleLen = intval($GLOBALS['BE_USER']->uc['titleLen']);
			$mainPageRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('pages', $expPageId);
			$picon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', $mainPageRec);
			$picon .= \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle('pages', $mainPageRec, TRUE);
			$out .= $picon . '<br />';
			// Look up tt_content elements from the expanded page:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,header,hidden,starttime,endtime,fe_group,CType,colPos,bodytext', 'tt_content', 'pid=' . intval($expPageId) . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tt_content') . \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('tt_content'), '', 'colPos,sorting');
			$cc = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
			// Traverse list of records:
			$c = 0;
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$c++;
				$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('tt_content', $row);
				if ($this->curUrlInfo['act'] == 'page' && $this->curUrlInfo['cElement'] == $row['uid']) {
					$arrCol = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/blinkarrow_left.gif', 'width="5" height="9"') . ' class="c-blinkArrowL" alt="" />';
				} else {
					$arrCol = '';
				}
				// Putting list element HTML together:
				$out .= '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], ('gfx/ol/join' . ($c == $cc ? 'bottom' : '') . '.gif'), 'width="18" height="16"') . ' alt="" />' . $arrCol . '<a href="#" onclick="return link_typo3Page(\'' . $expPageId . '\',\'#' . $row['uid'] . '\');">' . $icon . \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle('tt_content', $row, TRUE) . '</a><br />';
				// Finding internal anchor points:
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('text,textpic', $row['CType'])) {
					$split = preg_split('/(<a[^>]+name=[\'"]?([^"\'>[:space:]]+)[\'"]?[^>]*>)/i', $row['bodytext'], -1, PREG_SPLIT_DELIM_CAPTURE);
					foreach ($split as $skey => $sval) {
						if ($skey % 3 == 2) {
							// Putting list element HTML together:
							$sval = substr($sval, 0, 100);
							$out .= '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/ol/line.gif', 'width="18" height="16"') . ' alt="" />' . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], ('gfx/ol/join' . ($skey + 3 > count($split) ? 'bottom' : '') . '.gif'), 'width="18" height="16"') . ' alt="" />' . '<a href="#" onclick="return link_typo3Page(\'' . $expPageId . '\',\'#' . rawurlencode($sval) . '\');">' . htmlspecialchars((' <A> ' . $sval)) . '</a><br />';
						}
					}
				}
			}
		}
		return $out;
	}

	/**
	 * For TYPO3 Element Browser: This lists all content elements from the given list of tables
	 *
	 * @param string $tables Commalist of tables. Set to "*" if you want all tables.
	 * @return string HTML output.
	 * @todo Define visibility
	 */
	public function TBE_expandPage($tables) {
		$out = '';
		if ($this->expandPage >= 0 && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->expandPage) && $GLOBALS['BE_USER']->isInWebMount($this->expandPage)) {
			// Set array with table names to list:
			if (!strcmp(trim($tables), '*')) {
				$tablesArr = array_keys($GLOBALS['TCA']);
			} else {
				$tablesArr = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $tables, 1);
			}
			reset($tablesArr);
			// Headline for selecting records:
			$out .= $this->barheader($GLOBALS['LANG']->getLL('selectRecords') . ':');
			// Create the header, showing the current page for which the listing is. Includes link to the page itself, if pages are amount allowed tables.
			$titleLen = intval($GLOBALS['BE_USER']->uc['titleLen']);
			$mainPageRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('pages', $this->expandPage);
			$ATag = '';
			$ATag_e = '';
			$ATag2 = '';
			$picon = '';
			if (is_array($mainPageRec)) {
				$picon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', $mainPageRec);
				if (in_array('pages', $tablesArr)) {
					$ATag = '<a href="#" onclick="return insertElement(\'pages\', \'' . $mainPageRec['uid'] . '\', \'db\', ' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($mainPageRec['title']) . ', \'\', \'\', \'\',\'\',1);">';
					$ATag2 = '<a href="#" onclick="return insertElement(\'pages\', \'' . $mainPageRec['uid'] . '\', \'db\', ' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($mainPageRec['title']) . ', \'\', \'\', \'\',\'\',0);">';
					$ATag_alt = substr($ATag, 0, -4) . ',\'\',1);">';
					$ATag_e = '</a>';
				}
			}
			$pBicon = $ATag2 ? '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/plusbullet2.gif', 'width="18" height="16"') . ' alt="" />' : '';
			$pText = htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($mainPageRec['title'], $titleLen));
			$out .= $picon . $ATag2 . $pBicon . $ATag_e . $ATag . $pText . $ATag_e . '<br />';
			// Initialize the record listing:
			$id = $this->expandPage;
			$pointer = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->pointer, 0, 100000);
			$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
			$pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($id, $perms_clause);
			$table = '';
			// Generate the record list:
			/** @var $dblist \TYPO3\CMS\Backend\RecordList\ElementBrowserRecordList */
			$dblist = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\RecordList\\ElementBrowserRecordList');
			$dblist->thisScript = $this->thisScript;
			$dblist->backPath = $GLOBALS['BACK_PATH'];
			$dblist->thumbs = 0;
			$dblist->calcPerms = $GLOBALS['BE_USER']->calcPerms($pageinfo);
			$dblist->noControlPanels = 1;
			$dblist->clickMenuEnabled = 0;
			$dblist->tableList = implode(',', $tablesArr);
			$pArr = explode('|', $this->bparams);
			// a string like "data[pages][79][storage_pid]"
			$fieldPointerString = $pArr[0];
			// parts like: data, pages], 79], storage_pid]
			$fieldPointerParts = explode('[', $fieldPointerString);
			$relatingTableName = substr($fieldPointerParts[1], 0, -1);
			$relatingFieldName = substr($fieldPointerParts[3], 0, -1);
			if ($relatingTableName && $relatingFieldName) {
				$dblist->setRelatingTableAndField($relatingTableName, $relatingFieldName);
			}
			$dblist->start($id, \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('table'), $pointer, \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('search_field'), \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('search_levels'), \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('showLimit'));
			$dblist->setDispFields();
			$dblist->generateList();
			$dblist->writeBottom();
			//	Add the HTML for the record list to output variable:
			$out .= $dblist->HTMLcode;
			// Add support for fieldselectbox in singleTableMode
			if ($dblist->table) {
				$out .= $dblist->fieldSelectBox($dblist->table);
			}
			$out .= $dblist->getSearchBox();
		}
		// Return accumulated content:
		return $out;
	}

	/**
	 * Render list of folders inside a folder.
	 *
	 * @param string $folder String of the current folder
	 * @return string HTML output
	 * @todo Define visibility
	 */
	public function TBE_expandSubFolders(\TYPO3\CMS\Core\Resource\Folder $folder) {
		$content = '';
		if ($folder->checkActionPermission('read')) {
			$content .= $this->folderList($folder);
		}
		// Return accumulated content for folderlisting:
		return $content;
	}

	/******************************************************************
	 *
	 * File listing
	 *
	 ******************************************************************/
	/**
	 * For RTE: This displays all files from folder. No thumbnails shown
	 *
	 * @param string $folder The folder path to expand
	 * @param string $extensionList List of fileextensions to show
	 * @return string HTML output
	 * @todo Define visibility
	 */
	public function expandFolder(\TYPO3\CMS\Core\Resource\Folder $folder, $extensionList = '') {
		$out = '';
		$renderFolders = $this->act === 'folder';
		if ($folder->checkActionPermission('read')) {
			// Create header for filelisting:
			$out .= $this->barheader($GLOBALS['LANG']->getLL('files') . ':');
			// Prepare current path value for comparison (showing red arrow)
			$currentIdentifier = '';
			if ($this->curUrlInfo['value']) {
				$currentIdentifier = $this->curUrlInfo['info'];
			}
			// Create header element; The folder from which files are listed.
			$titleLen = intval($GLOBALS['BE_USER']->uc['titleLen']);
			$folderIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile('folder');
			$folderIcon .= htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($folder->getIdentifier(), $titleLen));
			$picon = '<a href="#" onclick="return link_folder(\'file:' . $folder->getCombinedIdentifier() . '\');">' . $folderIcon . '</a>';
			if ($this->curUrlInfo['act'] == 'folder' && $currentIdentifier == $folder->getCombinedIdentifier()) {
				$out .= '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/blinkarrow_left.gif', 'width="5" height="9"') . ' class="c-blinkArrowL" alt="" />';
			}
			$out .= $picon . '<br />';
			// Get files from the folder:
			if ($renderFolders) {
				$items = $folder->getSubfolders();
			} else {
				$filter = new \TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter();
				$filter->setAllowedFileExtensions($extensionList);
				$folder->getStorage()->setFileAndFolderNameFilters(array(array($filter, 'filterFileList')));

				$items = $folder->getFiles();
			}
			$c = 0;
			$totalItems = count($items);
			foreach ($items as $fileOrFolderObject) {
				$c++;
				if ($renderFolders) {
					$fileIdentifier = $fileOrFolderObject->getCombinedIdentifier();
					$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile('folder');
					$itemUid = 'file:' . $fileIdentifier;
				} else {
					$fileIdentifier = $fileOrFolderObject->getUid();
					// File icon:
					$fileExtension = $fileOrFolderObject->getExtension();
					// Get size and icon:
					$size = ' (' . \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($fileOrFolderObject->getSize()) . 'bytes)';
					$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile($fileExtension, array('title' => $fileOrFolderObject->getName() . $size));
					$itemUid = 'file:' . $fileIdentifier;
				}
				// If the listed file turns out to be the CURRENT file, then show blinking arrow:
				if (($this->curUrlInfo['act'] == 'file' || $this->curUrlInfo['act'] == 'folder') && $currentIdentifier == $fileIdentifier) {
					$arrCol = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/blinkarrow_left.gif', 'width="5" height="9"') . ' class="c-blinkArrowL" alt="" />';
				} else {
					$arrCol = '';
				}
				// Put it all together for the file element:
				$out .= '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], ('gfx/ol/join' . ($c == $totalItems ? 'bottom' : '') . '.gif'), 'width="18" height="16"') . ' alt="" />' . $arrCol . '<a href="#" onclick="return link_folder(\'' . $itemUid . '\');">' . $icon . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($fileOrFolderObject->getName(), $titleLen)) . '</a><br />';
			}
		}
		return $out;
	}

	/**
	 * For TYPO3 Element Browser: Expand folder of files.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder The folder path to expand
	 * @param string $extensionList List of fileextensions to show
	 * @param boolean $noThumbs Whether to show thumbnails or not. If set, no thumbnails are shown.
	 * @return string HTML output
	 * @todo Define visibility
	 */
	public function TBE_expandFolder(\TYPO3\CMS\Core\Resource\Folder $folder, $extensionList = '', $noThumbs = 0) {
		$extensionList = $extensionList == '*' ? '' : $extensionList;
		$content = '';
		if ($folder->checkActionPermission('read')) {
			// Listing the files:
			$files = $folder->getFiles($extensionList);
			$content = $this->fileList($files, $folder, $noThumbs);
		}
		// Return accumulated content for filelisting:
		return $content;
	}

	/**
	 * Render list of files.
	 *
	 * @param array $files List of files. See \TYPO3\CMS\Core\Utility\GeneralUtility::getFilesInDir
	 * @param string \TYPO3\CMS\Core\Resource\Folder $folder If set a header with a folder icon and folder name are shown
	 * @param boolean $noThumbs Whether to show thumbnails or not. If set, no thumbnails are shown.
	 * @return string HTML output
	 * @todo Define visibility
	 */
	public function fileList(array $files, \TYPO3\CMS\Core\Resource\Folder $folder = NULL, $noThumbs = 0) {
		$out = '';
		// Listing the files:
		if (is_array($files)) {
			$lines = array();
			// Create headline (showing number of files):
			$filesCount = count($files);
			$out .= $this->barheader(sprintf($GLOBALS['LANG']->getLL('files') . ' (%s):', $filesCount));
			$out .= '<div id="filelist">';
			$out .= $this->getBulkSelector($filesCount);
			$titleLen = intval($GLOBALS['BE_USER']->uc['titleLen']);
			// Create the header of current folder:
			if ($folder) {
				$folderIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile('folder');
				$lines[] = '<tr class="t3-row-header">
					<td colspan="4">' . $folderIcon . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($folder->getIdentifier(), $titleLen)) . '</td>
				</tr>';
			}
			if ($filesCount == 0) {
				$lines[] = '
					<tr class="file_list_normal">
						<td colspan="4">No files found.</td>
					</tr>';
			}
			// Init graphic object for reading file dimensions:
			/** @var $imgObj \TYPO3\CMS\Core\Imaging\GraphicalFunctions */
			$imgObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions');
			$imgObj->init();
			$imgObj->mayScaleUp = 0;
			$imgObj->tempPath = PATH_site . $imgObj->tempPath;
			// Traverse the file list:
			foreach ($files as $fileObject) {
				$fileExtension = $fileObject->getExtension();
				// Thumbnail/size generation:
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList(strtolower($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']), strtolower($fileExtension)) && !$noThumbs) {
					$imageUrl = $fileObject->process(\TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGEPREVIEW, array('width' => 64, 'height' => 64))->getPublicUrl(TRUE);
					$imgInfo = $imgObj->getImageDimensions($fileObject->getForLocalProcessing(FALSE));
					$pDim = $imgInfo[0] . 'x' . $imgInfo[1] . ' pixels';
					$clickIcon = '<img src="' . $imageUrl . '" hspace="5" vspace="5" border="1"';
				} else {
					$clickIcon = '';
					$pDim = '';
				}
				// Create file icon:
				$size = ' (' . \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($fileObject->getSize()) . 'bytes' . ($pDim ? ', ' . $pDim : '') . ')';
				$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile($fileExtension, array('title' => $fileObject->getName() . $size));
				// Create links for adding the file:
				$filesIndex = count($this->elements);
				$this->elements['file_' . $filesIndex] = array(
					'type' => 'file',
					'table' => 'sys_file',
					'uid' => $fileObject->getUid(),
					'fileName' => $fileObject->getName(),
					'filePath' => $fileObject->getUid(),
					'fileExt' => $fileExtension,
					'fileIcon' => $icon
				);
				$ATag = '<a href="#" onclick="return BrowseLinks.File.insertElement(\'file_' . $filesIndex . '\');">';
				$ATag_alt = substr($ATag, 0, -4) . ',1);">';
				$bulkCheckBox = '<input type="checkbox" class="typo3-bulk-item" name="file_' . $filesIndex . '" value="0" /> ';
				$ATag_e = '</a>';
				// Create link to showing details about the file in a window:
				$Ahref = $GLOBALS['BACK_PATH'] . 'show_item.php?type=file&table=_FILE&uid=' . rawurlencode($fileObject->getCombinedIdentifier()) . '&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'));
				$ATag2 = '<a href="' . htmlspecialchars($Ahref) . '">';
				$ATag2_e = '</a>';
				// Combine the stuff:
				$filenameAndIcon = $bulkCheckBox . $ATag_alt . $icon . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($fileObject->getName(), $titleLen)) . $ATag_e;
				// Show element:
				// Image...
				if ($pDim) {
					$lines[] = '
						<tr class="file_list_normal">
							<td nowrap="nowrap">' . $filenameAndIcon . '&nbsp;</td>
							<td>' . $ATag . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/plusbullet2.gif', 'width="18" height="16"') . ' title="' . $GLOBALS['LANG']->getLL('addToList', 1) . '" alt="" />' . $ATag_e . '</td>
							<td nowrap="nowrap">' . ($ATag2 . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/zoom2.gif', 'width="12" height="12"') . ' title="' . $GLOBALS['LANG']->getLL('info', 1) . '" alt="" /> ' . $GLOBALS['LANG']->getLL('info', 1) . $ATag2_e) . '</td>
							<td nowrap="nowrap">&nbsp;' . $pDim . '</td>
						</tr>';
					$lines[] = '
						<tr>
							<td class="filelistThumbnail" colspan="4">' . $ATag_alt . $clickIcon . $ATag_e . '</td>
						</tr>';
				} else {
					$lines[] = '
						<tr class="file_list_normal">
							<td nowrap="nowrap">' . $filenameAndIcon . '&nbsp;</td>
							<td>' . $ATag . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/plusbullet2.gif', 'width="18" height="16"') . ' title="' . $GLOBALS['LANG']->getLL('addToList', 1) . '" alt="" />' . $ATag_e . '</td>
							<td nowrap="nowrap">' . ($ATag2 . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/zoom2.gif', 'width="12" height="12"') . ' title="' . $GLOBALS['LANG']->getLL('info', 1) . '" alt="" /> ' . $GLOBALS['LANG']->getLL('info', 1) . $ATag2_e) . '</td>
							<td>&nbsp;</td>
						</tr>';
				}
			}
			// Wrap all the rows in table tags:
			$out .= '

		<!--
			File listing
		-->
				<table cellpadding="0" cellspacing="0" id="typo3-filelist">
					' . implode('', $lines) . '
				</table>';
		}
		// Return accumulated content for filelisting:
		$out .= '</div>';
		return $out;
	}

	/**
	 * Render list of folders.
	 *
	 * @param array $baseFolder List of folders. See \TYPO3\CMS\Core\Utility\GeneralUtility::get_dirs
	 * @param string $folders If set a header with a folder icon and folder name are shown
	 * @return string HTML output
	 * @todo Define visibility
	 */
	public function folderList($baseFolder, $folders) {
		$content = '';
		// Create headline (showing number of folders):
		$content .= $this->barheader(sprintf($GLOBALS['LANG']->getLL('folders') . ' (%s):', count($folders)));
		$titleLength = intval($GLOBALS['BE_USER']->uc['titleLen']);
		// Create the header of current folder:
		if ($baseFolder) {
			if (strstr($baseFolder, ',') || strstr($baseFolder, '|')) {
				// In case an invalid character is in the filepath, display error message:
				$errorMessage = $GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->getLL('invalidChar'), ', |'));
				$aTag = ($aTag_alt = '<a href="#" onclick="alert(' . $errorMessage . ');return false;">');
			} else {
				// If foldername is OK, just add it:
				$aTag = '<a href="#" onclick="return insertElement(\'\',\'' . rawurlencode($baseFolder) . '\', \'folder\', \'' . rawurlencode($baseFolder) . '\', unescape(\'' . rawurlencode($baseFolder) . '\'), \'' . $fI['extension'] . '\', \'' . $ficon . '\');">';
				$aTag_alt = substr($aTag, 0, -4) . ',\'\',1);">';
			}
			$aTag_e = '</a>';
			// Add the foder icon
			$folderIcon = $aTag_alt;
			$folderIcon .= '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/i/_icon_webfolders.gif', 'width="18" height="16"') . ' alt="" />';
			$folderIcon .= htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs(basename($baseFolder), $titleLength));
			$folderIcon .= $aTag_e;
			$content .= $folderIcon . '<br />';
		}
		// Listing the folders:
		if (is_array($folders)) {
			if (count($folders) > 0) {
				// Traverse the folder list:
				$lines = array();
				foreach ($folders as $folderPath) {
					$pathInfo = pathinfo($folderPath);
					// Create folder icon:
					$icon = '<img src="clear.gif" width="16" height="16" alt="" /><img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/i/_icon_webfolders.gif', 'width="16" height="16"') . ' title="' . htmlspecialchars(($pathInfo['basename'] . $size)) . '" class="absmiddle" alt="" />';
					// Create links for adding the folder:
					if ($this->P['itemName'] != '' && $this->P['formName'] != '') {
						$aTag = '<a href="#" onclick="return set_folderpath(unescape(\'' . rawurlencode($folderPath) . '\'));">';
					} else {
						$aTag = '<a href="#" onclick="return insertElement(\'\',\'' . rawurlencode($folderPath) . '\', \'folder\', \'' . rawurlencode($folderPath) . '\', unescape(\'' . rawurlencode($folderPath) . '\'), \'' . $pathInfo['extension'] . '\', \'' . $ficon . '\');">';
					}
					if (strstr($folderPath, ',') || strstr($folderPath, '|')) {
						// In case an invalid character is in the filepath, display error message:
						$errorMessage = $GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->getLL('invalidChar'), ', |'));
						$aTag = ($aTag_alt = '<a href="#" onclick="alert(' . $errorMessage . ');return false;">');
					} else {
						// If foldername is OK, just add it:
						$aTag_alt = substr($aTag, 0, -4) . ',\'\',1);">';
					}
					$aTag_e = '</a>';
					// Combine icon and folderpath:
					$foldernameAndIcon = $aTag_alt . $icon . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs(basename($folderPath), $titleLength)) . $aTag_e;
					if ($this->P['itemName'] != '') {
						$lines[] = '
							<tr class="bgColor4">
								<td nowrap="nowrap">' . $foldernameAndIcon . '&nbsp;</td>
								<td>&nbsp;</td>
							</tr>';
					} else {
						$lines[] = '
							<tr class="bgColor4">
								<td nowrap="nowrap">' . $foldernameAndIcon . '&nbsp;</td>
								<td>' . $aTag . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/plusbullet2.gif', 'width="18" height="16"') . ' title="' . $GLOBALS['LANG']->getLL('addToList', 1) . '" alt="" />' . $aTag_e . ' </td>
								<td>&nbsp;</td>
							</tr>';
					}
					$lines[] = '
							<tr>
								<td colspan="3"><img src="clear.gif" width="1" height="3" alt="" /></td>
							</tr>';
				}
			}
			// Wrap all the rows in table tags:
			$content .= '

		<!--
			Folder listing
		-->
				<table border="0" cellpadding="0" cellspacing="1" id="typo3-folderList">
					' . implode('', $lines) . '
				</table>';
		}
		// Return accumulated content for folderlisting:
		return $content;
	}

	/**
	 * For RTE: This displays all IMAGES (gif,png,jpg) (from extensionList) from folder. Thumbnails are shown for images.
	 * This listing is of images located in the web-accessible paths ONLY - the listing is for drag-n-drop use in the RTE
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder The folder path to expand
	 * @param string $extensionList List of fileextensions to show
	 * @return string HTML output
	 * @todo Define visibility
	 */
	public function TBE_dragNDrop(\TYPO3\CMS\Core\Resource\Folder $folder, $extensionList = '') {
		$extensionList = $extensionList == '*' ? '' : $extensionList;
		$out = '';
		if ($folder) {
			if ($folder->getStorage()->isPublic()) {
				// Read files from directory:
				$files = $folder->getFiles($extensionList);
				if (is_array($files)) {
					$out .= $this->barheader(sprintf($GLOBALS['LANG']->getLL('files') . ' (%s):', count($files)));
					$titleLen = intval($GLOBALS['BE_USER']->uc['titleLen']);
					$picon = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/i/_icon_webfolders.gif', 'width="18" height="16"') . ' alt="" />';
					$picon .= htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs(basename($folder->getName()), $titleLen));
					$out .= $picon . '<br />';
					// Init row-array:
					$lines = array();
					// Add "drag-n-drop" message:
					$lines[] = '
						<tr>
							<td colspan="2">' . $this->getMsgBox($GLOBALS['LANG']->getLL('findDragDrop')) . '</td>
						</tr>';
					// Traverse files:
					foreach ($files as $fileObject) {
						$fileInfo = $fileObject->getStorage()->getFileInfo($fileObject);
						// URL of image:
						$iUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::rawurlencodeFP($fileObject->getPublicUrl(TRUE));
						// Show only web-images
						$fileExtension = strtolower($fileObject->getExtension());
						if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('gif,jpeg,jpg,png', $fileExtension)) {
							$imgInfo = @getimagesize($fileObject->getForLocalProcessing(FALSE));
							$pDim = $imgInfo[0] . 'x' . $imgInfo[1] . ' pixels';
							$size = ' (' . \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($fileObject->getSize()) . 'bytes' . ($pDim ? ', ' . $pDim : '') . ')';
							$filenameAndIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile($fileExtension, array('title' => $fileObject->getName() . $size));
							if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('noLimit')) {
								$maxW = 10000;
								$maxH = 10000;
							} else {
								$maxW = 380;
								$maxH = 500;
							}
							$IW = $imgInfo[0];
							$IH = $imgInfo[1];
							if ($IW > $maxW) {
								$IH = ceil($IH / $IW * $maxW);
								$IW = $maxW;
							}
							if ($IH > $maxH) {
								$IW = ceil($IW / $IH * $maxH);
								$IH = $maxH;
							}
							// Make row:
							$lines[] = '
								<tr class="bgColor4">
									<td nowrap="nowrap">' . $filenameAndIcon . '&nbsp;</td>
									<td nowrap="nowrap">' . ($imgInfo[0] != $IW ? '<a href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('noLimit' => '1'))) . '">' . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/icon_warning2.gif', 'width="18" height="16"') . ' title="' . $GLOBALS['LANG']->getLL('clickToRedrawFullSize', 1) . '" alt="" />' . '</a>' : '') . $pDim . '&nbsp;</td>
								</tr>';
							$lines[] = '
								<tr>
									<td colspan="2"><img src="' . $iUrl . '" data-htmlarea-file-uid="' . $fileObject->getUid() . '" width="' . $IW . '" height="' . $IH . '" border="1" alt="" /></td>
								</tr>';
							$lines[] = '
								<tr>
									<td colspan="2"><img src="clear.gif" width="1" height="3" alt="" /></td>
								</tr>';
						}
					}
					// Finally, wrap all rows in a table tag:
					$out .= '


			<!--
				File listing / Drag-n-drop
			-->
						<table border="0" cellpadding="0" cellspacing="1" id="typo3-dragBox">
							' . implode('', $lines) . '
						</table>';
				}
			} else {
				// Print this warning if the folder is NOT a web folder:
				$out .= $this->barheader($GLOBALS['LANG']->getLL('files'));
				$out .= $this->getMsgBox($GLOBALS['LANG']->getLL('noWebFolder'), 'icon_warning2');
			}
		}
		return $out;
	}

	/******************************************************************
	 *
	 * Miscellaneous functions
	 *
	 ******************************************************************/
	/**
	 * Verifies that a path is a web-folder:
	 *
	 * @param string $folder Absolute filepath
	 * @return boolean If the input path is found in PATH_site then it returns TRUE.
	 * @todo Define visibility
	 */
	public function isWebFolder($folder) {
		$folder = rtrim($folder, '/') . '/';
		return \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($folder, PATH_site) ? TRUE : FALSE;
	}

	/**
	 * Checks, if a path is within the mountpoints of the backend user
	 *
	 * @param string $folder Absolute filepath
	 * @return boolean If the input path is found in the backend users filemounts, then return TRUE.
	 * @todo Define visibility
	 */
	public function checkFolder($folder) {
		return $this->fileProcessor->checkPathAgainstMounts(rtrim($folder, '/') . '/') ? TRUE : FALSE;
	}

	/**
	 * Checks, if a path is within a read-only mountpoint of the backend user
	 *
	 * @param string $folder Absolute filepath
	 * @return boolean If the input path is found in the backend users filemounts and if the filemount is of type readonly, then return TRUE.
	 * @todo Define visibility
	 */
	public function isReadOnlyFolder($folder) {
		return $GLOBALS['FILEMOUNTS'][$this->fileProcessor->checkPathAgainstMounts(rtrim($folder, '/') . '/')]['type'] == 'readonly';
	}

	/**
	 * Prints a 'header' where string is in a tablecell
	 *
	 * @param string $str The string to print in the header. The value is htmlspecialchars()'ed before output.
	 * @return string The header HTML (wrapped in a table)
	 * @todo Define visibility
	 */
	public function barheader($str) {
		return '
			<!-- Bar header: -->
			<h3>' . htmlspecialchars($str) . '</h3>
			';
	}

	/**
	 * Displays a message box with the input message
	 *
	 * @param string $in_msg Input message to show (will be htmlspecialchars()'ed inside of this function)
	 * @param string $icon Icon filename body from gfx/ (default is "icon_note") - meant to allow change to warning type icons...
	 * @return string HTML for the message (wrapped in a table).
	 * @todo Define visibility
	 */
	public function getMsgBox($in_msg, $icon = 'icon_note') {
		$msg = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], ('gfx/' . $icon . '.gif'), 'width="18" height="16"') . ' alt="" />' . htmlspecialchars($in_msg);
		$msg = '

			<!--
				Message box:
			-->
			<table cellspacing="0" class="bgColor4" id="typo3-msgBox">
				<tr>
					<td>' . $msg . '</td>
				</tr>
			</table>
			';
		return $msg;
	}

	/**
	 * For RTE/link: This prints the 'currentUrl'
	 *
	 * @param string $str URL value. The value is htmlspecialchars()'ed before output.
	 * @return string HTML content, wrapped in a table.
	 * @todo Define visibility
	 */
	public function printCurrentUrl($str) {
		// Output the folder or file identifier, when working with files
		if ($str && $this->act == 'file' || $this->act == 'folder') {
			try {
				$fileObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($str);
				if (is_object($fileObject)) {
					$str = $fileObject->getIdentifier();
				}
			} catch (\TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException $e) {
				$str = '';
			}
		}
		if (strlen($str)) {
			return '
				<!-- Print current URL -->
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-curUrl">
					<tr>
						<td>' . $GLOBALS['LANG']->getLL('currentLink', 1) . ': ' . htmlspecialchars(rawurldecode($str)) . '</td>
					</tr>
				</table>';
		} else {
			return '';
		}
	}

	/**
	 * For RTE/link: Parses the incoming URL and determines if it's a page, file, external or mail address.
	 *
	 * @param string $href HREF value tp analyse
	 * @param string $siteUrl The URL of the current website (frontend)
	 * @return array Array with URL information stored in assoc. keys: value, act (page, file, spec, mail), pageid, cElement, info
	 * @todo Define visibility
	 */
	public function parseCurUrl($href, $siteUrl) {
		$href = trim($href);
		if ($href) {
			$info = array();
			// Default is "url":
			$info['value'] = $href;
			$info['act'] = 'url';
			$specialParts = explode('#_SPECIAL', $href);
			// Special kind (Something RTE specific: User configurable links through: "userLinks." from ->thisConfig)
			if (count($specialParts) == 2) {
				$info['value'] = '#_SPECIAL' . $specialParts[1];
				$info['act'] = 'spec';
			} elseif (strpos($href, 'file:') !== FALSE) {
				$rel = substr($href, strpos($href, 'file:') + 5);
				$rel = rawurldecode($rel);
				// resolve FAL-api "file:UID-of-sys_file-record" and "file:combined-identifier"
				$fileOrFolderObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($rel);
				if ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
					$info['act'] = 'folder';
					$info['value'] = $fileOrFolderObject->getCombinedIdentifier();
				} elseif ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
					$info['act'] = 'file';
					$info['value'] = $fileOrFolderObject->getUid();
				} else {
					$info['value'] = $rel;
				}
			} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($href, $siteUrl)) {
				// If URL is on the current frontend website:
				// URL is a file, which exists:
				if (file_exists(PATH_site . rawurldecode($href))) {
					$info['value'] = rawurldecode($href);
					if (@is_dir((PATH_site . $info['value']))) {
						$info['act'] = 'folder';
					} else {
						$info['act'] = 'file';
					}
				} else {
					// URL is a page (id parameter)
					$uP = parse_url($href);

					$pp = preg_split('/^id=/', $uP['query']);
					$pp[1] = preg_replace('/&id=[^&]*/', '', $pp[1]);
					$parameters = explode('&', $pp[1]);
					$id = array_shift($parameters);
					if ($id) {
						// Checking if the id-parameter is an alias.
						if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($id)) {
							list($idPartR) = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField('pages', 'alias', $id);
							$id = intval($idPartR['uid']);
						}
						$pageRow = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('pages', $id);
						$titleLen = intval($GLOBALS['BE_USER']->uc['titleLen']);
						$info['value'] = ((((($GLOBALS['LANG']->getLL('page', 1) . ' \'') . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($pageRow['title'], $titleLen))) . '\' (ID:') . $id) . ($uP['fragment'] ? ', #' . $uP['fragment'] : '')) . ')';
						$info['pageid'] = $id;
						$info['cElement'] = $uP['fragment'];
						$info['act'] = 'page';
						$info['query'] = $parameters[0] ? '&' . implode('&', $parameters) : '';
					}
				}
			} else {
				// Email link:
				if (strtolower(substr($href, 0, 7)) == 'mailto:') {
					$info['value'] = trim(substr($href, 7));
					$info['act'] = 'mail';
				}
			}
			$info['info'] = $info['value'];
		} else {
			// NO value inputted:
			$info = array();
			$info['info'] = $GLOBALS['LANG']->getLL('none');
			$info['value'] = '';
			$info['act'] = 'page';
		}
		// let the hook have a look
		foreach ($this->hookObjects as $hookObject) {
			$info = $hookObject->parseCurrentUrl($href, $siteUrl, $info);
		}
		return $info;
	}

	/**
	 * For TBE: Makes an upload form for uploading files to the filemount the user is browsing.
	 * The files are uploaded to the tce_file.php script in the core which will handle the upload.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject Absolute filepath on server to which to upload.
	 * @return string HTML for an upload form.
	 * @todo Define visibility
	 */
	public function uploadForm(\TYPO3\CMS\Core\Resource\Folder $folderObject) {
		if (!$folderObject->checkActionPermission('write')) {
			return '';
		}
		// Read configuration of upload field count
		$userSetting = $GLOBALS['BE_USER']->getTSConfigVal('options.folderTree.uploadFieldsInLinkBrowser');
		$count = isset($userSetting) ? $userSetting : 3;
		if ($count === '0') {
			return '';
		}
		$count = intval($count) == 0 ? 3 : intval($count);
		// Create header, showing upload path:
		$header = $folderObject->getIdentifier();
		$code = '
			<br />
			<!--
				Form, for uploading files:
			-->
			<form action="' . $GLOBALS['BACK_PATH'] . 'tce_file.php" method="post" name="editform" id="typo3-uplFilesForm" enctype="' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] . '">
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-uplFiles">
					<tr>
						<td>' . $this->barheader(($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:file_upload.php.pagetitle', 1) . ':')) . '</td>
					</tr>
					<tr>
						<td class="c-wCell c-hCell"><strong>' . $GLOBALS['LANG']->getLL('path', 1) . ':</strong> ' . htmlspecialchars($header) . '</td>
					</tr>
					<tr>
						<td class="c-wCell c-hCell">';
		// Traverse the number of upload fields (default is 3):
		for ($a = 1; $a <= $count; $a++) {
			$code .= '<input type="file" multiple="multiple" name="upload_' . $a . '"' . $this->doc->formWidth(35) . ' size="50" />
				<input type="hidden" name="file[upload][' . $a . '][target]" value="' . htmlspecialchars($folderObject->getCombinedIdentifier()) . '" />
				<input type="hidden" name="file[upload][' . $a . '][data]" value="' . $a . '" /><br />';
		}
		// Make footer of upload form, including the submit button:
		$redirectValue = $this->thisScript . '?act=' . $this->act . '&mode=' . $this->mode . '&expandFolder=' . rawurlencode($folderObject->getCombinedIdentifier()) . '&bparams=' . rawurlencode($this->bparams);
		$code .= '<input type="hidden" name="redirect" value="' . htmlspecialchars($redirectValue) . '" />';
		$code .= '
			<div id="c-override">
				<label><input type="checkbox" name="overwriteExistingFiles" id="overwriteExistingFiles" value="1" /> ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xlf:overwriteExistingFiles', 1) . '</label>
			</div>
			<input type="submit" name="submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:file_upload.php.submit', 1) . '" />
		';
		$code .= '</td>
					</tr>
				</table>
			</form><br />';
		return $code;
	}

	/**
	 * For TBE: Makes a form for creating new folders in the filemount the user is browsing.
	 * The folder creation request is sent to the tce_file.php script in the core which will handle the creation.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject Absolute filepath on server in which to create the new folder.
	 * @return string HTML for the create folder form.
	 * @todo Define visibility
	 */
	public function createFolder(\TYPO3\CMS\Core\Resource\Folder $folderObject) {
		if (!$folderObject->checkActionPermission('write')) {
			return '';
		}
		// Don't show Folder-create form if it's denied
		if ($GLOBALS['BE_USER']->getTSConfigVal('options.folderTree.hideCreateFolder')) {
			return '';
		}
		// Create header, showing upload path:
		$header = $folderObject->getIdentifier();
		$code = '

			<!--
				Form, for creating new folders:
			-->
			<form action="' . $GLOBALS['BACK_PATH'] . 'tce_file.php" method="post" name="editform2" id="typo3-crFolderForm">
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-crFolder">
					<tr>
						<td>' . $this->barheader(($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:file_newfolder.php.pagetitle') . ':')) . '</td>
					</tr>
					<tr>
						<td class="c-wCell c-hCell"><strong>' . $GLOBALS['LANG']->getLL('path', 1) . ':</strong> ' . htmlspecialchars($header) . '</td>
					</tr>
					<tr>
						<td class="c-wCell c-hCell">';
		// Create the new-folder name field:
		$a = 1;
		$code .= '<input' . $this->doc->formWidth(20) . ' type="text" name="file[newfolder][' . $a . '][data]" />' . '<input type="hidden" name="file[newfolder][' . $a . '][target]" value="' . htmlspecialchars($folderObject->getCombinedIdentifier()) . '" />';
		// Make footer of upload form, including the submit button:
		$redirectValue = $this->thisScript . '?act=' . $this->act . '&mode=' . $this->mode . '&expandFolder=' . rawurlencode($folderObject->getCombinedIdentifier()) . '&bparams=' . rawurlencode($this->bparams);
		$code .= '<input type="hidden" name="redirect" value="' . htmlspecialchars($redirectValue) . '" />' . '<input type="submit" name="submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:file_newfolder.php.submit', 1) . '" />';
		$code .= '</td>
					</tr>
				</table>
			</form>';
		return $code;
	}

	/**
	 * Get the HTML data required for a bulk selection of files of the TYPO3 Element Browser.
	 *
	 * @param integer $filesCount Number of files currently displayed
	 * @return string HTML data required for a bulk selection of files - if $filesCount is 0, nothing is returned
	 * @todo Define visibility
	 */
	public function getBulkSelector($filesCount) {
		if ($filesCount) {
			$labelToggleSelection = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_browse_links.xlf:toggleSelection', 1);
			$labelImportSelection = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_browse_links.xlf:importSelection', 1);
			// Getting flag for showing/not showing thumbnails:
			$noThumbsInEB = $GLOBALS['BE_USER']->getTSConfigVal('options.noThumbsInEB');
			$out = $this->doc->spacer(10) . '<div>' . '<a href="#" onclick="BrowseLinks.Selector.handle()">' . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/import.gif', 'width="12" height="12"') . ' title="' . $labelImportSelection . '" alt="" /> ' . $labelImportSelection . '</a>&nbsp;&nbsp;&nbsp;' . '<a href="#" onclick="BrowseLinks.Selector.toggle()">' . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/clip_select.gif', 'width="12" height="12"') . ' title="' . $labelToggleSelection . '" alt="" /> ' . $labelToggleSelection . '</a>' . '</div>';
			$thumbNailCheck = '';
			if (!$noThumbsInEB && $this->selectedFolder) {
				// MENU-ITEMS, fetching the setting for thumbnails from File>List module:
				$_MOD_MENU = array('displayThumbs' => '');
				$_MCONF['name'] = 'file_list';
				$_MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData($_MOD_MENU, \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SET'), $_MCONF['name']);
				$addParams = '&act=' . $this->act . '&mode=' . $this->mode . '&expandFolder=' . rawurlencode($this->selectedFolder->getCombinedIdentifier()) . '&bparams=' . rawurlencode($this->bparams);
				$thumbNailCheck = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck('', 'SET[displayThumbs]', $_MOD_SETTINGS['displayThumbs'], $this->thisScript, $addParams, 'id="checkDisplayThumbs"') . ' <label for="checkDisplayThumbs">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:displayThumbs', 1) . '</label>';
				$out .= $this->doc->spacer(5) . $thumbNailCheck . $this->doc->spacer(15);
			} else {
				$out .= $this->doc->spacer(15);
			}
		}
		return $out;
	}

	/**
	 * Determines whether submitted field change functions are valid
	 * and are coming from the system and not from an external abuse.
	 *
	 * @param boolean $handleFlexformSections Whether to handle flexform sections differently
	 * @return boolean Whether the submitted field change functions are valid
	 */
	protected function areFieldChangeFunctionsValid($handleFlexformSections = FALSE) {
		$result = FALSE;
		if (isset($this->P['fieldChangeFunc']) && is_array($this->P['fieldChangeFunc']) && isset($this->P['fieldChangeFuncHash'])) {
			$matches = array();
			$pattern = '#\\[el\\]\\[(([^]-]+-[^]-]+-)(idx\\d+-)([^]]+))\\]#i';
			$fieldChangeFunctions = $this->P['fieldChangeFunc'];
			// Special handling of flexform sections:
			// Field change functions are modified in JavaScript, thus the hash is always invalid
			if ($handleFlexformSections && preg_match($pattern, $this->P['itemName'], $matches)) {
				$originalName = $matches[1];
				$cleanedName = $matches[2] . $matches[4];
				foreach ($fieldChangeFunctions as &$value) {
					$value = str_replace($originalName, $cleanedName, $value);
				}
				unset($value);
			}
			$result = $this->P['fieldChangeFuncHash'] === \TYPO3\CMS\Core\Utility\GeneralUtility::hmac(serialize($fieldChangeFunctions));
		}
		return $result;
	}

}


?>
