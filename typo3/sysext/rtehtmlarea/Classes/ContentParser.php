<?php
namespace TYPO3\CMS\Rtehtmlarea;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2013 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Content parsing for htmlArea RTE
 *
 * @author 	Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class ContentParser {

	/**
	 * @todo Define visibility
	 */
	public $content;

	/**
	 * @todo Define visibility
	 */
	public $modData;

	/**
	 * document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @todo Define visibility
	 */
	public $doc;

	/**
	 * @todo Define visibility
	 */
	public $extKey = 'rtehtmlarea';

	/**
	 * @todo Define visibility
	 */
	public $prefixId = 'TYPO3HtmlParser';

	/**
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function init() {
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->JScode = '';
		$this->modData = $GLOBALS['BE_USER']->getModuleData($GLOBALS['MCONF']['name'], 'ses');
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('OC_key')) {
			$parts = explode('|', \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('OC_key'));
			$this->modData['openKeys'][$parts[1]] = $parts[0] == 'O' ? 1 : 0;
			$GLOBALS['BE_USER']->pushModuleData($GLOBALS['MCONF']['name'], $this->modData);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function main() {
		$this->content .= $this->main_parse_html($this->modData['openKeys']);
		header('Content-Type: text/plain; charset=utf-8');
	}

	/**
	 * [Describe function...]
	 *
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Rich Text Editor (RTE) html parser
	 *
	 * @param 	[type]		$openKeys: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function main_parse_html($openKeys) {
		global $TYPO3_CONF_VARS;
		$editorNo = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('editorNo');
		$html = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('content');
		$RTEtsConfigParts = explode(':', \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('RTEtsConfigParams'));
		$RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($RTEtsConfigParts[5]));
		$thisConfig = \TYPO3\CMS\Backend\Utility\BackendUtility::RTEsetup($RTEsetup['properties'], $RTEtsConfigParts[0], $RTEtsConfigParts[2], $RTEtsConfigParts[4]);
		$HTMLParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Html\\HtmlParser');
		if (is_array($thisConfig['enableWordClean.'])) {
			$HTMLparserConfig = $thisConfig['enableWordClean.']['HTMLparser.'];
			if (is_array($HTMLparserConfig)) {
				$this->keepSpanTagsWithId($HTMLparserConfig);
				$HTMLparserConfig = $HTMLParser->HTMLparserConfig($HTMLparserConfig);
			}
		}
		if (is_array($HTMLparserConfig)) {
			$html = $HTMLParser->HTMLcleaner($html, $HTMLparserConfig[0], $HTMLparserConfig[1], $HTMLparserConfig[2], $HTMLparserConfig[3]);
		}
		if (is_array($TYPO3_CONF_VARS['EXTCONF'][$this->extKey][$this->prefixId]['cleanPastedContent'])) {
			foreach ($TYPO3_CONF_VARS['EXTCONF'][$this->extKey][$this->prefixId]['cleanPastedContent'] as $classRef) {
				$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'cleanPastedContent_afterCleanWord')) {
					$html = $hookObj->cleanPastedContent_afterCleanWord($html, $thisConfig);
				}
			}
		}
		return $html;
	}

	/**
	 * Modify incoming HTMLparser config in an attempt to keep span tags with id
	 * Such tags are used by the RTE in order to restore the cursor position when the cleaning operation is completed.
	 *
	 * @param 	array		$HTMLparserConfig: incoming HTMLParser configuration (wil be modified)
	 * @return 	void
	 */
	protected function keepSpanTagsWithId(&$HTMLparserConfig) {
		// Allow span tag
		if (isset($HTMLparserConfig['allowTags'])) {
			if (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList($HTMLparserConfig['allowTags'], 'span')) {
				$HTMLparserConfig['allowTags'] .= ',span';
			}
		} else {
			$HTMLparserConfig['allowTags'] = 'span';
		}
		// Allow attributes on span tags
		if (isset($HTMLparserConfig['noAttrib']) && \TYPO3\CMS\Core\Utility\GeneralUtility::inList($HTMLparserConfig['noAttrib'], 'span')) {
			$HTMLparserConfig['noAttrib'] = \TYPO3\CMS\Core\Utility\GeneralUtility::rmFromList('span', $HTMLparserConfig['noAttrib']);
		}
		// Do not remove span tags
		if (isset($HTMLparserConfig['removeTags']) && \TYPO3\CMS\Core\Utility\GeneralUtility::inList($HTMLparserConfig['removeTags'], 'span')) {
			$HTMLparserConfig['removeTags'] = \TYPO3\CMS\Core\Utility\GeneralUtility::rmFromList('span', $HTMLparserConfig['removeTags']);
		}
		// Review the tags array
		if (is_array($HTMLparserConfig['tags.'])) {
			// Allow span tag
			if (isset($HTMLparserConfig['tags.']['span']) && !$HTMLparserConfig['tags.']['span']) {
				$HTMLparserConfig['tags.']['span'] = 1;
			}
			if (is_array($HTMLparserConfig['tags.']['span.'])) {
				if (isset($HTMLparserConfig['tags.']['span.']['allowedAttribs'])) {
					if (!$HTMLparserConfig['tags.']['span.']['allowedAttribs']) {
						$HTMLparserConfig['tags.']['span.']['allowedAttribs'] = 'id';
					} elseif (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList($HTMLparserConfig['tags.']['span.']['allowedAttribs'], 'id')) {
						$HTMLparserConfig['tags.']['span.']['allowedAttribs'] .= ',id';
					}
				}
				if (isset($HTMLparserConfig['tags.']['span.']['fixAttrib.']['id.']['unset'])) {
					unset($HTMLparserConfig['tags.']['span.']['fixAttrib.']['id.']['unset']);
				}
			}
		}
	}

}


?>