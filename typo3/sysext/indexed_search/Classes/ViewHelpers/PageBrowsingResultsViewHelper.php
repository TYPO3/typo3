<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Benjamin Mack (benni@typo3.org)
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
 * renders the header of the results page
 *
 * @author	Benjamin Mack <benni@typo3.org>
 *
 */
class Tx_IndexedSearch_ViewHelpers_PageBrowsingResultsViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {


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
		$label = Tx_Extbase_Utility_Localization::translate('displayResults', 'indexed_search');

		$content = sprintf(
			$label,
			$firstResultOnPage,
			min(array($numberOfResults, $lastResultOnPage)),
			$numberOfResults
		);

		return $content;
	}

}

?>