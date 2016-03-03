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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for the Context Sensitive Menu in TYPO3 (rendered in top frame, normally writing content dynamically to list frames).
 * @see \TYPO3\CMS\Backend\Template\DocumentTemplate::getContextMenuCode()
 */
class ClickMenuController
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->getLanguageService()->includeLLFile('EXT:lang/locallang_misc.xlf');
        $GLOBALS['SOBE'] = $this;
        // Setting pseudo module name
        $this->MCONF['name'] = 'xMOD_alt_clickmenu.php';
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
        /** @var Clipboard $clipObj */
        $clipObj = GeneralUtility::makeInstance(Clipboard::class);
        $clipObj->initializeClipboard();
        // This locks the clipboard to the Normal for this request.
        $clipObj->lockToNormal();
        // Update clipboard if some actions are sent.
        $clipObj->setCmd($request->getQueryParams()['CB']);
        $clipObj->cleanCurrent();
        // Saves
        $clipObj->endClipboard();
        // Create clickmenu object
        $clickMenu = GeneralUtility::makeInstance(ClickMenu::class);
        // Set internal vars in clickmenu object:
        $clickMenu->clipObj = $clipObj;
        // Setting internal array of classes for extending the clickmenu:
        $clickMenu->extClassArray = $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'];

        $content = $clickMenu->init();
        if (is_array($content)) {
            $response->getBody()->write(json_encode($content));
        }
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
