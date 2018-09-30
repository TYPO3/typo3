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
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class, putting the frameset together.
 *
 * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
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
    protected $pageRenderer;

    /**
     * Constructor
     */
    public function __construct()
    {
        trigger_error(
            self::class . ' will be removed in TYPO3 v10.0. Use route wizard_element_browser instead.',
            E_USER_DEPRECATED
        );
        $GLOBALS['SOBE'] = $this;
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->main();
        return new HtmlResponse($this->content);
    }

    /**
     * Main function.
     * Creates the header code in XHTML, the JavaScript, then the frameset for the two frames.
     */
    public function main()
    {
        // Setting GPvars:
        $mode = GeneralUtility::_GP('mode');
        $bparams = GeneralUtility::_GP('bparams');
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $moduleUrl = (string)$uriBuilder->buildUriFromRoute('wizard_element_browser') . '&mode=';
        $documentTemplate = $this->getDocumentTemplate();
        $documentTemplate->JScode = GeneralUtility::wrapJS('
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
        $documentTemplate->startPage($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:TYPO3_Element_Browser'));

        // URL for the inner main frame:
        $url = $moduleUrl . rawurlencode($mode) . '&bparams=' . rawurlencode($bparams);

        // Create the frameset for the window
        // Formerly there were a ' onunload="closing();"' in the <frameset> tag - but it failed on Safari browser on Mac unless the handler was "onUnload"
        $this->content = $this->getPageRenderer()->render(PageRenderer::PART_HEADER) .
            '<frameset rows="*,1" framespacing="0" frameborder="0" border="0">
				<frame name="content" src="' . htmlspecialchars($url) . '" marginwidth="0" marginheight="0" frameborder="0" scrolling="auto" noresize="noresize" />
				<frame name="menu" src="' . htmlspecialchars((string)$uriBuilder->buildUriFromRoute('dummy')) . '" marginwidth="0" marginheight="0" frameborder="0" scrolling="no" noresize="noresize" />
			</frameset>
		</html>
		';
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
