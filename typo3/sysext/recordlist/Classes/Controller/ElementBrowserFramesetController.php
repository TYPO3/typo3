<?php
namespace TYPO3\CMS\Recordlist\Controller;

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
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Script Class, putting the frameset together.
 */
class ElementBrowserFramesetController
{
    /**
     * Internal, dynamic
     *
     * @var string
     */
    public $content;

    /**
     * @var PageRenderer
     */
    protected $pageRenderer = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $GLOBALS['SOBE'] = $this;
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response the prepared response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->main();

        $response->getBody()->write($this->content);
        return $response;
    }

    /**
     * Main function.
     * Creates the header code in XHTML, the JavaScript, then the frameset for the two frames.
     *
     * @return void
     */
    public function main()
    {
        // Setting GPvars:
        $mode = GeneralUtility::_GP('mode');
        $bparams = GeneralUtility::_GP('bparams');
        $moduleUrl = BackendUtility::getModuleUrl('wizard_element_browser') . '&mode=';
        $documentTemplate = $this->getDocumentTemplate();
        $documentTemplate->JScode = $documentTemplate->wrapScriptTags('
				function closing() {	//
					close();
				}
				function setParams(mode,params) {	//
					parent.content.location.href = ' . GeneralUtility::quoteJSvalue($moduleUrl) . '+mode+"&bparams="+params;
				}
				if (!window.opener) {
					alert("ERROR: Sorry, no link to main window... Closing");
					close();
				}
		');

        // build the header part
        $documentTemplate->startPage($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:TYPO3_Element_Browser'));

        // URL for the inner main frame:
        $url = $moduleUrl . rawurlencode($mode) . '&bparams=' . rawurlencode($bparams);

        // Create the frameset for the window
        // Formerly there were a ' onunload="closing();"' in the <frameset> tag - but it failed on Safari browser on Mac unless the handler was "onUnload"
        $this->content = $this->getPageRenderer()->render(PageRenderer::PART_HEADER) .
            '<frameset rows="*,1" framespacing="0" frameborder="0" border="0">
				<frame name="content" src="' . htmlspecialchars($url) . '" marginwidth="0" marginheight="0" frameborder="0" scrolling="auto" noresize="noresize" />
				<frame name="menu" src="' . htmlspecialchars(BackendUtility::getModuleUrl('dummy')) . '" marginwidth="0" marginheight="0" frameborder="0" scrolling="no" noresize="noresize" />
			</frameset>
		</html>
		';
    }

    /**
     * Outputs the page content.
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
     * @return DocumentTemplate
     */
    protected function getDocumentTemplate()
    {
        return $GLOBALS['TBE_TEMPLATE'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        if ($this->pageRenderer === null) {
            $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        }

        return $this->pageRenderer;
    }
}
