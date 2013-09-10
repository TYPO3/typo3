<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *  (c) 2013 Jan Kiesewetter <janYYYY@t3easy.de>, t3easy
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Default view helper that is only usable within the SwitchViewHelper.
 * @see \TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper
 *
 * @api
 */
class DefaultViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @return string the contents of this view helper
	 * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
	 * @api
	 */
	public function render() {
		$viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
		if (!$viewHelperVariableContainer->exists('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')) {
			throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('The default View helper can only be used within a switch View helper', 1378796758);
		}
		return $this->renderChildren();
	}
}
?>