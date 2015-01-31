<?php
namespace TYPO3\CMS\Compatibility6\Hooks\TypoScriptFrontendController;

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

/**
 * Class that hooks into TypoScriptFrontendController to do XHTML cleaning
 */
class ContentPostProcHook {

	/**
	 * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected $pObj;

	/**
	 * XHTML-clean the code, if flag config.xhtml_cleaning is set
	 * to "all"
	 *
	 * @param $parameters array
	 * @param $parentObject \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	public function contentPostProcAll(&$parameters, $parentObject) {
		$this->pObj = $parentObject;
		// XHTML-clean the code, if flag set
		if ($this->doXHTML_cleaning() == 'all') {
			$GLOBALS['TT']->push('XHTML clean, all', '');
			$XHTML_clean = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Html\HtmlParser::class);
			$this->pObj->content = $XHTML_clean->XHTML_clean($this->pObj->content);
			$GLOBALS['TT']->pull();
		}
	}

	/**
	 * XHTML-clean the code, if flag config.xhtml_cleaning is set
	 * to "cached"
	 *
	 * @param $parameters array
	 * @param $parentObject \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	public function contentPostProcCached(&$parameters, $parentObject) {
		$this->pObj = $parentObject;
		// XHTML-clean the code, if flag set
		if ($this->doXHTML_cleaning() == 'cached') {
			$GLOBALS['TT']->push('XHTML clean, cached', '');
			$XHTML_clean = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Html\HtmlParser::class);
			$this->pObj->content = $XHTML_clean->XHTML_clean($this->pObj->content);
			$GLOBALS['TT']->pull();
		}
	}

	/**
	 * XHTML-clean the code, if flag config.xhtml_cleaning is set
	 * to "output"
	 *
	 * @param $parameters array
	 * @param $parentObject \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	public function contentPostProcOutput(&$parameters, $parentObject) {
		$this->pObj = $parentObject;
		// XHTML-clean the code, if flag set
		if ($this->doXHTML_cleaning() == 'output') {
			$GLOBALS['TT']->push('XHTML clean, output', '');
			$XHTML_clean = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Html\HtmlParser::class);
			$this->pObj->content = $XHTML_clean->XHTML_clean($this->pObj->content);
			$GLOBALS['TT']->pull();
		}
	}

	/**
	 * Returns the mode of XHTML cleaning
	 *
	 * @return string Keyword: "all", "cached" or "output"
	 */
	protected function doXHTML_cleaning() {
		if ($this->pObj->config['config']['xmlprologue'] == 'none') {
			return 'none';
		}
		return $this->pObj->config['config']['xhtml_cleaning'];
	}
}
