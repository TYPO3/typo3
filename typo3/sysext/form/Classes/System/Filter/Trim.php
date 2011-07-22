<?php
declare(encoding = 'utf-8');

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
 * Trim filter
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_system_filter_trim implements tx_form_system_filter_interface {

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
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct($arguments = array()) {
		$this->setCharacterList($arguments['characterList']);
	}

	/**
	 * Set the characters that need to be stripped from the
	 * beginning or the end of the input,
	 * in addition to the default trim characters
	 *
	 * @param  string $characterList
	 * @return tx_form_filter_trim
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setCharacterList($characterList) {
		$this->characterList = $characterList;

		return $this;
	}
	/**
	 * Return filtered value
	 * Strip characters from the beginning and the end
	 *
	 * @param  string $value
	 * @return string
	 * @author Patrick Broens <patrick@patrickbroens.nl>
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