<?php
namespace TYPO3\CMS\Form\Filter;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Title filter
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class TitleCaseFilter implements FilterInterface {

	/**
	 * Convert alphabetic characters to title case
	 *
	 * @param string $value
	 * @return string
	 */
	public function filter($value) {
		$tsfe = $this->getTypoScriptFrontendController();
		$lower = $tsfe->csConvObj->conv_case($tsfe->renderCharset, $value, 'toLower');
		return ucwords($lower);
	}

	/**
	 * @return TypoScriptFrontendController
	 */
	protected function getTypoScriptFrontendController() {
		return $GLOBALS['TSFE'];
	}
}
