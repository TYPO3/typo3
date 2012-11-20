<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be;

use \TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * The abstract base class for all backend view helpers
 * Note: backend view helpers are still experimental!
 */
abstract class AbstractBackendViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Gets instance of template if exists or create a new one.
	 * Saves instance in viewHelperVariableContainer
	 *
	 * @return \TYPO3\CMS\Backend\Template\DocumentTemplate $doc
	 */
	public function getDocInstance() {
		if ($this->viewHelperVariableContainer->exists(
			'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\AbstractBackendViewHelper',
			'DocumentTemplate'
		)
		) {
			$doc = $this->viewHelperVariableContainer->get(
				'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\AbstractBackendViewHelper',
				'DocumentTemplate'
			);
		} else {
			/** @var $doc \TYPO3\CMS\Backend\Template\DocumentTemplate */
			$doc = $this->createDocInstance();
			$doc->backPath = $GLOBALS['BACK_PATH'];
			$this->viewHelperVariableContainer->add(
				'TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\AbstractBackendViewHelper',
				'DocumentTemplate',
				$doc
			);
		}

		return $doc;
	}

	/**
	 * Other extensions may rely on the fact that $GLOBALS['SOBE'] exists and holds
	 * the DocumentTemplate instance. We should really get rid of this, but for now, let's be backwards compatible.
	 * Relying on $GLOBALS['SOBE'] is @deprecated since 6.0 and will be removed in 6.2 Instead ->getDocInstance() should be used.
	 *
	 * If $GLOBALS['SOBE']->doc holds an instance of \TYPO3\CMS\Backend\Template\DocumentTemplate we reuse it,
	 * if not we create a new one.
	 *
	 * Relying on $GLOBALS['SOBE'] is
	 * @deprecated since 6.0 and will be removed in 6.2 ->getDocInstance() should be used instead.
	 *
	 * @return \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	protected function createDocInstance() {
		if (
			isset($GLOBALS['SOBE']) &&
			is_object($GLOBALS['SOBE']) &&
			isset($GLOBALS['SOBE']->doc) &&
			$GLOBALS['SOBE']->doc instanceof \TYPO3\CMS\Backend\Template\DocumentTemplate
		) {
			GeneralUtility::deprecationLog('Usage of $GLOBALS[\'SOBE\'] is deprecated since 6.0 and will be removed in 6.2 ->getDocInstance() should be used instead');
			$doc = $GLOBALS['SOBE']->doc;
		} else {
			$doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
			if (!isset($GLOBALS['SOBE'])) {
				$GLOBALS['SOBE'] = new \stdClass();
			}
			if (!isset($GLOBALS['SOBE']->doc)) {
				$GLOBALS['SOBE']->doc = $doc;
			}
		}

		return $doc;
	}
}

?>