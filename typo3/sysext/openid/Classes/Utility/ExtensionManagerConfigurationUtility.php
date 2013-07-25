<?php
namespace TYPO3\CMS\Openid\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) Christian Weiske <cweiske@cweiske.de>
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
 * class providing extensino configuration helpers for openid.
 *
 * @author Christian Weiske <cweiske@cweiske.de>
 */
class ExtensionManagerConfigurationUtility
{
	/**
	 * Render a textare for custom OpenId provider configuration.
	 *
	 * @param array $params Field information to be rendered
	 *                      - fieldName
	 *                      - fieldValue
	 *                      - propertyName
	 * @param \TYPO3\CMS\Core\TypoScript\ConfigurationForm $pObj The calling parent object.
	 *
	 * @return string Messages as HTML if something needs to be reported
	 */
	public function providerInput(array $params, $pObj) {
		return '<textarea rows="5" cols="80" name="' . $params['fieldName'] . '">'
			. htmlspecialchars($params['fieldValue'])
			. '</textarea>';
	}

}
?>