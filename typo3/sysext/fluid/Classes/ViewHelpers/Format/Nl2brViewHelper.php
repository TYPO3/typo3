<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Format;

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
/**
 * Wrapper for PHPs nl2br function.
 *
 * @see http://www.php.net/manual/en/function.nl2br.php
 * @api
 */
class Nl2brViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Replaces newline characters by HTML line breaks.
	 *
	 * @return string the altered string.
	 * @api
	 */
	public function render() {
		$content = $this->renderChildren();
		return nl2br($content);
	}

}


?>