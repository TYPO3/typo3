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
 * Strip new lines filter
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class StripNewLinesFilter implements \TYPO3\CMS\Form\Filter\FilterInterface {

	/**
	 * Strip newlines
	 *
	 * @param string $value
	 * @return string
	 */
	public function filter($value) {
		return str_replace(array(CRLF, LF, CR), ' ', (string)$value);
	}

}
