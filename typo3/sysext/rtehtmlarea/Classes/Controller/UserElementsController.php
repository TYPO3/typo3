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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * User defined content for htmlArea RTE
 */
class UserElementsController
{
    /**
     * @var string
     */
    public $content;

    /**
     * @var array
     */
    public $modData;

    /**
     * @var string
     */
    public $siteUrl;

    /**
     * document template object
     *
     * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    public $doc;

    /**
     * @var string
     */
    public $editorNo;

    /**
     * Initialize language files
     */
    public function __construct()
    {
        $GLOBALS['LANG']->includeLLFile('EXT:rtehtmlarea/Resources/Private/Language/locallang_userelementscontroller.xlf');
        $GLOBALS['LANG']->includeLLFile('EXT:rtehtmlarea/Resources/Private/Language/locallang_dialogs.xlf');

        $this->init();
    }

    /**
     * @return void
     */
    public function init()
    {
        $this->editorNo = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('editorNo');
        $this->siteUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        $this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);
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
					alert(' . GeneralUtility::quoteJSvalue($GLOBALS['LANG']->getLL('noTextSelection')) . ');
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
				theLocation = URL+RTEtsConfigParams+editorNo;
				window.location.href = theLocation;
			}
		';

        // unset the default jumpToUrl() function
        unset($this->doc->JScodeArray['jumpToUrl']);

        $this->doc->JScode = $this->doc->wrapScriptTags($JScode);
        $this->modData = $GLOBALS['BE_USER']->getModuleData('user.php', 'ses');
        if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('OC_key')) {
            $parts = explode('|', \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('OC_key'));
            $this->modData['openKeys'][$parts[1]] = $parts[0] == 'O' ? 1 : 0;
            $GLOBALS['BE_USER']->pushModuleData('user.php', $this->modData);
        }
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main_user() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response the prepared response object
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $content = $this->main_user($this->modData['openKeys']);
        $response->getBody()->write($content);
        return $response;
    }

    /**
     * Main function
     *
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use main_user() instead
     */
    public function main()
    {
        GeneralUtility::logDeprecatedFunction();
        $this->content = '';
        $this->content .= $this->main_user($this->modData['openKeys']);
    }

    /**
     * Print content
     *
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use mainAction() instead
     */
    public function printContent()
    {
        GeneralUtility::logDeprecatedFunction();
        echo $this->content;
    }

    /********************************
     *
     * Other functions
     *
     *********************************/
    /**
     * @param array $imgInfo
     * @param int $maxW
     * @param int $maxH
     * @return array
     */
    public function calcWH($imgInfo, $maxW = 380, $maxH = 500)
    {
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
     * @param array $openKeys
     * @return string
     */
    public function main_user($openKeys)
    {
        // Starting content:
        $content = $this->doc->startPage($GLOBALS['LANG']->getLL('Insert Custom Element', true));
        $RTEtsConfigParts = explode(':', \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('RTEtsConfigParams'));
        $RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($RTEtsConfigParts[5]));
        $thisConfig = \TYPO3\CMS\Backend\Utility\BackendUtility::RTEsetup($RTEsetup['properties'], $RTEtsConfigParts[0], $RTEtsConfigParts[2], $RTEtsConfigParts[4]);
        if (is_array($thisConfig['userElements.'])) {
            $categories = [];
            foreach ($thisConfig['userElements.'] as $k => $value) {
                $ki = (int)$k;
                $v = $thisConfig['userElements.'][$ki . '.'];
                if (substr($k, -1) == '.' && is_array($v)) {
                    $subcats = [];
                    $openK = $ki;
                    if ($openKeys[$openK]) {
                        $mArray = '';
                        if ($v['load'] === 'images_from_folder') {
                            $mArray = [];
                            if ($v['path'] && @is_dir((PATH_site . $v['path']))) {
                                $files = \TYPO3\CMS\Core\Utility\GeneralUtility::getFilesInDir(PATH_site . $v['path'], 'gif,jpg,jpeg,png', 0, '');
                                if (is_array($files)) {
                                    $c = 0;
                                    foreach ($files as $filename) {
                                        $iInfo = @getimagesize((PATH_site . $v['path'] . $filename));
                                        $iInfo = $this->calcWH($iInfo, 50, 100);
                                        $ks = (string)(100 + $c);
                                        $mArray[$ks] = $filename;
                                        $mArray[$ks . '.'] = [
                                            'content' => '<img src="' . $this->siteUrl . $v['path'] . $filename . '" />',
                                            '_icon' => '<img src="' . $this->siteUrl . $v['path'] . $filename . '" ' . $iInfo[3] . ' />',
                                            'description' => $GLOBALS['LANG']->getLL('filesize') . ': ' . str_replace('&nbsp;', ' ', \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize(@filesize((PATH_site . $v['path'] . $filename)))) . ', ' . $GLOBALS['LANG']->getLL('pixels', 1) . ': ' . $iInfo[0] . 'x' . $iInfo[1]
                                        ];
                                        $c++;
                                    }
                                }
                            }
                        }
                        if (is_array($mArray)) {
                            if ($v['merge']) {
                                \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($mArray, $v);
                                $v = $mArray;
                            } else {
                                $v = $mArray;
                            }
                        }
                        foreach ($v as $k2 => $dummyValue) {
                            $k2i = (int)$k2;
                            if (substr($k2, -1) == '.' && is_array($v[$k2i . '.'])) {
                                $title = trim($v[$k2i]);
                                if (!$title) {
                                    $title = '[' . $GLOBALS['LANG']->getLL('noTitle', true) . ']';
                                } else {
                                    $title = $GLOBALS['LANG']->sL($title, true);
                                }
                                $description = $GLOBALS['LANG']->sL($v[$k2i . '.']['description'], true) . '<br />';
                                if (!$v[$k2i . '.']['dontInsertSiteUrl']) {
                                    $v[$k2i . '.']['content'] = str_replace('###_URL###', $this->siteUrl, $v[$k2i . '.']['content']);
                                }
                                $logo = $v[$k2i . '.']['_icon'] ?: '';
                                $onClickEvent = '';
                                switch ((string)$v[$k2i . '.']['mode']) {
                                    case 'wrap':
                                        $wrap = explode('|', $v[$k2i . '.']['content']);
                                        $onClickEvent = 'wrapHTML(' . GeneralUtility::quoteJSvalue($wrap[0]) . ',' . GeneralUtility::quoteJSvalue($wrap[1]) . ',false);';
                                        break;
                                    case 'processor':
                                        $script = trim($v[$k2i . '.']['submitToScript']);
                                        if (substr($script, 0, 4) != 'http') {
                                            $script = $this->siteUrl . $script;
                                        }
                                        if ($script) {
                                            $onClickEvent = 'processSelection(' . GeneralUtility::quoteJSvalue($script) . ');';
                                        }
                                        break;
                                    case 'insert':

                                    default:
                                        $onClickEvent = 'insertHTML(' . GeneralUtility::quoteJSvalue($v[$k2i . '.']['content']) . ');';
                                }
                                $A = ['<a href="#" onClick="' . $onClickEvent . 'return false;">', '</a>'];
                                $subcats[$k2i] = '<tr>
									<td></td>
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
            $lines = [];
            foreach ($categories as $k => $v) {
                $title = trim($thisConfig['userElements.'][$k]);
                $openK = $k;
                if (!$title) {
                    $title = '[' . $GLOBALS['LANG']->getLL('noTitle', true) . ']';
                } else {
                    $title = $GLOBALS['LANG']->sL($title, true);
                }

                $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
                $url = (string)$uriBuilder->buildUriFromRoute('rtehtmlarea_wizard_user_elements', ['OC_key' => ($openKeys[$openK] ? 'C|' : 'O|') . $openK]);

                $lines[] = '<tr><td colspan="3" class="bgColor5"><a href="#" title="' . $GLOBALS['LANG']->getLL('expand', true) . '" onClick="jumpToUrl(' . GeneralUtility::quoteJSvalue($url) . ');return false;"><i class="fa fa-caret-square-o-' . ($openKeys[$openK] ? 'left' : 'right') . '" title="' . $GLOBALS['LANG']->getLL('expand', true) . '"></i><strong>' . $title . '</strong></a></td></tr>';
                $lines[] = $v;
            }
            $content .= '<table border="0" cellpadding="1" cellspacing="1">' . implode('', $lines) . '</table>';
        }
        $content .= $this->doc->endPage();
        return $content;
    }
}
