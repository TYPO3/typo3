<?php
namespace TYPO3\CMS\Fluid\Core\ViewHelper;

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
 * @deprecated . Extend Tx_Fluid_Core_ViewHelper_AbstractTagBasedViewHelper instead!
 * @api
 */
abstract class TagBasedViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper {

	/**
	 * Constructor
	 */
	public function __construct() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('the ViewHelper "' . get_class($this) . '" extends "TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TagBasedViewHelper". This is deprecated since TYPO3 4.5. Please extend the class "TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\AbstractTagBasedViewHelper"');
		parent::__construct();
	}

}


?>