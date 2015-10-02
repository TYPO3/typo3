<?php
namespace TYPO3\CMS\Rtehtmlarea;

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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Recordlist\Browser\ElementBrowser;

/**
 * Script class for the Element Browser window.
 */
class BrowseLinks extends ElementBrowser {

	/**
	 * @var array
	 */
	public $additionalAttributes = array();

	/**
	 * @var array
	 */
	public $anchorTypes = array('page', 'url', 'file', 'mail');

	/**
	 * @var array
	 */
	public $classesAnchorDefault = array();

	/**
	 * @var array
	 */
	public $classesAnchorDefaultTitle = array();

	/**
	 * @var array
	 */
	public $classesAnchorClassTitle = array();

	/**
	 * @var array
	 */
	public $classesAnchorDefaultTarget = array();

	/**
	 * @var array
	 */
	public $classesAnchorJSOptions = array();

	/**
	 * @var
	 */
	protected $defaultLinkTarget;

	/**
	 * Initialize the current or default values of the link attributes
	 *
	 * @return void
	 */
	protected function initLinkAttributes() {
		// Initializing the class value
		$this->setClass = isset($this->curUrlArray['class']) ? $this->curUrlArray['class'] : '';
		// Processing the classes configuration
		if (!empty($this->buttonConfig['properties.']['class.']['allowedClasses'])) {
			$classesAnchorArray = GeneralUtility::trimExplode(',', $this->buttonConfig['properties.']['class.']['allowedClasses'], TRUE);
			// Collecting allowed classes and configured default values
			$classesAnchor = array();
			$classesAnchor['all'] = array();
			$titleReadOnly = $this->buttonConfig['properties.']['title.']['readOnly']
				|| $this->buttonConfig[$this->act . '.']['properties.']['title.']['readOnly'];
			if (is_array($this->RTEProperties['classesAnchor.'])) {
				foreach ($this->RTEProperties['classesAnchor.'] as $label => $conf) {
					if (in_array($conf['class'], $classesAnchorArray)) {
						$classesAnchor['all'][] = $conf['class'];
						if (in_array($conf['type'], $this->anchorTypes)) {
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
			// Constructing the class selector options
			foreach ($this->anchorTypes as $anchorType) {
				$currentClass = $this->curUrlInfo['act'] === $anchorType ? $this->curUrlArray['class'] : '';
				foreach ($classesAnchorArray as $class) {
					if (!in_array($class, $classesAnchor['all']) || in_array($class, $classesAnchor['all']) && is_array($classesAnchor[$anchorType]) && in_array($class, $classesAnchor[$anchorType])) {
						$selected = '';
						if ($currentClass == $class || !$currentClass && $this->classesAnchorDefault[$anchorType] == $class) {
							$selected = 'selected="selected"';
						}
						$classLabel = !empty($this->RTEProperties['classes.'][$class . '.']['name'])
							? $this->getPageConfigLabel($this->RTEProperties['classes.'][$class . '.']['name'], 0)
							: $class;
						$classStyle = !empty($this->RTEProperties['classes.'][$class . '.']['value'])
							? $this->RTEProperties['classes.'][$class . '.']['value']
							: '';
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
		// Initializing the title value
		$this->setTitle = isset($this->curUrlArray['title']) ? $this->curUrlArray['title'] : '';
		// Initializing the target value
		$this->setTarget = isset($this->curUrlArray['target']) ? $this->curUrlArray['target'] : '';
		// Default target
		$this->defaultLinkTarget = $this->classesAnchorDefault[$this->act] && $this->classesAnchorDefaultTarget[$this->act]
			? $this->classesAnchorDefaultTarget[$this->act]
			: (isset($this->buttonConfig[$this->act . '.']['properties.']['target.']['default'])
				? $this->buttonConfig[$this->act . '.']['properties.']['target.']['default']
				: (isset($this->buttonConfig['properties.']['target.']['default'])
					? $this->buttonConfig['properties.']['target.']['default']
					: ''));
		// Initializing additional attributes
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['TYPO3Link']['additionalAttributes']) {
			$addAttributes = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['TYPO3Link']['additionalAttributes'], TRUE);
			foreach ($addAttributes as $attribute) {
				$this->additionalAttributes[$attribute] = isset($this->curUrlArray[$attribute]) ? $this->curUrlArray[$attribute] : '';
			}
		}
	}

	/**
	 * Generate JS code to be used on the link insert/modify dialogue
	 *
	 * @return string the generated JS code
	 */
	public function getJSCode() {
		// BEGIN accumulation of header JavaScript:
		$JScode = '';
		// Attributes setting functions
		$JScode .= '
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
				browse_links_setAdditionalValue("data-htmlarea-external", "");
				plugin.createLink(theLink,cur_target,cur_class,cur_title,additionalValues);
				return false;
			}
		';

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
	 * @param bool $wiz If set, the "remove link" is not shown in the menu: Used for the "Select link" wizard which is used by the TCEforms
	 * @return string Modified content variable.
	 */
	protected function main_rte($wiz = FALSE) {
		// Starting content:
		$content = $this->doc->startPage($this->getLanguageService()->getLL('Insert/Modify Link', TRUE));
		// Making menu in top:
		$content .= $this->doc->getTabMenuRaw($this->buildMenuArray($wiz, $this->getAllowedItems('page,file,folder,url,mail')));
		// Adding the menu and header to the top of page:
		$content .= $this->printCurrentUrl($this->curUrlInfo['info']) . '<br />';
		// Depending on the current action we will create the actual module content for selecting a link:
		switch ($this->act) {
			case 'mail':
				$extUrl = $this->getEmailSelectorHtml();
				$content .= $this->addAttributesForm($extUrl);
				break;
			case 'url':
				$extUrl = $this->getExternalUrlSelectorHtml();
				$content .= $this->addAttributesForm($extUrl);
				break;
			case 'file':
			case 'folder':
				$content .= $this->addAttributesForm();
				$content .= $this->getFileSelectorHtml(FolderTree::class);
				break;
			case 'page':
				$content .= $this->addAttributesForm();
				$content .= $this->getPageSelectorHtml(PageTree::class);
				break;
			default:
				// call hook
				foreach ($this->hookObjects as $hookObject) {
					$content .= $hookObject->getTab($this->act);
				}
		}
		// End page, return content:
		$content .= $this->doc->endPage();
		$content = $this->doc->insertStylesAndJS($content);
		return $content;
	}

	/**
	 * Returns HTML of the email link from
	 *
	 * @return string
	 */
	protected function getEmailSelectorHtml() {
		$extUrl = '
			<!--
				Enter mail address:
			-->
			<tr>
				<td>
					<label>
						' . $this->getLanguageService()->getLL('emailAddress', TRUE) . ':
					</label>
				</td>
				<td>
					<input type="text" name="lemail"' . $this->doc->formWidth(20)
						. ' value="' . htmlspecialchars(($this->curUrlInfo['act'] == 'mail' ? $this->curUrlInfo['info'] : '')) . '" />
					<input class="btn btn-default" type="submit" value="' . $this->getLanguageService()->getLL('setLink', TRUE)
						. '" onclick="browse_links_setTarget(\'\');browse_links_setHref(\'mailto:\'+document.ltargetform.lemail.value);'
						. 'browse_links_setAdditionalValue(\'data-htmlarea-external\', \'\');return link_current();" />
				</td>
			</tr>';
		return $extUrl;
	}

	/**
	 * Returns HTML of the external url link from
	 *
	 * @return string
	 */
	protected function getExternalUrlSelectorHtml() {
		$extUrl = '
			<!--
				Enter External URL:
			-->
			<tr>
				<td>
					<label>
						URL:
					</label>
				</td>
				<td colspan="3">
					<input type="text" name="lurl"' . $this->doc->formWidth(20)
						. ' value="' . htmlspecialchars(($this->curUrlInfo['act'] == 'url' ? $this->curUrlInfo['info'] : 'http://'))
						. '" />
					<input class="btn btn-default" type="submit" value="' . $this->getLanguageService()->getLL('setLink', TRUE)
						. '" onclick="if (/^[A-Za-z0-9_+]{1,8}:/.test(document.ltargetform.lurl.value)) { '
						. ' browse_links_setHref(document.ltargetform.lurl.value); } else { browse_links_setHref(\'http://\''
						. '+document.ltargetform.lurl.value); } browse_links_setAdditionalValue(\'data-htmlarea-external\', \'1\');'
						. 'return link_current();" />
				</td>
			</tr>';
		return $extUrl;
	}

	/**
	 * Creates a form for link attributes
	 *
	 * @param string $rows: html code for some initial rows of the table to be wrapped in form
	 * @return string The HTML code of the form
	 */
	public function addAttributesForm($rows = '') {
		// additional fields for links
		$additionalAttributeFields = '';
		if (
			isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->hookName]['addAttributeFields'])
			&& is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->hookName]['addAttributeFields'])
		) {
			$conf = array();
			$_params = array(
				'conf' => &$conf
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->hookName]['addAttributeFields'] as $objRef) {
				$processor =& GeneralUtility::getUserObj($objRef);
				$additionalAttributeFields .= $processor->getAttributefields($_params, $this);
			}
		}

		// Add page id, target, class selector box, title and parameters fields:
		$lpageId = $this->addPageIdSelector();


		$ltargetForm = '';
		if ($rows || $lpageId || $queryParameters || $lclass || $ltitle || $ltarget || $rel) {
			$ltargetForm = '
			<!--
				Selecting target for link:
			-->
			<form action="" name="ltargetform" id="ltargetform">
				<table id="typo3-linkTarget" class="htmlarea-window-table">' . $rows . $lpageId . $queryParameters . $lclass . $ltitle . $ltarget . $rel . $additionalAttributeFields;
			if ($this->act === $this->curUrlInfo['act'] && $this->act != 'mail' && $this->curUrlArray['href']) {
				$ltargetForm .= '
					<tr>
						<td>
						</td>
						<td colspan="3">
							<input class="btn btn-default" type="submit" value="' . $this->getLanguageService()->getLL('update', TRUE) . '" onclick="'
					. ($this->act === 'url' ? 'browse_links_setAdditionalValue(\'data-htmlarea-external\', \'1\'); ' : '')
					. 'return link_current();" />
						</td>
					</tr>';
			}
			$ltargetForm .= '
				</table>
			</form>';
		}
		return $ltargetForm;
	}


	/**
	 * Add page id selector
	 *
	 * @return string
	 */
	public function addPageIdSelector() {
		if ($this->act === 'page' && isset($this->buttonConfig['pageIdSelector.']['enabled'])
			&& $this->buttonConfig['pageIdSelector.']['enabled']
		) {
			return '
				<tr>
					<td>
						<label>
							' . $this->getLanguageService()->getLL('page_id', TRUE) . ':
						</label>
					</td>
					<td colspan="3">
						<input type="text" size="6" name="luid" /> <input class="btn btn-default" type="submit" value="'
							. $this->getLanguageService()->getLL('setLink', TRUE) . '" onclick="return link_typo3Page(document.ltargetform.luid.value);" />
					</td>
				</tr>';
		}
		return '';
	}

	/**
	 * Localize a label obtained from Page TSConfig
	 *
	 * @param string $string The label to be localized
	 * @param bool $JScharCode If needs to be converted to an array of char numbers
	 * @return string Localized string
	 */
	public function getPageConfigLabel($string, $JScharCode = TRUE) {
		if (substr($string, 0, 4) !== 'LLL:') {
			$label = $string;
		} else {
			$label = $this->getLanguageService()->sL(trim($string));
		}
		$label = str_replace('"', '\\"', str_replace('\\\'', '\'', $label));
		return $JScharCode ? GeneralUtility::quoteJSvalue($label) : $label;
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

}
