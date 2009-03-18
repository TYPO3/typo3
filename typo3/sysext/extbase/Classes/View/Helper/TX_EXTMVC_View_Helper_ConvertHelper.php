<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
 * A For Helper
 *
 * @package TYPO3
 * @subpackage extmvc
 * @version $ID:$
 */
class TX_EXTMVC_View_Helper_ConvertHelper extends TX_EXTMVC_View_Helper_AbstractHelper {

	public function render($view, $content, $arguments) {
		$value = $content;
		$format = $arguments['format'];
		if (!is_string($format)) ; // TODO Throw exception?
		if ($value instanceof DateTime) {
			if ($format === NULL) {
				$value = $value->format('Y-m-d G:i'); // TODO Date time format from extension settings
			} else {
				$value = $value->format($format);
			}
		} else {
		}
		return $value;
	}
	

}

?>