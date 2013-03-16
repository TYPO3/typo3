<?php
namespace TYPO3\CMS\Form\Filter;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Patrick Broens (patrick@patrickbroens.nl)
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Title filter
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class TitleCaseFilter implements \TYPO3\CMS\Form\Filter\FilterInterface {

	/**
	 * Convert alphabetic characters to title case
	 *
	 * @param string $value
	 * @return string
	 */
	public function filter($value) {
		return ucwords(strtolower((string) $value));
	}

}

?>