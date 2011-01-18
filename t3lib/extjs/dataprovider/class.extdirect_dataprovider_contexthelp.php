<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Steffen Kamper <steffen@typo3.org>
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
 * ExtDirect DataProvider for ContextHelp
 */
class extDirect_DataProvider_ContextHelp {

	/**
	 * Fetch the context help for the given table/field parameters
	 *
	 * @param  string $table table identifier
	 * @param  string $field field identifier
	 * @return array complete help information
	 */
	public function getContextHelp($table, $field) {
		$helpTextArray = t3lib_befunc::helpTextArray($table, $field);
		$moreIcon = $helpTextArray['moreInfo'] ? t3lib_iconWorks::getSpriteIcon('actions-view-go-forward') : '';
		return array(
			'title' => $helpTextArray['title'],
			'description' => '<p class="t3-help-short' . ($moreIcon ? ' tipIsLinked' : '') . '">' .
					$helpTextArray['description'] . $moreIcon . '</p>',
			'id' => $table . '.' . $field,
			'moreInfo' =>  $helpTextArray['moreInfo']
		);
	}
}

?>
