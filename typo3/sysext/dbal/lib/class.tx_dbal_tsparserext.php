<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Xavier Perseguers <typo3@perseguers.ch>
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
 * Class that renders fields for the Extension Manager configuration.
 *
 * $Id: class.tx_dbal_tsparserext.php 28572 2010-01-08 17:13:29Z xperseguers $
 * @author Xavier Perseguers <typo3@perseguers.ch>
 *
 * @package TYPO3
 * @subpackage dbal
 */
class tx_dbal_tsparserext {

	/**
	 * Renders a message for EM.
	 * 
	 * @param array $params
	 * @param t3lib_tsStyleConfig $tsObj
	 * @return string
	 */
	function displayMessage(array &$params, t3lib_tsStyleConfig $tsObj) {
		$out = '
			<div>
				<div class="typo3-message message-information">
					<div class="message-header">PostgreSQL</div>
					<div class="message-body">
						If you use a PostgreSQL database, make sure to run SQL scripts located in<br />
						<tt>' . t3lib_extMgm::extPath('dbal') . 'res/postgresql/</tt><br />
						to ensure best compatibility with TYPO3.
					</div>
				</div>
			</div>
		';

		return $out;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/lib/class.tx_dbal_tsparserext.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/lib/class.tx_dbal_tsparserext.php']);
}

?>