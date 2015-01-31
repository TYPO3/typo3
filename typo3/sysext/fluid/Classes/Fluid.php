<?php
namespace TYPO3\CMS\Fluid;

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

class Fluid {

	const LEGACY_NAMESPACE_SEPARATOR = '_';
	const NAMESPACE_SEPARATOR = '\\';

	/**
	 * Can be used to enable the verbose mode of Fluid.
	 *
	 * This enables the following things:
	 * - ViewHelper argument descriptions are being parsed from the PHPDoc
	 *
	 * This is NO PUBLIC API and the way this mode is enabled might change without
	 * notice in the future.
	 *
	 * @var boolean
	 */
	static public $debugMode = FALSE;
}
