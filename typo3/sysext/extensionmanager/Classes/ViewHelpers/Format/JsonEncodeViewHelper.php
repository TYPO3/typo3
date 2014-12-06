<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers\Format;

/*
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
 * Wrapper for PHPs json_encode function.
 *
 * @see http://www.php.net/manual/en/function.json-encode.php
 * @internal
 */
class JsonEncodeViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Constructor
	 *
	 * @api
	 */
	public function __construct() {
		$this->registerArgument('additionalAttributes', 'array', 'Additional tag attributes. They will be added directly to the resulting HTML tag.', FALSE);
	}

	/**
	 * Replaces newline characters by HTML line breaks.
	 *
	 * @return string the altered string.
	 * @api
	 */
	public function render() {
		if ($this->hasArgument('additionalAttributes') && is_array($this->arguments['additionalAttributes'])) {
			return json_encode($this->arguments['additionalAttributes']);
		}
		$content = $this->renderChildren();
		return json_encode($content);
	}

}
