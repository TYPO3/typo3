<?php
namespace TYPO3\CMS\Styleguide\Utility;

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

/**
 * @author Benjamin Kott <info@bk2k.info>
 */
class ExtensionManagerConfigurationUtility {

	/**
	 * User 1
	 *
	 * @return    string
	 */
	public function user_1(&$params, &$tsObj) {
		$out = '';

		// Params;
		$out .= '<pre>';
		ob_start();
		var_dump($params);
		$out .= ob_get_contents();
		ob_end_clean();
		$out .= '</pre>';

		return $out;
	}

}
