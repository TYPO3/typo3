<?php
namespace TYPO3\CMS\Form\Filter;

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
 * Regular expression filter
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class RegExpFilter implements \TYPO3\CMS\Form\Filter\FilterInterface {

	/**
	 * Regular expression for filter
	 *
	 * @var boolean
	 */
	protected $regularExpression;

	/**
	 * Constructor
	 *
	 * @param array $arguments Filter configuration
	 */
	public function __construct(array $arguments = array()) {
		$this->setRegularExpression($arguments['expression']);
	}

	/**
	 * Set the regular expression
	 *
	 * @param string $expression The regular expression
	 * @return void
	 */
	public function setRegularExpression($expression) {
		$this->regularExpression = (string) $expression;
	}

	/**
	 * Return filtered value
	 * Remove all characters found in regular expression
	 *
	 * @param string $value
	 * @return string
	 */
	public function filter($value) {
		return preg_replace($this->regularExpression, '', (string) $value);
	}

}
