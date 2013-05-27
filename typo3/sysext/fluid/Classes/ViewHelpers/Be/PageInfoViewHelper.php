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
 * View helper which return page info icon as known from TYPO3 backend modules
 * Note: This view helper is experimental!
 *
 * = Examples =
 *
 * <code>
 * <f:be.pageInfo />
 * </code>
 *
 * Output:
 * Page info icon with context menu
 *
 * @package     Fluid
 * @subpackage  ViewHelpers\Be
 * @author      Steffen Kamper <info@sk-typo3.de>
 * @author      Bastian Waidelich <bastian@typo3.org>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id:
 *
 */
class Tx_Fluid_ViewHelpers_Be_PageInfoViewHelper extends Tx_Fluid_ViewHelpers_Be_AbstractBackendViewHelper {


	/**
	 * Render javascript in header
	 *
	 * @return string the rendered page info icon
	 * @see template::getPageInfo() Note: can't call this method as it's protected!
	 */
	public function render() {
		$doc = $this->getDocInstance();
		$id = t3lib_div::_GP('id');
		$pageRecord = t3lib_BEfunc::readPageAccess($id, $GLOBALS['BE_USER']->getPagePermsClause(1));

				// Add icon with clickmenu, etc:
		if ($pageRecord['uid'])	{	// If there IS a real page
			$alttext = t3lib_BEfunc::getRecordIconAltText($pageRecord, 'pages');
			$iconImg = t3lib_iconWorks::getIconImage('pages', $pageRecord, $this->backPath, 'class="absmiddle" title="'. htmlspecialchars($alttext) . '"');
				// Make Icon:
			$theIcon = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg, 'pages', $pageRecord['uid']);
		} else {	// On root-level of page tree
				// Make Icon
			$iconImg = '<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/i/_icon_website.gif') . ' alt="' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']) . '" />';
			if($BE_USER->user['admin']) {
				$theIcon = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg, 'pages', 0);
			} else {
				$theIcon = $iconImg;
			}
		}

			// Setting icon with clickmenu + uid
		$pageInfo = $theIcon . '<em>[pid: ' . $pageRecord['uid'] . ']</em>';
		return $pageInfo;
	}
}
?>
