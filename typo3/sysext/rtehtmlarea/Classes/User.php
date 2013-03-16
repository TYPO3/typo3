<?php
namespace TYPO3\CMS\Rtehtmlarea;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasper@typo3.com)
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
 * User defined content for htmlArea RTE
 *
 * @author 	Kasper Skårhøj <kasper@typo3.com>
 * @author 	Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class User {

	/**
	 * @todo Define visibility
	 */
	public $content;

	/**
	 * @todo Define visibility
	 */
	public $modData;

	/**
	 * @todo Define visibility
	 */
	public $siteUrl;

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
	public $editorNo;

	/**
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function init() {
		$this->editorNo = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('editorNo');
		$this->siteUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->bodyTagAdditions = 'onload="Init();"';
		$this->doc->form = '
	<form action="" id="process" name="process" method="post">
		<input type="hidden" name="processContent" value="" />
		<input type="hidden" name="returnUrl" value="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '" />
		';
		$JScode = '
			var plugin = window.parent.RTEarea["' . $this->editorNo . '"].editor.getPlugin("UserElements");
			var HTMLArea = window.parent.HTMLArea;
			var editor = plugin.editor;

			function Init() {
			};
			function insertHTML(content,noHide) {
				plugin.restoreSelection();
				editor.getSelection().insertHtml(content);
				if(!noHide) plugin.close();
			};
			function wrapHTML(wrap1,wrap2,noHide) {
				plugin.restoreSelection();
				if(!editor.getSelection().isEmpty()) {
					editor.getSelection().surroundHtml(wrap1,wrap2);
				} else {
					alert(' . $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL('noTextSelection')) . ');
				}
				if(!noHide) plugin.close();
			};
			function processSelection(script) {
				plugin.restoreSelection();
				document.process.action = script;
				document.process.processContent.value = editor.getSelection().getHtml();
				document.process.submit();
			};
			function jumpToUrl(URL) {
				var RTEtsConfigParams = "&RTEtsConfigParams=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('RTEtsConfigParams')) . '";
				var editorNo = "&editorNo=' . rawurlencode($this->editorNo) . '";
				theLocation = "' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_NAME') . '"+URL+RTEtsConfigParams+editorNo;
				window.location.href = theLocation;
			}
		';
		$this->doc->JScode = $this->doc->wrapScriptTags($JScode);
		$this->modData = $GLOBALS['BE_USER']->getModuleData('user.php', 'ses');
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('OC_key')) {
			$parts = explode('|', \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('OC_key'));
			$this->modData['openKeys'][$parts[1]] = $parts[0] == 'O' ? 1 : 0;
			$GLOBALS['BE_USER']->pushModuleData('user.php', $this->modData);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function main() {
		$this->content = '';
		$this->content .= $this->main_user($this->modData['openKeys']);
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

	/********************************
	 *
	 * Other functions
	 *
	 *********************************/
	/**
	 * @param 	[type]		$imgInfo: ...
	 * @param 	[type]		$maxW: ...
	 * @param 	[type]		$maxH: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function calcWH($imgInfo, $maxW = 380, $maxH = 500) {
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
		$imgInfo[3] = 'width="' . $IW . '" height="' . $IH . '"';
		return $imgInfo;
	}

	/**
	 * Rich Text Editor (RTE) user element selector
	 *
	 * @param 	[type]		$openKeys: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function main_user($openKeys) {
		// Starting content:
		$content = $this->doc->startPage($GLOBALS['LANG']->getLL('Insert Custom Element', 1));
		$RTEtsConfigParts = explode(':', \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('RTEtsConfigParams'));
		$RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($RTEtsConfigParts[5]));
		$thisConfig = \TYPO3\CMS\Backend\Utility\BackendUtility::RTEsetup($RTEsetup['properties'], $RTEtsConfigParts[0], $RTEtsConfigParts[2], $RTEtsConfigParts[4]);
		if (is_array($thisConfig['userElements.'])) {
			$categories = array();
			foreach ($thisConfig['userElements.'] as $k => $value) {
				$ki = intval($k);
				$v = $thisConfig['userElements.'][$ki . '.'];
				if (substr($k, -1) == '.' && is_array($v)) {
					$subcats = array();
					$openK = $ki;
					if ($openKeys[$openK]) {
						$mArray = '';
						switch ((string) $v['load']) {
						case 'images_from_folder':
							$mArray = array();
							if ($v['path'] && @is_dir((PATH_site . $v['path']))) {
								$files = \TYPO3\CMS\Core\Utility\GeneralUtility::getFilesInDir(PATH_site . $v['path'], 'gif,jpg,jpeg,png', 0, '');
								if (is_array($files)) {
									$c = 0;
									foreach ($files as $filename) {
										$iInfo = @getimagesize((PATH_site . $v['path'] . $filename));
										$iInfo = $this->calcWH($iInfo, 50, 100);
										$ks = (string) (100 + $c);
										$mArray[$ks] = $filename;
										$mArray[$ks . '.'] = array(
											'content' => '<img src="' . $this->siteUrl . $v['path'] . $filename . '" />',
											'_icon' => '<img src="' . $this->siteUrl . $v['path'] . $filename . '" ' . $iInfo[3] . ' />',
											'description' => $GLOBALS['LANG']->getLL('filesize') . ': ' . str_replace('&nbsp;', ' ', \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize(@filesize((PATH_site . $v['path'] . $filename)))) . ', ' . $GLOBALS['LANG']->getLL('pixels', 1) . ': ' . $iInfo[0] . 'x' . $iInfo[1]
										);
										$c++;
									}
								}
							}
							break;
						}
						if (is_array($mArray)) {
							if ($v['merge']) {
								$v = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($mArray, $v);
							} else {
								$v = $mArray;
							}
						}
						foreach ($v as $k2 => $dummyValue) {
							$k2i = intval($k2);
							if (substr($k2, -1) == '.' && is_array($v[$k2i . '.'])) {
								$title = trim($v[$k2i]);
								if (!$title) {
									$title = '[' . $GLOBALS['LANG']->getLL('noTitle', 1) . ']';
								} else {
									$title = $GLOBALS['LANG']->sL($title, 1);
								}
								$description = $GLOBALS['LANG']->sL($v[($k2i . '.')]['description'], 1) . '<br />';
								if (!$v[($k2i . '.')]['dontInsertSiteUrl']) {
									$v[$k2i . '.']['content'] = str_replace('###_URL###', $this->siteUrl, $v[$k2i . '.']['content']);
								}
								$logo = $v[$k2i . '.']['_icon'] ? $v[$k2i . '.']['_icon'] : '';
								$onClickEvent = '';
								switch ((string) $v[($k2i . '.')]['mode']) {
								case 'wrap':
									$wrap = explode('|', $v[$k2i . '.']['content']);
									$onClickEvent = 'wrapHTML(' . $GLOBALS['LANG']->JScharCode($wrap[0]) . ',' . $GLOBALS['LANG']->JScharCode($wrap[1]) . ',false);';
									break;
								case 'processor':
									$script = trim($v[$k2i . '.']['submitToScript']);
									if (substr($script, 0, 4) != 'http') {
										$script = $this->siteUrl . $script;
									}
									if ($script) {
										$onClickEvent = 'processSelection(' . $GLOBALS['LANG']->JScharCode($script) . ');';
									}
									break;
								case 'insert':

								default:
									$onClickEvent = 'insertHTML(' . $GLOBALS['LANG']->JScharCode($v[($k2i . '.')]['content']) . ');';
									break;
								}
								$A = array('<a href="#" onClick="' . $onClickEvent . 'return false;">', '</a>');
								$subcats[$k2i] = '<tr>
									<td><img src="clear.gif" width="18" height="1" /></td>
									<td class="bgColor4" valign="top">' . $A[0] . $logo . $A[1] . '</td>
									<td class="bgColor4" valign="top">' . $A[0] . '<strong>' . $title . '</strong><br />' . $description . $A[1] . '</td>
								</tr>';
							}
						}
						ksort($subcats);
					}
					$categories[$ki] = implode('', $subcats);
				}
			}
			ksort($categories);
			// Render menu of the items:
			$lines = array();
			foreach ($categories as $k => $v) {
				$title = trim($thisConfig['userElements.'][$k]);
				$openK = $k;
				if (!$title) {
					$title = '[' . $GLOBALS['LANG']->getLL('noTitle', 1) . ']';
				} else {
					$title = $GLOBALS['LANG']->sL($title, 1);
				}
				$lines[] = '<tr><td colspan="3" class="bgColor5"><a href="#" title="' . $GLOBALS['LANG']->getLL('expand', 1) . '" onClick="jumpToUrl(\'?OC_key=' . ($openKeys[$openK] ? 'C|' : 'O|') . $openK . '\');return false;"><img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], ('gfx/ol/' . ($openKeys[$openK] ? 'minus' : 'plus') . 'bullet.gif'), 'width="18" height="16"') . ' title="' . $GLOBALS['LANG']->getLL('expand', 1) . '" /><strong>' . $title . '</strong></a></td></tr>';
				$lines[] = $v;
			}
			$content .= '<table border="0" cellpadding="1" cellspacing="1">' . implode('', $lines) . '</table>';
		}
		$content .= $this->doc->endPage();
		return $content;
	}

}


?>