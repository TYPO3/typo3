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
 *
 *
 * @package
 * @subpackage
 * @version $Id:$
 */
class Tx_Fluid_ViewHelpers_TypolinkViewHelper extends Tx_Fluid_Core_TagBasedViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'a';

	/**
	 * Arguments initialization
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function initializeArguments() {
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('target', 'Target of link', FALSE);
		$this->registerTagAttribute('rel', 'Specifies the relationship between the current document and the linked document', FALSE);
	}

	/**
	 * @param array $arguments Arguments
	 * @param string $pageUid Target page. See TypoLink destination
	 * @param array $options other TypoLink options
	 * @return string Rendered string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render(array $arguments = array(), $pageUid = NULL, array $options = array()) {
		$uriHelper = $this->variableContainer->get('view')->getViewHelper('Tx_Extbase_MVC_View_Helper_URIHelper');
		$uri = $uriHelper->typolinkURI($pageUid, $arguments, $options);
		$this->tag->addAttribute('href', $uri);
		$this->tag->setContent($this->renderChildren(), FALSE);

		return $this->tag->render();
	}
}


?>