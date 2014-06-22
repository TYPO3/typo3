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
 * renders the header of the results page
 *
 * @author 	Benjamin Mack <benni@typo3.org>
 */
class PageBrowsingResultsViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * main render function
	 *
	 * @param integer $numberOfResults
	 * @param integer $resultsPerPage
	 * @param integer $currentPage
	 * @return the content
	 */
	public function render($numberOfResults, $resultsPerPage, $currentPage = 1) {
		$firstResultOnPage = $currentPage * $resultsPerPage + 1;
		$lastResultOnPage = $currentPage * $resultsPerPage + $resultsPerPage;
		$label = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('displayResults', 'indexed_search');
		$content = sprintf($label, $firstResultOnPage, min(array($numberOfResults, $lastResultOnPage)), $numberOfResults);
		return $content;
	}

}
