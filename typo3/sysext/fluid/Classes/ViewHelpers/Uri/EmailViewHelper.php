<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Uri;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */
/**
 * Email URI view helper.
 * Generates an email URI incorporating TYPO3s spamProtectEmailAddresses-settings.
 *
 * = Examples
 *
 * <code title="basic email URI">
 * <f:uri.email email="foo@bar.tld" />
 * </code>
 * <output>
 * javascript:linkTo_UnCryptMailto('ocknvq,hqqBdct0vnf');
 * (depending on your spamProtectEmailAddresses-settings)
 * </output>
 */
class EmailViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @param string $email The email address to be turned into a URI
	 * @return string Rendered email link
	 */
	public function render($email) {
		if (TYPO3_MODE === 'FE') {
			$emailParts = $GLOBALS['TSFE']->cObj->getMailTo($email, $email);
			return reset($emailParts);
		} else {
			return 'mailto:' . $email;
		}
	}
}

?>