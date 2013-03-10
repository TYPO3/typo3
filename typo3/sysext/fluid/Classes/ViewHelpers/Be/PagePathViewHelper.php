<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
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
 * <output>
 * Current page path, prefixed with "Path:" and wrapped in a span with the class "typo3-docheader-pagePath"
 * </output>
 */
class PagePathViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper {

	/**
	 * Renders the current page path
	 *
	 * @return string the rendered page path
	 * @see template::getPagePath() Note: can't call this method as it's protected!
	 */
	public function render() {
		$id = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');
		$pageRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($id, $GLOBALS['BE_USER']->getPagePermsClause(1));
		// Is this a real page
		if ($pageRecord['uid']) {
			$title = $pageRecord['_thePathFull'];
		} else {
			$title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
		}
		// Setting the path of the page
		$pagePath = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.path', 1) . ': <span class="typo3-docheader-pagePath">';
		// crop the title to title limit (or 50, if not defined)
		$cropLength = empty($GLOBALS['BE_USER']->uc['titleLen']) ? 50 : $GLOBALS['BE_USER']->uc['titleLen'];
		$croppedTitle = \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($title, -$cropLength);
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