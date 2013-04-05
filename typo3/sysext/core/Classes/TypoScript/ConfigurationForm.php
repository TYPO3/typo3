<?php
namespace TYPO3\CMS\Core\TypoScript;

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
 * Provides a simplified layer for making Constant Editor style configuration forms
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ConfigurationForm extends \TYPO3\CMS\Core\TypoScript\ExtendedTemplateService {

	// Internal
	/**
	 * @todo Define visibility
	 */
	public $categories = array();

	/**
	 * @todo Define visibility
	 */
	public $ext_dontCheckIssetValues = 1;

	/**
	 * @todo Define visibility
	 */
	public $ext_CEformName = 'tsStyleConfigForm';

	/**
	 * @todo Define visibility
	 */
	public $ext_printAll = 1;

	/**
	 * @todo Define visibility
	 */
	public $ext_incomingValues = array();

	/**
	 * @param string $configTemplate
	 * @param string $pathRel PathRel is the path relative to the typo3/ directory
	 * @param string $pathAbs PathAbs is the absolute path from root
	 * @param string $backPath BackPath is the backReference from current position to typo3/ dir
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_initTSstyleConfig($configTemplate, $pathRel, $pathAbs, $backPath) {
		// Do not log time-performance information
		$this->tt_track = 0;
		$this->constants = array($configTemplate, '');
		// The editable constants are returned in an array.
		$theConstants = $this->generateConfig_constants();
		$this->ext_localGfxPrefix = $pathAbs;
		$this->ext_localWebGfxPrefix = $backPath . $pathRel;
		$this->ext_backPath = $backPath;
		return $theConstants;
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$theConstants: ...
	 * @param 	[type]		$valueArray: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_setValueArray($theConstants, $valueArray) {
		$temp = $this->flatSetup;
		$this->flatSetup = array();
		$this->flattenSetup($valueArray, '', '');
		$this->objReg = ($this->ext_realValues = $this->flatSetup);
		$this->flatSetup = $temp;
		foreach ($theConstants as $k => $p) {
			if (isset($this->objReg[$k])) {
				$theConstants[$k]['value'] = $this->ext_realValues[$k];
			}
		}
		// Reset the default pool of categories.
		$this->categories = array();
		// The returned constants are sorted in categories, that goes into the $this->categories array
		$this->ext_categorizeEditableConstants($theConstants);
		return $theConstants;
	}

	/**
	 * [Describe function...]
	 *
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_getCategoriesForModMenu() {
		return $this->ext_getCategoryLabelArray();
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$cat: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_makeHelpInformationForCategory($cat) {
		return $this->ext_getTSCE_config($cat);
	}

	/**
	 * Get the form for extension configuration
	 *
	 * @param string $cat
	 * @param array $theConstants
	 * @param string $script
	 * @param string $addFields
	 * @param string $extKey
	 * @param boolean Adds opening <form> tag to the ouput, if TRUE
	 * @return string The form
	 * @todo Define visibility
	 */
	public function ext_getForm($cat, $theConstants, $script = '', $addFields = '', $extKey = '', $addFormTag = TRUE) {
		$this->ext_makeHelpInformationForCategory($cat);
		$printFields = trim($this->ext_printFields($theConstants, $cat));
		$content = '';
		$content .= \TYPO3\CMS\Core\Utility\GeneralUtility::wrapJS('
			function uFormUrl(aname) {
				document.' . $this->ext_CEformName . '.action = "' . \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript() . '#"+aname;
			}
		');
		if ($addFormTag) {
			$content .= '<form action="' . htmlspecialchars(($script ? $script : \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript())) . '" name="' . $this->ext_CEformName . '" method="post" enctype="' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] . '">';
		}
		$content .= $addFields;
		$content .= $printFields;
		$content .= '<input type="submit" name="submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_tsfe.xlf:update', TRUE) . '" id="configuration-submit-' . htmlspecialchars($extKey) . '" />';
		$example = $this->ext_displayExample();
		$content .= $example ? '<hr/>' . $example : '';
		return $content;
	}

	/**
	 * [Describe function...]
	 *
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_displayExample() {
		if ($this->helpConfig['imagetag'] || $this->helpConfig['description'] || $this->helpConfig['header']) {
			$out = '<div align="center">' . $this->helpConfig['imagetag'] . '</div><BR>' . ($this->helpConfig['description'] ? implode(explode('//', $this->helpConfig['description']), '<BR>') . '<BR>' : '') . ($this->helpConfig['bulletlist'] ? '<ul><li>' . implode(explode('//', $this->helpConfig['bulletlist']), '<li>') . '</ul>' : '<BR>');
		}
		return $out;
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$arr: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_mergeIncomingWithExisting($arr) {
		$parseObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
		$parseObj->parse(implode(LF, $this->ext_incomingValues));
		$arr2 = $parseObj->setup;
		return \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($arr, $arr2);
	}

	// Extends:
	/**
	 * @todo Define visibility
	 */
	public function ext_getKeyImage($key) {
		return '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->ext_backPath, ('gfx/rednumbers/' . $key . '.gif'), '') . ' hspace="2" align="top" alt="" />';
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$imgConf: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_getTSCE_config_image($imgConf) {
		$iFile = $this->ext_localGfxPrefix . $imgConf;
		$tFile = $this->ext_localWebGfxPrefix . $imgConf;
		$imageInfo = @getImagesize($iFile);
		return '<img src="' . $tFile . '" ' . $imageInfo[3] . '>';
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$params: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_fNandV($params) {
		$fN = 'data[' . $params['name'] . ']';
		$fV = ($params['value'] = isset($this->ext_realValues[$params['name']]) ? $this->ext_realValues[$params['name']] : $params['default_value']);
		$reg = array();
		// Values entered from the constantsedit cannot be constants!
		if (preg_match('/^\\{[\\$][a-zA-Z0-9\\.]*\\}$/', trim($fV), $reg)) {
			$fV = '';
		}
		$fV = htmlspecialchars($fV);
		return array($fN, $fV, $params);
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$key: ...
	 * @param 	[type]		$var: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_putValueInConf($key, $var) {
		$this->ext_incomingValues[$key] = $key . '=' . $var;
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$key: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_removeValueInConf($key) {

	}

}


?>