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
 * Content parsing for htmlArea RTE
 */
class ParseHtmlController
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
     * document template object
     *
     * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    public $doc;

    /**
     * @var string
     */
    public $extKey = 'rtehtmlarea';

    /**
     * @var string
     */
    public $prefixId = 'TYPO3HtmlParser';

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'rtehtmlarea_wizard_parse_html';

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * @return void
     */
    public function init()
    {
        $this->doc = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);
        $this->doc->JScode = '';
        $this->modData = $GLOBALS['BE_USER']->getModuleData($this->moduleName, 'ses');
        if (GeneralUtility::_GP('OC_key')) {
            $parts = explode('|', GeneralUtility::_GP('OC_key'));
            $this->modData['openKeys'][$parts[1]] = $parts[0] == 'O' ? 1 : 0;
            $GLOBALS['BE_USER']->pushModuleData($this->moduleName, $this->modData);
        }
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main_parse_html() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->content .= $this->main_parse_html($this->modData['openKeys']);

        $response->getBody()->write($this->content);
        $response = $response->withHeader('Content-Type', 'text/plain; charset=utf-8');
        return $response;
    }

    /**
     * Main function
     *
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use main_parse_html() instead
     */
    public function main()
    {
        GeneralUtility::logDeprecatedFunction();
        $this->content .= $this->main_parse_html($this->modData['openKeys']);
        header('Content-Type: text/plain; charset=utf-8');
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

    /**
     * Rich Text Editor (RTE) html parser
     *
     * @param array $openKeys Unused
     * @return string
     */
    public function main_parse_html($openKeys)
    {
        $editorNo = GeneralUtility::_GP('editorNo');
        $html = GeneralUtility::_GP('content');
        $RTEtsConfigParts = explode(':', GeneralUtility::_GP('RTEtsConfigParams'));
        $RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($RTEtsConfigParts[5]));
        $thisConfig = \TYPO3\CMS\Backend\Utility\BackendUtility::RTEsetup($RTEsetup['properties'], $RTEtsConfigParts[0], $RTEtsConfigParts[2], $RTEtsConfigParts[4]);
        $HTMLParser = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Html\HtmlParser::class);
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
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey][$this->prefixId]['cleanPastedContent'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey][$this->prefixId]['cleanPastedContent'] as $classRef) {
                $hookObj = GeneralUtility::getUserObj($classRef);
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
     * @param array $HTMLparserConfig: incoming HTMLParser configuration (wil be modified)
     * @return void
     */
    protected function keepSpanTagsWithId(&$HTMLparserConfig)
    {
        // Allow span tag
        if (isset($HTMLparserConfig['allowTags'])) {
            if (!GeneralUtility::inList($HTMLparserConfig['allowTags'], 'span')) {
                $HTMLparserConfig['allowTags'] .= ',span';
            }
        } else {
            $HTMLparserConfig['allowTags'] = 'span';
        }
        // Allow attributes on span tags
        if (isset($HTMLparserConfig['noAttrib']) && GeneralUtility::inList($HTMLparserConfig['noAttrib'], 'span')) {
            $HTMLparserConfig['noAttrib'] = GeneralUtility::rmFromList('span', $HTMLparserConfig['noAttrib']);
        }
        // Do not remove span tags
        if (isset($HTMLparserConfig['removeTags']) && GeneralUtility::inList($HTMLparserConfig['removeTags'], 'span')) {
            $HTMLparserConfig['removeTags'] = GeneralUtility::rmFromList('span', $HTMLparserConfig['removeTags']);
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
                    } elseif (!GeneralUtility::inList($HTMLparserConfig['tags.']['span.']['allowedAttribs'], 'id')) {
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
