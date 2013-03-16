<?php
namespace TYPO3\CMS\Form\Validation;

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
 * File Allowed Types rule
 * The file type must fit one of the given mime types
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class FileAllowedTypesValidator extends \TYPO3\CMS\Form\Validation\AbstractValidator implements \TYPO3\CMS\Form\Validation\ValidatorInterface {

	/**
	 * The allowed types
	 *
	 * @var array
	 */
	protected $allowedTypes;

	/**
	 * Constructor
	 *
	 * @param array $arguments Typoscript configuration
	 */
	public function __construct($arguments) {
		$this->setAllowedTypes($arguments['types']);
		parent::__construct($arguments);
	}

	/**
	 * Returns TRUE if submitted value validates according to rule
	 *
	 * @return boolean
	 * @see \TYPO3\CMS\Form\Validation\ValidatorInterface::isValid()
	 */
	public function isValid() {
		if ($this->requestHandler->has($this->fieldName)) {
			$fileValue = $this->requestHandler->getByMethod($this->fieldName);
			$value = strtolower($fileValue['type']);
			if (!in_array($value, $this->allowedTypes)) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Set the allowed types
	 *
	 * @param string $allowedTypes Allowed Types
	 * @return object Rule object
	 */
	public function setAllowedTypes($allowedTypes) {
		$allowedTypes = strtolower($allowedTypes);
		$this->allowedTypes = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(', ', $allowedTypes);
		return $this;
	}

	/**
	 * Substitute makers in the message text
	 * Overrides the abstract
	 *
	 * @param string $message Message text with markers
	 * @return string Message text with substituted markers
	 */
	protected function substituteValues($message) {
		$message = str_replace('%allowedTypes', implode(',', $this->allowedTypes), $message);
		return $message;
	}

}

?>