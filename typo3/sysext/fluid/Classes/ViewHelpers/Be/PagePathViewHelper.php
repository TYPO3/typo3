<?php
/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * View helper which returns the current page path as known from TYPO3 backend modules
 * Note: This view helper is experimental!
 *
 * = Examples =
 *
 * <code>
 * <f:be.pagePath />
 * </code>
 *
 * Output:
 * Current page path, prefixed with "Path:" and wrapped in a span with the class "typo3-docheader-pagePath"
 *
 *
 * @package     Fluid
 * @subpackage  ViewHelpers\Be
 * @author      Steffen Kamper <info@sk-typo3.de>
 * @author      Bastian Waidelich <bastian@typo3.org>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id:
 *
 */
class Tx_Fluid_ViewHelpers_Be_PagePathViewHelper extends Tx_Fluid_ViewHelpers_Be_AbstractBackendViewHelper {


	/**
	 * Renders the current page path
	 *
	 * @return string the rendered page path
	 * @see template::getPagePath() Note: can't call this method as it's protected!
	 */
	public function render() {
		$doc = $this->getDocInstance();
		$id = t3lib_div::_GP('id');
		$pageRecord = t3lib_BEfunc::readPageAccess($id, $GLOBALS['BE_USER']->getPagePermsClause(1));

			// Is this a real page
		if ($pageRecord['uid'])	{
			$title = $pageRecord['_thePathFull'];
		} else {
			$title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
		}
			// Setting the path of the page
		$pagePath = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.path', 1) . ': <span class="typo3-docheader-pagePath">';

			// crop the title to title limit (or 50, if not defined)
		$cropLength = (empty($GLOBALS['BE_USER']->uc['titleLen'])) ? 50 : $GLOBALS['BE_USER']->uc['titleLen'];
		$croppedTitle = t3lib_div::fixed_lgd_cs($title, -$cropLength);
		if ($croppedTitle !== $title) {
			$pagePath .= '<abbr title="' . htmlspecialchars($title) . '">' . htmlspecialchars($croppedTitle) . '</abbr>';
		} else {
			$pagePath .= htmlspecialchars($title);
		}
		$pagePath .= '</span>';
		return $pagePath;
	}
}
?>
