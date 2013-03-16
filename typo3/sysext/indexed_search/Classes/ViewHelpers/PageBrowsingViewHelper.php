<?php
namespace TYPO3\CMS\IndexedSearch\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Benjamin Mack (benni@typo3.org)
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
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
	 * main render function
	 *
	 * @param array $details	an array with the browser settings
	 * @param integer $maximumNumberOfResultPages
	 * @param integer $numberOfResults
	 * @param integer $resultsPerPage
	 * @param integer $currentPage
	 * @return the content
	 */
	public function render($maximumNumberOfResultPages, $numberOfResults, $resultsPerPage, $currentPage = 1) {
		$maximumNumberOfResultPages = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($maximumNumberOfResultPages, 1, 100, 10);
		$pageCount = ceil($numberOfResults / $resultsPerPage);
		$content = '';
		// only show the result browser if more than one page is needed
		if ($pageCount > 1) {
			$currentPage = intval($currentPage);
			// prev page
			// show on all pages after the 1st one
			if ($currentPage > 0) {
				$label = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('displayResults.previous', 'indexed_search');
				$content .= '<li>' . $this->makecurrentPageSelector_link($label, ($currentPage - 1), $freeIndexUid) . '</li>';
			}
			for ($a = 0; $a < $pageCount; $a++) {
				$min = max(0, $currentPage + 1 - ceil($maximumNumberOfResultPages / 2));
				$max = $min + $maximumNumberOfResultPages;
				if ($max > $pageCount) {
					$min = $min - ($max - $pageCount);
				}
				if ($a >= $min && $a < $max) {
					$label = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('displayResults.page', 'indexed_search');
					$label = trim($label . ' ' . ($a + 1));
					$label = $this->makecurrentPageSelector_link($label, $a, $freeIndexUid);
					if ($a == $currentPage) {
						$content .= '<li class="tx-indexedsearch-browselist-currentPage"><strong>' . $label . '</strong></li>';
					} else {
						$content .= '<li>' . $label . '</li>';
					}
				}
			}
			// next link
			if ($currentPage + 1 < $pageCount) {
				$label = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('displayResults.next', 'indexed_search');
				$content = '<li>' . $this->makecurrentPageSelector_link($label, ($currentPage + 1), $freeIndexUid) . '</li>';
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
		$onclick = 'document.getElementById(\'' . $this->prefixId . '_currentPage\').value=\'' . $p . '\';' . 'document.getElementById(\'' . $this->prefixId . '_freeIndexUid\').value=\'' . rawurlencode($freeIndexUid) . '\';' . 'document.getElementById(\'' . $this->prefixId . '\').submit();return false;';
		return '<a href="#" onclick="' . htmlspecialchars($onclick) . '">' . $str . '</a>';
	}

}


?>