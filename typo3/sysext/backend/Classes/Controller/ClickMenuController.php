<?php
namespace TYPO3\CMS\Backend\Controller;

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
use TYPO3\CMS\Backend\ClickMenu\ClickMenu;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for the Context Sensitive Menu in TYPO3 (rendered in top frame, normally writing content dynamically to list frames).
 * @see \TYPO3\CMS\Backend\Template\DocumentTemplate::getContextMenuCode()
 */
class ClickMenuController
{
    /**
     * Defines the name of the document object for which to reload the URL.
     *
     * @var int
     */
    public $reloadListFrame;

    /**
     * Content accumulation
     *
     * @var string
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public $content = '';

    /**
     * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    public $doc;

    /**
     * Internal array of classes for extending the clickmenu
     *
     * @var array
     */
    public $extClassArray = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->getLanguageService()->includeLLFile('EXT:lang/locallang_misc.xlf');
        $GLOBALS['SOBE'] = $this;

        // Setting GPvars:
        $this->reloadListFrame = GeneralUtility::_GP('reloadListFrame');
        // Setting pseudo module name
        $this->MCONF['name'] = 'xMOD_alt_clickmenu.php';

        // Setting internal array of classes for extending the clickmenu:
        $this->extClassArray = $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'];

        // Initialize template object
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
    }

    /**
     * Constructor function for script class.
     *
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, all done in the constructor now
     */
    protected function init()
    {
        GeneralUtility::logDeprecatedFunction();
    }

    /**
     * Main function - generating the click menu in whatever form it has.
     *
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, as an AJAX Route is now the main entry point
     * @return void
     */
    public function main()
    {
        GeneralUtility::logDeprecatedFunction();
        // Initialize Clipboard object:
        $clipObj = GeneralUtility::makeInstance(Clipboard::class);
        $clipObj->initializeClipboard();
        // This locks the clipboard to the Normal for this request.
        $clipObj->lockToNormal();
        // Update clipboard if some actions are sent.
        $CB = GeneralUtility::_GET('CB');
        $clipObj->setCmd($CB);
        $clipObj->cleanCurrent();
        // Saves
        $clipObj->endClipboard();
        // Create clickmenu object
        $clickMenu = GeneralUtility::makeInstance(ClickMenu::class);
        // Set internal vars in clickmenu object:
        $clickMenu->clipObj = $clipObj;
        $clickMenu->extClassArray = $this->extClassArray;
        // Set content of the clickmenu with the incoming var, "item"
        $this->content .= $clickMenu->init();
    }

    /**
     * End page and output content.
     *
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, as an AJAX Route is now the main entry point
     * @return void
     */
    public function printContent()
    {
        GeneralUtility::logDeprecatedFunction();
        header('Content-Type: text/xml');
        echo '<?xml version="1.0"?>' . LF . '<t3ajax>' . $this->content . '</t3ajax>';
    }

    /**
     * this is an intermediate clickmenu handler
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getContextMenuAction(ServerRequestInterface $request, ResponseInterface $response)
    {

        // XML has to be parsed, no parse errors allowed
        @ini_set('display_errors', 0);

        $clipObj = GeneralUtility::makeInstance(Clipboard::class);
        $clipObj->initializeClipboard();
        // This locks the clipboard to the Normal for this request.
        $clipObj->lockToNormal();
        // Update clipboard if some actions are sent.
        $CB = GeneralUtility::_GET('CB');
        $clipObj->setCmd($CB);
        $clipObj->cleanCurrent();
        // Saves
        $clipObj->endClipboard();
        // Create clickmenu object
        $clickMenu = GeneralUtility::makeInstance(ClickMenu::class);
        // Set internal vars in clickmenu object:
        $clickMenu->clipObj = $clipObj;
        $clickMenu->extClassArray = $this->extClassArray;

        // Set content of the clickmenu with the incoming var, "item"
        $ajaxContent = $clickMenu->init();

        // send the data
        $ajaxContent = '<?xml version="1.0"?><t3ajax>' . $ajaxContent . '</t3ajax>';
        $response->getBody()->write($ajaxContent);
        $response = $response->withHeader('Content-Type', 'text/xml; charset=utf-8');
        return $response;
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
