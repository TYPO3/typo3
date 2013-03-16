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
 * Attribute 'action'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class ActionAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Gets the attribute 'action'
	 * Used with the element 'form'
	 * Lower case
	 *
	 * This attribute specifies a form processing agent.
	 * User agent behavior for a value other than an HTTP URI is undefined.
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		$value = $this->value;
		if (empty($value)) {
			$value = $GLOBALS['TSFE']->id;
		}
		$attribute = $this->localCobj->getTypoLink_URL($value);
		return $attribute;
	}

}

?>