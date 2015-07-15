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

use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Script Class, putting the frameset together.
 */
class ElementBrowserFramesetController {

	/**
	 * Internal, dynamic
	 *
	 * @var string
	 */
	public $content;

	/**
	 * @var PageRenderer
	 */
	protected $pageRenderer = NULL;

	/**
	 * Main function.
	 * Creates the header code in XHTML, the JavaScript, then the frameset for the two frames.
	 *
	 * @return void
	 */
	public function main() {
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
		$url = $GLOBALS['BACK_PATH'] . $moduleUrl . rawurlencode($mode) . '&bparams=' . rawurlencode($bparams);

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
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * @return DocumentTemplate
	 */
	protected function getDocumentTemplate() {
		return $GLOBALS['TBE_TEMPLATE'];
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * @return PageRenderer
	 */
	protected function getPageRenderer() {
		if ($this->pageRenderer === NULL) {
			$this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
		}

		return $this->pageRenderer;
	}

}
