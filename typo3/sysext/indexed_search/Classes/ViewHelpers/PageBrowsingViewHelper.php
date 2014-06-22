<?php
namespace TYPO3\CMS\IndexedSearch\ViewHelpers;

/**
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
 * Page browser for indexed search, and only useful here, as the
 * regular pagebrowser
 * so this is a cleaner "pi_browsebox" but not a real page browser
 * functionality
 *
 * @author 	Benjamin Mack <benni@typo3.org>
 */
class PageBrowsingViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var string
	 */
	protected $prefixId = 'tx_indexedsearch';

	/**
	 * Main render function
	 *
	 * @param integer $maximumNumberOfResultPages
	 * @param integer $numberOfResults
	 * @param integer $resultsPerPage
	 * @param integer $currentPage
	 * @param string|NULL $freeIndexUid
	 * @return string The content
	 */
	public function render($maximumNumberOfResultPages, $numberOfResults, $resultsPerPage, $currentPage = 1, $freeIndexUid = NULL) {
		$pageCount = ceil($numberOfResults / $resultsPerPage);
		$content = '';
		// only show the result browser if more than one page is needed
		if ($pageCount > 1) {
			$currentPage = (int)$currentPage;
			// prev page
			// show on all pages after the 1st one
			if ($currentPage > 0) {
				$label = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('displayResults.previous', 'IndexedSearch');
				$content .= '<li>' . $this->makecurrentPageSelector_link($label, $currentPage - 1, $freeIndexUid) . '</li>';
			}
			$maximumNumberOfResultPages = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($maximumNumberOfResultPages, 1, 200000000, 10);
			$min = max(0, $currentPage + 1 - ceil($maximumNumberOfResultPages / 2));
			$max = $min + $maximumNumberOfResultPages;
			if ($max > $pageCount) {
				$min -= $max - $pageCount;
			}
			for ($a = $min; $a < $pageCount && $a < $max; $a++) {
				$label = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('displayResults.page', 'IndexedSearch');
				$label = trim($label . ' ' . ($a + 1));
				$label = $this->makecurrentPageSelector_link($label, $a, $freeIndexUid);
				if ($a === $currentPage) {
					$content .= '<li class="tx-indexedsearch-browselist-currentPage"><strong>' . $label . '</strong></li>';
				} else {
					$content .= '<li>' . $label . '</li>';
				}
			}
			// next link
			if ($currentPage + 1 < $pageCount) {
				$label = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('displayResults.next', 'IndexedSearch');
				$content .= '<li>' . $this->makecurrentPageSelector_link($label, ($currentPage + 1), $freeIndexUid) . '</li>';
			}
			$content = '<ul class="tx-indexedsearch-browsebox">' . $content . '</ul>';
		}

		return $content;
	}

	/**
	 * Used to make the link for the result-browser.
	 * Notice how the links must resubmit the form after setting the new currentPage-value in a hidden formfield.
	 *
	 * @param string $str String to wrap in <a> tag
	 * @param integer $p currentPage value
	 * @param string $freeIndexUid List of integers pointing to free indexing configurations to search. -1 represents no filtering, 0 represents TYPO3 pages only, any number above zero is a uid of an indexing configuration!
	 * @return string Input string wrapped in <a> tag with onclick event attribute set.
	 * @todo Define visibility
	 */
	public function makecurrentPageSelector_link($str, $p, $freeIndexUid) {
		$onclick = 'document.getElementById(\'' . $this->prefixId . '_pointer\').value=\'' . $p . '\';';
		if ($freeIndexUid !== NULL) {
			$onclick .= 'document.getElementById(\'' . $this->prefixId . '_freeIndexUid\').value=\'' . rawurlencode($freeIndexUid) . '\';';
		}
		$onclick .= 'document.getElementById(\'' . $this->prefixId . '\').submit();return false;';
		return '<a href="#" onclick="' . htmlspecialchars($onclick) . '">' . $str . '</a>';
	}

}
