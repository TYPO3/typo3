<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Patrick Broens (patrick@patrickbroens.nl)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Main view layer for Forms.
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_View_Form extends tx_form_View_Form_Element_Container {
	/**
	 * @var string
	 */
	protected $expectedModelName = 'tx_form_Domain_Model_Form';

	/**
	 * Default layout of this object
	 *
	 * @var string
	 */
	protected $layout = '
		<form>
			<containerWrap />
		</form>';

	/**
	 * Set the data for the FORM tag
	 *
	 * @param tx_form_Domain_Model_Form $formModel The model of the form
	 * @return void
	 */
	public function setData(tx_form_Domain_Model_Form $model) {
		$this->model = (object) $model;
	}

	/**
	 * Start the main DOMdocument for the form
	 * Return it as a string using saveXML() to get a proper formatted output
	 * (when using formatOutput :-)
	 *
	 * @return string XHTML string containing the whole form
	 */
	public function get() {
		$this->setCss();
		$node = $this->render('element', FALSE);
		$content = chr(10) . html_entity_decode($node->saveXML($node->firstChild), ENT_QUOTES, 'UTF-8') . chr(10);

		return $content;
	}

	/**
	 * Add the form CSS file as additional header data
	 *
	 * @return void
	 */
	protected function setCss() {
		$GLOBALS['TSFE']->additionalHeaderData['tx_form_css'] =
			'<link rel="stylesheet" type="text/css" href="' .
			t3lib_extMgm::siteRelPath('form') .
			'Resources/Public/CSS/Form.css' .
			'" media="all" />';
	}
}
?>