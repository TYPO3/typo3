<?php
namespace TYPO3\CMS\Form\Domain\Model\Attribute;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Patrick Broens (patrick@patrickbroens.nl)
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
 * Attribute 'enctype'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class EnctypeAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'enctype'.
	 * Used with the element 'form'
	 * Case Insensitive
	 *
	 * This attribute specifies the content type used to submit the form to the
	 * server (when the value of method is "post"). The default value for this
	 * attribute is "application/x-www-form-urlencoded".
	 * The value "multipart/form-data" should be used in combination with the
	 * INPUT element, type="file".
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		$value = strtolower((string) $this->value);
		if ($value == 'multipart/form-data' || $value == 'application/x-www-form-urlencoded') {
			$attribute = $value;
		} elseif (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'])) {
			$attribute = $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'];
		}
		return $attribute;
	}

}

?>