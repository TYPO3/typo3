<?php
namespace TYPO3\CMS\Recordlist\Tree\View;

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
 * Extension class for the TBE record browser
 */
class ElementBrowserPageTreeView extends \TYPO3\CMS\Backend\Tree\View\ElementBrowserPageTreeView {

	/**
	 * Returns TRUE if a doktype can be linked (which is always the case here).
	 *
	 * @param int $doktype Doktype value to test
	 * @param int $uid uid to test.
	 * @return bool
	 */
	public function ext_isLinkable($doktype, $uid) {
		return TRUE;
	}

	/**
	 * Wrapping the title in a link, if applicable.
	 *
	 * @param string $title Title, ready for output.
	 * @param array $v The record
	 * @param bool $ext_pArrPages If set, pages clicked will return immediately, otherwise reload page.
	 * @return string Wrapping title string.
	 */
	public function wrapTitle($title, $v, $ext_pArrPages) {
		if ($ext_pArrPages) {
			$ficon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', $v);
			$onClick = 'return insertElement(\'pages\', \'' . $v['uid'] . '\', \'db\', ' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($v['title']) . ', \'\', \'\', ' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($ficon) . ',\'\',1);';
		} else {
			$onClick = 'return jumpToUrl(' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($this->getThisScript() . 'act=' . $GLOBALS['SOBE']->browser->act . '&mode=' . $GLOBALS['SOBE']->browser->mode . '&expandPage=' . $v['uid']) . ');';
		}
		return '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . $title . '</a>';
	}

}
