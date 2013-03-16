<?php
namespace TYPO3\CMS\Form\Filter;

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
 * Trim filter
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class TrimFilter implements \TYPO3\CMS\Form\Filter\FilterInterface {

	/**
	 * Characters used by trim filter
	 *
	 * @var string
	 */
	protected $characterList;

	/**
	 * Constructor
	 *
	 * @param array $arguments Filter configuration
	 */
	public function __construct(array $arguments = array()) {
		$this->setCharacterList($arguments['characterList']);
	}

	/**
	 * Set the characters that need to be stripped from the
	 * beginning or the end of the input,
	 * in addition to the default trim characters
	 *
	 * @param string $characterList
	 * @return tx_form_Filter_Trim
	 */
	public function setCharacterList($characterList) {
		$this->characterList = $characterList;
		return $this;
	}

	/**
	 * Return filtered value
	 * Strip characters from the beginning and the end
	 *
	 * @param string $value
	 * @return string
	 */
	public function filter($value) {
		if ($this->characterList === NULL) {
			return trim((string) $value);
		} else {
			return trim((string) $value, $this->characterList);
		}
	}

}

?>