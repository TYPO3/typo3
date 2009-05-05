<?php

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
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id$
 */

/**
 * Email link view helper. Generates an email link incorporating TYPO3s spamProtectEmailAddresses-settings.
 *
 * Example
 *
 * (1) Basic email link:
 * <f:emaillink email="foo@bar.tld" />
 * 
 * Output:
 * <a href="javascript:linkTo_UnCryptMailto('ocknvq,hqqBdct0vnf');">foo(at)bar.tld</a>
 * (depending on your spamProtectEmailAddresses-settings)
 * 
 * (2) Email link with custom linktext:
 * <f:emaillink email="foo@bar.tld">some custom content</f:emaillink>
 *
 * Output:
 * <a href="javascript:linkTo_UnCryptMailto('ocknvq,hqqBdct0vnf');">some custom content</a>
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id$
 */
class Tx_Fluid_ViewHelpers_EmaillinkViewHelper extends Tx_Fluid_Core_TagBasedViewHelper {

	/**
	 * @var	string
	 */
	protected $tagName = 'a';

	/**
	 * @param string $email The email address to be turned into a link.
	 * @return string Rendered email link
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render($email) {
		list($linkHref, $linkText) = $GLOBALS['TSFE']->cObj->getMailTo($email, $email);
		$tagContent = $this->renderChildren();
		if ($tagContent !== '') {
			$linkText = $tagContent;
		}
		$this->tag->setContent($linkText, FALSE);
		$this->tag->addAttribute('href', $linkHref);

		return $this->tag->render();
	}
}


?>