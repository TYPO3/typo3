<?php
namespace TYPO3\CMS\Rtehtmlarea\Extension;

/**
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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TYPO3Link plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class Typo3Link extends \TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi {

	protected $extensionKey = 'rtehtmlarea';

	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'TYPO3Link';

	// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = '';

	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/TYPO3Link/skin/htmlarea.css';

	// Path to the skin (css) file relative to the extension dir.
	protected $htmlAreaRTE;

	// Reference to the invoking object
	protected $thisConfig;

	// Reference to RTE PageTSConfig
	protected $toolbar;

	// Reference to RTE toolbar array
	protected $LOCAL_LANG;

	// Frontend language array
	protected $pluginButtons = 'link, unlink';

	protected $convertToolbarForHtmlAreaArray = array(
		'link' => 'CreateLink',
		'unlink' => 'UnLink'
	);

	public function main($parentObject) {
		$enabled = parent::main($parentObject);
		// Check if this should be enabled based on Page TSConfig
		return $enabled && !$this->thisConfig['buttons.']['link.']['TYPO3Browser.']['disabled'];
	}

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param 	integer		Relative id of the RTE editing area in the form
	 * @return 	string		JS configuration for registered plugins, in this case, JS configuration of block elements
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		$registerRTEinJavascriptString = '';
		$button = 'link';
		if (in_array($button, $this->toolbar)) {
			if (!is_array($this->thisConfig['buttons.']) || !is_array($this->thisConfig['buttons.'][($button . '.')])) {
				$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . '].buttons.' . $button . ' = new Object();';
			}
			$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . '].buttons.' . $button . '.pathLinkModule = ' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('rtehtmlarea_wizard_element_browser'));
			if ($this->htmlAreaRTE->is_FE()) {
				$RTEProperties = $this->htmlAreaRTE->RTEsetup;
			} else {
				$RTEProperties = $this->htmlAreaRTE->RTEsetup['properties'];
			}
			if (is_array($RTEProperties['classesAnchor.'])) {
				$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . '].buttons.' . $button . '.classesAnchorUrl = "' . $this->htmlAreaRTE->writeTemporaryFile('', ('classesAnchor_' . $this->htmlAreaRTE->contentLanguageUid), 'js', $this->buildJSClassesAnchorArray(), TRUE) . '";';
			}
			$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . '].buttons.' . $button . '.additionalAttributes = "data-htmlarea-external' . ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extensionKey]['plugins'][$this->pluginName]['additionalAttributes'] ? ',' . $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extensionKey]['plugins'][$this->pluginName]['additionalAttributes'] : '') . '";';
		}
		return $registerRTEinJavascriptString;
	}

	/**
	 * Return a JS array for special anchor classes
	 *
	 * @return 	string		classesAnchor array definition
	 */
	public function buildJSClassesAnchorArray() {
		global $LANG;
		$JSClassesAnchorArray .= 'HTMLArea.classesAnchorSetup = [ ' . LF;
		$classesAnchorIndex = 0;
		foreach ($this->htmlAreaRTE->RTEsetup['properties']['classesAnchor.'] as $label => $conf) {
			if (is_array($conf) && $conf['class']) {
				$JSClassesAnchorArray .= ($classesAnchorIndex++ ? ',' : '') . ' { ' . LF;
				$index = 0;
				$JSClassesAnchorArray .= ($index++ ? ',' : '') . 'name : "' . str_replace('"', '', str_replace('\'', '', $conf['class'])) . '"' . LF;
				if ($conf['type']) {
					$JSClassesAnchorArray .= ($index++ ? ',' : '') . 'type : "' . str_replace('"', '', str_replace('\'', '', $conf['type'])) . '"' . LF;
				}
				if (trim(str_replace('\'', '', str_replace('"', '', $conf['image'])))) {
					$JSClassesAnchorArray .= ($index++ ? ',' : '') . 'image : "' . $this->htmlAreaRTE->siteURL . \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath((TYPO3_mainDir . $this->htmlAreaRTE->getFullFileName(trim(str_replace('\'', '', str_replace('"', '', $conf['image'])))))) . '"' . LF;
				}
				$JSClassesAnchorArray .= ($index++ ? ',' : '') . 'addIconAfterLink : ' . ($conf['addIconAfterLink'] ? 'true' : 'false') . LF;
				if (trim($conf['altText'])) {
					$string = $this->htmlAreaRTE->getLLContent(trim($conf['altText']));
					$JSClassesAnchorArray .= ($index++ ? ',' : '') . 'altText : ' . str_replace('"', '\\"', str_replace('\\\'', '\'', $string)) . LF;
				}
				if (trim($conf['titleText'])) {
					$string = $this->htmlAreaRTE->getLLContent(trim($conf['titleText']));
					$JSClassesAnchorArray .= ($index++ ? ',' : '') . 'titleText : ' . str_replace('"', '\\"', str_replace('\\\'', '\'', $string)) . LF;
				}
				if (trim($conf['target'])) {
					$JSClassesAnchorArray .= ($index++ ? ',' : '') . 'target : "' . trim($conf['target']) . '"' . LF;
				}
				$JSClassesAnchorArray .= '}' . LF;
			}
		}
		$JSClassesAnchorArray .= '];' . LF;
		return $JSClassesAnchorArray;
	}

	/**
	 * Return an updated array of toolbar enabled buttons
	 *
	 * @param 	array		$show: array of toolbar elements that will be enabled, unless modified here
	 * @return 	array		toolbar button array, possibly updated
	 */
	public function applyToolbarConstraints($show) {
		// We will not allow unlink if link is not enabled
		if (!in_array('link', $show)) {
			return array_diff($show, \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->pluginButtons));
		} else {
			return $show;
		}
	}

}
